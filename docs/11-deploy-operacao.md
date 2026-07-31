# 11 — Deploy & Operação (Dokploy / Docker)

Este documento concentra **tudo que aprendemos na prática** ao subir o WMS no
Dokploy. Mantenha-o atualizado: toda vez que ajustarmos infraestrutura, anote
aqui para não repetir erros.

---

## 1. Visão geral da infraestrutura

O deploy usa **Docker Compose** no Dokploy, com subdomínio
`wmsapaevideira.mlsoftwares.com.br`. O Traefik do Dokploy faz o proxy/HTTPS
(gera o certificado automaticamente), então **não expomos portas no host** —
só a rede interna.

| Serviço  | Imagem/Estágio        | Função                                  |
|----------|-----------------------|-----------------------------------------|
| `app`    | estágio `app`         | PHP-FPM (Laravel) — entra no entrypoint|
| `nginx`  | estágio `nginx`       | Web server, serve o `public/build`      |
| `queue`  | estágio `app`         | `php artisan queue:work` (Redis)        |
| `reverb` | estágio `app`         | Websockets (laravel-echo)               |
| `mysql`  | `mysql:8.4`           | Banco (`wms_apae`)                      |
| `redis`  | `redis:7-alpine`      | Cache/filas/sessão                     |

O **entrypoint** (`docker/entrypoint.sh`) roda em `app`/`queue`/`reverb` e, a
cada deploy, executa: aguarda MySQL → `migrate --force` → `optimize` → sobe o
processo principal. **Isso significa que toda migration nova aplicada no push
já vai para o banco sozinha** — não precisa rodar manualmente.

---

## 2. Arquivos de infraestrutura

| Arquivo | O que é |
|---------|---------|
| `Dockerfile` | Multi-stage: `base` (PHP) → `node` (compila front) → `app` (PHP+vendor+build) → `nginx` (web server). |
| `docker-compose.yml` | **Arquivo de produção lido pelo Dokploy.** Serviços + redes + volumes. |
| `docker-compose.prod.yml` | Espelho do `.yml` (mantido por simetria). |
| `docker-compose.local.yml` | Dev local (bind mount do código, portas `8000/3307/6379`). |
| `docker/entrypoint.sh` | Migrate + optimize a cada deploy. |
| `docker/nginx/default.conf` | Vhost nginx (root `/var/www/public`, proxy do Reverb em `/app`). |
| `.env.prod` | Template de variáveis de produção (placeholders, **sem segredos**). |
| `.env.local` | Variáveis para dev local via Docker. |

> ⚠️ **O Dokploy lê o `docker-compose.yml` (padrão).** Se você editar só o
> `.prod.yml`, o deploy **não** reflete as mudanças. Sempre espelhe as
> alterações de produção no `docker-compose.yml`.

---

## 3. Deploy no Dokploy (passo a passo)

1. Crie o projeto apontando para o repositório GitHub.
2. Selecione **`docker-compose.yml`** como arquivo de compose.
3. Na aba **Environment**, cole as variáveis do `.env.prod` e **preencha os
   valores reais** (veja seção 4).
4. Marque o serviço **`nginx`** para receber o domínio
   `wmsapaevideira.mlsoftwares.com.br` (o Traefik emite o HTTPS).
5. Faça o deploy. O build roda `composer install` + `npm run build` dentro da
   imagem (deploy reproduzível).

---

## 4. Variáveis de ambiente obrigatórias (Dokploy)

O `docker-compose.yml` repassa um bloco `x-app-environment` para os containers
PHP (`app`/`queue`/`reverb`). **Se faltar, o container não enxerga o banco.**

Mínimo para funcionar:

```
APP_KEY=            # gere com: php artisan key:generate --show
APP_URL=https://wmsapaevideira.mlsoftwares.com.br
DB_HOST=mysql       # nome do serviço no compose, NÃO 127.0.0.1
DB_DATABASE=wms_apae
DB_USERNAME=wms_apae
DB_PASSWORD=********
REDIS_HOST=redis
REVERB_HOST=wmsapaevideira.mlsoftwares.com.br
REVERB_PORT=443
REVERB_SCHEME=https
```

