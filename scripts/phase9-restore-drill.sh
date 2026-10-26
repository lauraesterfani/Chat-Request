#!/usr/bin/env bash
set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
drill_root="$(mktemp -d /tmp/chat-request-phase9-restore.XXXXXX)"
source_database="$drill_root/source.sqlite"
restored_database="$drill_root/restored.sqlite"
started_at="$(date +%s)"

cleanup() {
  if [[ "$drill_root" == /tmp/chat-request-phase9-restore.* ]]; then rm -rf -- "$drill_root"; fi
}
trap cleanup EXIT INT TERM

touch "$source_database"
mkdir -p "$drill_root/source-storage" "$drill_root/restored-storage"
export APP_ENV=testing
export APP_KEY='base64:ZmFrZS1waGFzZTktcmVzdG9yZS1rZXktdGVzdC0xMjM0NTY3ODk='
export JWT_SECRET='phase9-isolated-restore-secret-do-not-use-outside-testing'
export DB_CONNECTION=sqlite
export DB_DATABASE="$source_database"
export DB_FOREIGN_KEYS=true
export CACHE_STORE=array
export SESSION_DRIVER=array
export QUEUE_CONNECTION=sync
export MAIL_MAILER=array
export LARAVEL_STORAGE_PATH="$drill_root/source-storage"

cd "$repo_root"
php artisan migrate --force
php artisan db:seed --class=Phase9RestoreFixtureSeeder --force
cp -- "$source_database" "$restored_database"
cp -a -- "$drill_root/source-storage/." "$drill_root/restored-storage/"

export DB_DATABASE="$restored_database"
export LARAVEL_STORAGE_PATH="$drill_root/restored-storage"
php scripts/verify-phase9-restore.php
finished_at="$(date +%s)"
echo "Duração observada do ensaio local: $((finished_at - started_at)) s. RPO/RTO institucionais continuam sem aprovação."
echo "Ensaio isolado concluído; nenhum serviço externo foi contatado."
