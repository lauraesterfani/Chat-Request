#!/usr/bin/env bash
set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
test_root="$(mktemp -d /tmp/chat-request-phase7-e2e.XXXXXX)"
database_path="$test_root/database.sqlite"
api_port="${PHASE7_API_PORT:-18100}"
frontend_port="${PHASE7_FRONTEND_PORT:-3011}"
next_dist_dir=".next-phase7-$$"
api_pid=""
frontend_pid=""

port_in_use() {
  timeout 1 bash -c "</dev/tcp/127.0.0.1/$1" >/dev/null 2>&1
}

if port_in_use "$api_port" || port_in_use "$frontend_port"; then
  echo "As portas isoladas $api_port e $frontend_port já estão em uso. Encerre o serviço de teste conflitante e tente novamente."
  exit 1
fi

cleanup() {
  if [[ -n "$frontend_pid" ]]; then kill "$frontend_pid" 2>/dev/null || true; wait "$frontend_pid" 2>/dev/null || true; fi
  if [[ -n "$api_pid" ]]; then kill "$api_pid" 2>/dev/null || true; fi
  if [[ "$next_dist_dir" == .next-phase7-* ]]; then rm -rf -- "$repo_root/frontend/$next_dist_dir"; fi
}
trap cleanup EXIT INT TERM

touch "$database_path"
export APP_ENV=testing
export LARAVEL_STORAGE_PATH="$test_root/storage"
export APP_KEY='base64:ZmFrZS10ZXN0LWtleS1mb3ItcGhhc2U3LXRlc3RpbmctMTIzNDU2Nzg='
export JWT_SECRET='phase7-isolated-test-secret-do-not-use-outside-testing'
export DB_CONNECTION=sqlite
export DB_DATABASE="$database_path"
export DB_FOREIGN_KEYS=true
export CACHE_STORE=array
export SESSION_DRIVER=array
export QUEUE_CONNECTION=sync
export MAIL_MAILER=array

cd "$repo_root"
php artisan migrate --force
php artisan db:seed --class=Phase7E2ESeeder --force
PHP_CLI_SERVER_WORKERS=4 php artisan serve --host=127.0.0.1 --port="$api_port" --no-reload >"$test_root/laravel.log" 2>&1 &
api_pid=$!

cd "$repo_root/frontend"
export LARAVEL_API_ORIGIN="http://127.0.0.1:$api_port"
export NEXT_DIST_DIR="$next_dist_dir"
export PATH="$HOME/.nvm/versions/node/v24.14.1/bin:$PATH"
./node_modules/.bin/next dev --hostname 127.0.0.1 --port "$frontend_port" >"$test_root/next.log" 2>&1 &
frontend_pid=$!

ready=0
for attempt in $(seq 1 60); do
  if curl --silent --fail "http://127.0.0.1:$api_port/api/health" >/dev/null \
    && curl --silent --fail "http://127.0.0.1:$frontend_port/requests/new" >/dev/null; then
    ready=1
    break
  fi
  sleep 1
done
if [[ "$ready" != 1 ]]; then
  cat "$test_root/laravel.log" "$test_root/next.log"
  exit 1
fi

PLAYWRIGHT_BASE_URL="http://127.0.0.1:$frontend_port" \
PLAYWRIGHT_EXTERNAL_SERVERS=1 \
PHASE7_API_ORIGIN="http://127.0.0.1:$api_port" \
npx playwright test e2e/phase7-api-flow.spec.ts --workers=1 --reporter=line

echo "Banco isolado e logs temporários: $test_root"