> ⚠️ **Armadilha de senha:** o `DB_PASSWORD` tem caracteres especiais
> (`$`, `<`, `>`, `@`, `!`). O shell/YAML do Docker pode interpretá-los e o
> MySQL acaba gravando uma senha **diferente** da do `.env`. Recomendamos
> senhas **sem** `$ < > " \ ` (ex.: `Apae_Wms_Vide1ra_9Kx2Qm7Rn4Tp8Zc`). Se
> suspeitar, rode o `ALTER USER` da seção 7.

> ⚠️ **MySQL 8.4 não suporta `mysql_native_password`** (removido). O phpMyAdmin
> `latest` já autentica com `caching_sha2_password`, então não force o plugin.

---

## 5. Build do front (estágio node)

O `npm run build` roda **dentro da imagem** (estágio `node`), e o `public/build`
é copiado para os estágios `app` e `nginx`. Não depende do host.

- O `Dockerfile` usa `npm install` (não `npm ci`) porque o lock de
  optionalDependencies nativas (`@emnapi/core/runtime`) diverge entre plataformas
  (Windows × container) e o `npm ci` falhava com `EUSAGE`.
- **Não use `--omit=dev`**: o `vite`/`tailwind` (necessários ao build) estão em
  `devDependencies`. Tirar dá `vite: not found`.

---

## 6. Ordem dos Seeders (IMPORTANTE — fonte de 403)

O `DatabaseSeeder` roda na ordem:

```
RolesAndPermissionsSeeder  →  CatalogSeeder  →  AdminUserSeeder
```

**Nunca rode o `AdminUserSeeder` isolado** (`--class=AdminUserSeeder`) sem antes
ter rodado o `RolesAndPermissionsSeeder`. Motivo: o `DashboardController` exige
a permissão `reports.view`:

```php
abort_unless(request()->user()->can('reports.view'), 403);
```

Se as permissões não foram semeadas, `can('reports.view')` é `false` → **403
Forbidden logo após o login**. O papel `administrador` recebe `Permission::all()`
apenas quando o `RolesAndPermissionsSeeder` roda.

### Forma correta de popular o banco
```sh
# Dentro do container wms_app (use `sh`, NÃO `bash` — o Alpine não tem bash):
php artisan db:seed
```
Isso roda tudo na ordem certa e cria o admin inicial.

### Criar o admin inicial (credenciais padrão)
O `AdminUserSeeder` cria:
- **email:** `admin@wms.local`
- **senha:** `Admin@123456`
- papel `administrador` + `is_active = true`

> ⚠️ **Troque essa senha antes de expor em produção** (seção 7).

### Criar um admin com seu próprio e-mail (tinker)
```sh
php artisan tinker
```
```php
use App\Models\User;
$user = User::create([
    'name' => 'Administrador',
    'email' => 'voce@dominio.com',
    'password' => bcrypt('SuaSenhaForte123!'),
    'is_active' => true,
]);
$user->assignRole('administrador');
```
> Nota: não há tela de registro (`/register` não existe). Novos usuários são
> criados por um admin em `GET /admin/usuarios/novo`.

---

## 7. Trocar a senha do banco (sem quebrar)

**Trocar só o `DB_PASSWORD` no `.env` NÃO muda a senha do MySQL.** A senha só é
aplicada na **criação** do container (quando o volume `mysql_data` é criado).

Para trocar com segurança (mantendo o banco):
1. No `.env` do Dokploy: `DB_PASSWORD=NOVA_SENHA`
2. Dentro do container `wms_mysql`, conecte como root:
   ```sh
   mysql -uroot -p"$MYSQL_ROOT_PASSWORD"
   ```
3. Redefina a senha do usuário (host `%`):
   ```sql
   ALTER USER 'wms_apae'@'%' IDENTIFIED BY 'NOVA_SENHA';
   FLUSH PRIVILEGES;
   ```
