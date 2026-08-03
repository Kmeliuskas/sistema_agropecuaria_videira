# WMS — Gestão de Almoxarifado

Sistema web de **controle de almoxarifado (WMS)** para empresas que precisam
gerenciar produtos, estoques, movimentações e solicitações de materiais em um
ou mais almoxarifados.

> Este documento é a **documentação para o usuário final** — quem vai usar o
> sistema no dia a dia. Para detalhes técnicos (arquitetura, como o sistema foi
> feito, "macetes" de desenvolvimento), veja a pasta [`docs/`](docs/).

---

## O que é o WMS

O WMS é o lugar onde a empresa cadastra seus produtos, acompanha quanto tem em
cada almoxarifado, registra tudo que entra e sai (Kardex) e controla as
solicitações de material feitas pelos setores.

Tudo é acessado por um **navegador** (Chrome, Edge, Firefox), não precisa
instalar nada. Funciona no computador e no celular/tablet.

### Acesso

| Item | Valor |
|------|-------|
| Endereço | fornecido pelo administrador (ex.: `http://localhost:8000`) |
| Usuário inicial | `admin@wms.local` |
| Senha inicial | `Admin@123456` |

> ⚠️ Troque a senha do administrador antes de colocar o sistema em produção.

---

## Telas e o que cada uma faz

A tela inicial é o **Painel**, que mostra os números gerais. No menu lateral
(esquerda) estão todos os módulos.

### Painel (início)
Visão geral com indicadores:
- **Produtos ativos**, **Almoxarifados**, **Itens em estoque** e **Valor em estoque**.
- **Alertas de estoque baixo** — produtos abaixo do mínimo definido.
- **Curva ABC (por valor)** — classificação dos produtos em classes A, B e C.
- **Solicitações de material** — pendentes, em separação e em conferência.
- **Operacional** — inventários em andamento e movimentações dos últimos 30 dias.

### Cadastros
- **Produtos** — cadastro completo (nome, código, código de barras, categoria,
  subcategoria, marca, fabricante, unidade, almoxarifado, estoques mín/máx/atual,
  custo, preço de venda). Permite **criar, editar, visualizar e excluir**.
  A tela de detalhe mostra o **saldo do produto em cada almoxarifado**.
- **Almoxarifados** — lista dos almoxarifados cadastrados (Central, Filial,
  Produção etc.) com tipo, responsável e cidade.
- **Setores** — cadastro de setores solicitantes (criar, editar, excluir).

### Estoque
- **Posição de estoque** — saldos por produto × almoxarifado (atual, reservado,
  disponível, bloqueado). Filtra por almoxarifado e por saldo negativo.

### Solicitações
- **Solicitação de Materiais** — fluxo de 6 etapas:
  `solicitado → aprovado → separado → conferido → entregue → finalizado`.
  - Na criação, você escolhe o almoxarifado e adiciona os itens desejados.
  - Quem aprova libera a separação; na entrega, o material é **descontado do
    estoque automaticamente**.
  - Cada solicitação mostra solicitante, aprovador, justificativa e os itens
    (solicitado / aprovado / entregue).

### Movimentações
- **Histórico (Kardex)** — todo registro de entrada, saída, transferência e
  ajuste, com data, tipo, almoxarifado, motivo, quantidade, saldo após e usuário.
  Filtra por tipo e almoxarifado.

### Catálogos (leitura)
Listas de apoio usadas nos cadastros: **Categorias, Subcategorias, Marcas,
Fabricantes, Fornecedores e Unidades de Medida**.

---

## Como navegar

- Clique em qualquer item do menu à esquerda para abrir o módulo.
- A navegação entre telas **não recarrega a página inteira** (é instantânea,
  estilo aplicativo). A barra lateral e o tema escuro permanecem como estavam.
- Use os **filtros** (busca, almoxarifado, situação) e clique em **Filtrar**
  para refinar listas. O botão **Limpar** remove os filtros.
- As listas são paginadas; use os botões de página no rodapé.

### No celular / tela pequena
- A barra lateral fica escondida. Toque no **ícone de menu** (canto superior
  esquerdo) para abri-la. Toque fora dela (no escurecido) para fechar.

### Tema claro / escuro
- No canto inferior da barra lateral há o botão de **sol/lua** para alternar
  entre tema claro e escuro. A escolha é lembrada na próxima visita.

