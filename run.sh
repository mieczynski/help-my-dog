#!/usr/bin/env bash
set -euo pipefail

# --- config ---
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
API_DIR="$PROJECT_ROOT/api"
COMPOSE="docker compose"

function usage() {
  cat <<'USAGE'
Usage: ./run.sh <command>

Common commands:
  up           Build & start all services, install vendors, generate JWT keys, run migrations & fixtures
  fresh        Stop, remove volumes, then 'up' from scratch
  down         Stop all services
  ps           Show container status
  logs         Tail logs (Ctrl+C to exit)
  migrate      Run Doctrine migrations
  fixtures     Load Doctrine fixtures (WARNING: may purge data in dev)
  keys         (Re)generate JWT keypair
  sh           Shell into php container
USAGE
}

function ensure_env() {
  mkdir -p "$API_DIR/config/jwt"
  if [ ! -f "$API_DIR/.env.local" ]; then
    echo "Creating api/.env.local with defaults…"
    cat > "$API_DIR/.env.local" <<EOF
JWT_PASSPHRASE=change_this_dev_key
# Uncomment and adjust if needed:
# DATABASE_URL="postgresql://help_my_dog:help_my_dog@db:5432/help_my_dog?serverVersion=16&charset=utf8"
EOF
  fi
}

function build() {
  $COMPOSE build --pull
}

function up() {
  $COMPOSE up -d db redis
  echo "Waiting for database & redis health…"
  $COMPOSE wait db redis || true

  $COMPOSE up -d php nginx frontend
  echo "Waiting for PHP & Nginx to be healthy…"
  # Give php a moment to start up
  sleep 3
}

function composer_install() {
  $COMPOSE exec -T php bash -lc 'cd /var/www/html/api && composer install --prefer-dist --no-interaction'
}

function generate_keys() {
  # Requires JWT_PASSPHRASE in .env.local
  $COMPOSE exec -T php bash -lc 'cd /var/www/html/api && php bin/console lexik:jwt:generate-keypair --overwrite --skip-if-exists'
}

function migrate() {
  $COMPOSE exec -T php bash -lc 'cd /var/www/html/api && php bin/console doctrine:migrations:migrate -n'
}

function fixtures() {
  $COMPOSE exec -T php bash -lc 'cd /var/www/html/api && php bin/console doctrine:fixtures:load -n'
}

cmd="${1:-}"
case "$cmd" in
  up)
    ensure_env
    build
    up
    composer_install
    generate_keys
    migrate
    fixtures
    echo "Done. API: http://localhost:8080  Frontend: http://localhost:5173"
    ;;
  fresh)
    $COMPOSE down -v
    rm -rf "$API_DIR/var" || true
    up
    ;;
  migrate)
    migrate
    ;;
  fixtures)
    fixtures
    ;;
  keys)
    generate_keys
    ;;
  down)
    $COMPOSE down
    ;;
  ps)
    $COMPOSE ps
    ;;
  logs)
    $COMPOSE logs -f
    ;;
  sh)
    $COMPOSE exec php bash
    ;;
  ""|-h|--help|help)
    usage
    ;;
  *)
    echo "Unknown command: $cmd"
    usage
    exit 1
    ;;
esac