4. Re-deploy o WMS para pegar o novo `.env`.

> ⚠️ Se **recriar do zero** o banco (dropar o volume `mysql_data`), o MySQL
> recria o usuário com a senha do `.env` — mas **apaga todos os dados**. Só
> faça com banco vazio ou backup.

---

## 8. Conectar o phpMyAdmin (outro stack) ao MySQL do WMS

O Dokploy cria a rede compartilhada `dokploy-network` entre stacks. Para o
phpMyAdmin de outro projeto alcançar o `wms_mysql`:

1. O `docker-compose.yml` do WMS já está na `dokploy-network` (serviços
   `app`/`nginx`/`queue`/`reverb`/`mysql`/`redis`).
2. No compose do phpMyAdmin, use:
   ```yaml
   services:
     phpmyadmin:
       image: phpmyadmin:latest      # multi-arch (NÃO use arm64v8/ em servidor x86)
       expose:
         - "80"
       environment:
         PMA_HOST: wms_mysql         # container_name do MySQL do WMS
         PMA_PORT: 3306
         PMA_USER: wms_apae
         # PMA_PASSWORD: <DB_PASSWORD do WMS>  (opcional — pede no login)
       networks:
         - dokploy-network
   networks:
     dokploy-network:
       external: true
   ```
3. Login no phpMyAdmin: servidor `wms_mysql`, usuário `wms_apae`, senha do
   `DB_PASSWORD`.

> ⚠️ **Segurança:** o phpMyAdmin terá acesso total ao banco de produção.
> Recomenda-se protegê-lo atrás de autenticação (Basic Auth no Traefik) e usar
> senha forte no `DB_PASSWORD`.

---

## 9. Problemas comuns (e soluções)

| Sintoma | Causa | Solução |
|---------|-------|---------|
| `Call to undefined method ...Controller::create()` | método faltando | implementar o método na controller |
| `Table 'wms.xxx' doesn't exist` | migration não rodou / tabela com nome errado | `php artisan migrate --force` |
| `php: not found` (queue/reverb) | compose sem `target: app` pegou estágio `nginx` | definir `target: app` nos serviços PHP |
| `vite: not found` | `npm install --omit=dev` | remover `--omit=dev` |
| `npm ci` EUSAGE (`@emnapi` missing) | lock dessincronizado | usar `npm install` no Dockerfile |
| 502 `Connection refused` em `fastcgi://...:9000` | `php-fpm` não subiu (entrypoint travado) | checar se `DB_HOST` está preenchido no Dokploy |
| `PDO ... No such file or directory` no entrypoint | `DB_HOST` vazio → PDO cai em socket | preencher `DB_HOST=mysql` no Environment |
| Login → **403 Forbidden** | permissões não semeadas | rodar `php artisan db:seed` (não só AdminUserSeeder) |
| phpMyAdmin: `Access denied (1045)` | senha do MySQL ≠ `.env` | `ALTER USER ... IDENTIFIED BY '<senha do .env>'` |
| `Plugin 'mysql_native_password' is not loaded` | MySQL 8.4 removeu o plugin | não force o plugin; use `caching_sha2_password` (padrão) |
| Terminal do container: `bash: not found` | imagem Alpine não tem bash | usar `sh` em vez de `bash` |
| CSS não carrega em produção | nginx servia do host, sem `public/build` | nginx usa estágio `nginx` (build embutido) |

---

## 10. Comandos úteis (dentro dos containers)

```sh
# Entrar no container (Alpine → sh, não bash):
docker exec -it wms_app sh

# Rodar migrate manualmente:
php artisan migrate --force

# Rodar todos os seeders (ordem correta):
php artisan db:seed

# Só roles/permissões:
php artisan db:seed --class=RolesAndPermissionsSeeder

# Tinker:
php artisan tinker

# Ver logs do Laravel:
tail -f storage/logs/laravel.log
```
