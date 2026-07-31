#!/bin/sh
# NOTA: sem `set -e` para que um erro de optimize não derrube o container
# e impeça o php-fpm de subir. O migrate é essencial; o cache é tolerante.

# Entrypoint do app em produção.
# Roda as tarefas de "deploy" (migrations, cache) ANTES de subir o
# processo principal (php-fpm). Assim, a cada push/deploy no Dokploy,
# o banco e a configuração ficam atualizados automaticamente.

echo "==> [entrypoint] Aguardando o MySQL estar pronto (timeout 120s)..."
# Tenta conectar ao banco até o host/porta responder (evita race condition
# se o container app subir antes do mysql terminar de inicializar).
ATTEMPTS=0
until php -r "new PDO('mysql:host='.getenv('DB_HOST').';port='.getenv('DB_PORT'), getenv('DB_USERNAME'), getenv('DB_PASSWORD')); exit(0);" 2>/dev/null; do
  ATTEMPTS=$((ATTEMPTS + 1))
  if [ "$ATTEMPTS" -ge 40 ]; then
    echo "    [AVISO] MySQL não respondeu em 120s. Se DB_HOST está vazio,"
    echo "    defina as variáveis de ambiente no Dokploy. Subindo mesmo assim."
    break
  fi
  echo "    MySQL ainda não responde, aguardando... ($ATTEMPTS/40)"
  sleep 3
done
echo "==> [entrypoint] MySQL disponível (ou timeout atingido)."

echo "==> [entrypoint] Rodando migrations..."
php artisan migrate --force

echo "==> [entrypoint] Rodando seed de permissões (se necessário)..."
php artisan db:seed --class=RolesAndPermissionsSeeder --force

echo "==> [entrypoint] Otimizando (config/cache/routes)..."
php artisan optimize:clear
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Garante que os artefatos gerados (cache, storage) pertençam ao www-data,
# para o php-fpm (que roda como www-data) conseguir escrever neles.
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true

echo "==> [entrypoint] Iniciando o processo principal: $@"
exec "$@"