### Sair
- Botão **Sair** no canto inferior da barra lateral.

---

## O que o sistema NÃO faz (ainda)

Para evitar confusão, estas funcionalidades previstas **não estão disponíveis
na interface web nesta versão**:

- **Criar/editar/excluir direto pela tela**: Almoxarifados e os Catálogos
  (categorias, marcas, etc.) aparecem como lista de leitura. Seu cadastro é
  feito pelo administrador no banco/API.
- **Registro manual de entrada/saída/ajuste pela tela** — o Kardex é alimentado
  pelas operações (entrega de solicitação, ajustes e transferências feitas via
  API/backend).
- **Transferência entre almoxarifados e Ajustes de estoque** — existem no
  backend, mas ainda não têm tela própria.
- **Inventário (contagem e ajuste)** — disponível no backend, sem tela ainda.
- **Controle de Lotes, Validade e Serial Number** — modelos existem, sem tela.
- **Relatórios em PDF/Excel e exportação** — não implementados na interface.
- **Notificações** (e-mail/WhatsApp) e **integração com ERPs** (Protheus, SAP…).
- **Reservas de estoque** pela interface.

> O sistema já possui uma **API REST completa** (`/api/v1`) usada por integrações
> e por um front-end Angular em desenvolvimento. Veja `docs/07-api.md`.

---

## Dicas e boas práticas

- **Código interno e código de barras** do produto devem ser únicos — o sistema
  impede duplicados.
- **Estoque mínimo** define quando o produto aparece nos alertas do Painel.
- O **custo médio** do produto é recalculado automaticamente a cada entrada; não
  é editado manualmente.
- Produtos e setores excluídos podem ser recuperados (exclusão lógica) — fale com
  o administrador.
- Toda movimentação e alteração fica registrada em **auditoria** (quem, quando,
  o quê) — nada é apagado de verdade.

---

## Perguntas frequentes

**A página não atualiza ao trocar de tela, está quebrado?**
Não. A navegação é propositalmente instantânea (sem refresh). Se algo parecer
desatualizado, use o botão de atualizar do navegador.

**Perdi a senha / fui bloqueado.**
Apenas o administrador pode redefinir. Peça o reset pelo suporte.

**Posso usar no celular?**
Sim. O sistema se adapta à tela; o menu vira um drawer tocável.

**Os números do estoque estão errados?**
O estoque é impactado por entregas de solicitação, ajustes e transferências.
Consulte o **Histórico (Kardex)** para ver o rastro completo de cada produto.

---

## Para quem desenvolve ou administra

- Documentação técnica completa (arquitetura, módulos, "macetes" de
  desenvolvimento): pasta [`docs/`](docs/).
- Documentação da API REST: `docs/07-api.md`.
- **Deploy, operação e troubleshooting** (Dokploy/Docker, ordem correta dos
  seeders, phpMyAdmin, troca de senha do MySQL, erros comuns):
  [`docs/11-deploy-operacao.md`](docs/11-deploy-operacao.md). ⚠️ Leia este
  antes de subir ou mexer na infraestrutura — evita os erros que já passamos.
- Como rodar o projeto (Docker / local): veja `docs/02-arquitetura.md` e o
  `composer setup`.

### Primeiro acesso em produção
1. Suba o deploy no Dokploy (veja o doc de deploy).
2. Rode os seeders dentro do container (`php artisan db:seed`) — **não rode só
   o `AdminUserSeeder`**, senão o login dá 403.
3. Acesse com `admin@wms.local` / `Admin@123456` e **troque a senha** do
   administrador imediatamente.
4. rode 
```
  php artisan cache:clear
  php artisan config:clear
  php artisan route:clear
  php artisan view:clear
  php artisan optimize:clear
```
### Permissoes
Resumo do que foi ajustado
1. app/Support/PermissionLabels.php — Adicionado o módulo attributes nos dois métodos:

moduleLabel(): 'attributes' => 'Atributos' (rótulo amigável)
modules(): 'attributes' => true (inclusão na lista de módulos conhecidos)
2. database/seeders/RolesAndPermissionsSeeder.php — Adicionado:

'attributes' => ['view', 'create', 'update', 'delete'] no array $modules
'attributes.view' na lista $catalogViews (garante que todos os papéis não-admin possam visualizar)
