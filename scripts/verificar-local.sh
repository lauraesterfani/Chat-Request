#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

cd "$ROOT_DIR"
php artisan test

if ! command -v node >/dev/null 2>&1; then
  echo "Node Linux não encontrado no PATH. Instale Node 20+ no WSL antes de validar o front-end." >&2
  exit 2
fi

NODE_MAJOR="$(node --version | sed -E 's/^v([0-9]+).*/\1/')"
if [ "$NODE_MAJOR" -lt 20 ]; then
  echo "Node $(node --version) encontrado, mas o frontend usa Next.js 16 e requer Node 20.9+ no WSL." >&2
  echo "Atualize o Node antes de executar npm ci, lint ou build neste ambiente." >&2
  exit 2
fi

cd "$ROOT_DIR/frontend"
npm ci
npm run lint
npm run build
