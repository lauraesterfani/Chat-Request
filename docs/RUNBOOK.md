# Runbook de Operação – Chat Request

> Documento para operadores e desenvolvedores. Não inclui valores secretos ou dados de produção.

---

## Iniciar o sistema

### Modo desenvolvimento (todos os serviços juntos)

```bash
# Instala dependências PHP (uma vez)
composer install

# Instala dependências Node (uma vez)
cd frontend && npm install && cd ..

# Inicia backend + worker + logs + frontend em paralelo
composer run dev
```

O comando `composer run dev` inicia concorrentemente:
- **Backend Laravel** – `php artisan serve` → `http://localhost:8000`
- **Worker de filas** – `php artisan queue:listen --tries=1`
- **Log viewer** – `php artisan pail --timeout=0`
- **Frontend Next.js** – `npm run dev` → `http://localhost:3000`

### Serviços individualmente

```bash
# Backend (porta 8000)
php artisan serve

# Frontend (porta 3000)
cd frontend && npm run dev

# Worker de jobs / e-mails
php artisan queue:work

# Worker com tries e delay
php artisan queue:work --tries=3 --sleep=3 --timeout=90
```

---

## Variáveis de ambiente obrigatórias (`.env`)

Copie `.env.example` para `.env` e preencha os valores:

```bash
cp .env.example .env
php artisan key:generate
php artisan jwt:secret      # gera JWT_SECRET
```

| Variável | Descrição | Exemplo / Padrão |
|----------|-----------|-----------------|
| `APP_NAME` | Nome da aplicação | `Laravel` |
| `APP_ENV` | Ambiente (`local`, `production`) | `local` |
| `APP_KEY` | Chave de criptografia (gerada automaticamente) | _(gerada por `key:generate`)_ |
| `APP_DEBUG` | Habilita stack traces na resposta | `true` em dev, `false` em prod |
| `APP_URL` | URL base do backend | `http://localhost:8000` |
| `JWT_SECRET` | Chave secreta JWT (gerada por `jwt:secret`) | _(gerada)_ |
| `REQUEST_OVERDUE_DAYS` | Dias até considerar requerimento atrasado | `5` |
| `INSTITUTIONAL_IDENTITY_ENABLED` | Ativa IdP institucional (desabilitado até homologação) | `false` |
| `INSTITUTIONAL_IDENTITY_ISSUER` | Emissor do token institucional | _(vazio)_ |
| `INSTITUTIONAL_IDENTITY_BASE_URL` | URL base do IdP institucional | _(vazio)_ |
| `DB_CONNECTION` | Driver do banco (`sqlite`, `mysql`, `pgsql`) | `sqlite` |
| `DB_HOST` | Host do banco (MySQL/Postgres) | `127.0.0.1` |
| `DB_PORT` | Porta do banco | `3306` |
| `DB_DATABASE` | Nome do banco / caminho do arquivo SQLite | `chat_request` |
| `DB_USERNAME` | Usuário do banco | _(vazio no SQLite)_ |
| `DB_PASSWORD` | Senha do banco | _(vazio no SQLite)_ |
| `QUEUE_CONNECTION` | Driver de fila (`database`, `redis`, `sync`) | `database` |
| `CACHE_STORE` | Driver de cache | `database` |
| `SESSION_DRIVER` | Driver de sessão | `database` |
| `MAIL_MAILER` | Driver de e-mail (`log`, `smtp`) | `log` (dev) |
| `MAIL_HOST` | Host SMTP | `127.0.0.1` |
| `MAIL_PORT` | Porta SMTP | `2525` |
| `MAIL_USERNAME` | Usuário SMTP | _(vazio)_ |
| `MAIL_PASSWORD` | Senha SMTP | _(vazio)_ |
| `MAIL_FROM_ADDRESS` | Endereço de remetente | `hello@example.com` |
| `LOG_LEVEL` | Nível de log (`debug`, `info`, `warning`, `error`) | `debug` |
| `BCRYPT_ROUNDS` | Custo do hash bcrypt | `12` |

> **Em produção:** `APP_DEBUG=false`, `MAIL_MAILER=smtp` com SMTP real homologado, `QUEUE_CONNECTION=redis` recomendado.

---

## Execução de migrations

```bash
# Criar as tabelas no banco (primeira vez ou após pull)
php artisan migrate

# Ver status das migrations
php artisan migrate:status

# Rollback da última batch
php artisan migrate:rollback

# Recriar banco e rodar seeders (apenas desenvolvimento)
php artisan migrate:fresh --seed
```

> **Nunca rodar `migrate:fresh` em produção.** Use apenas `migrate`.

---

## Execução de testes

```bash
# Limpa config e executa toda a suíte PHPUnit
composer run test

# Equivalente manual
php artisan config:clear
php artisan test

# Testes de um arquivo específico
php artisan test tests/Feature/RequestTest.php

# Testes E2E do frontend (Playwright)
cd frontend
npm run qa:frontend              # headless
npm run qa:frontend:headed       # com browser visível
npm run qa:frontend:ui           # interface gráfica do Playwright
npm run qa:frontend:report       # abre relatório HTML

# Lint do frontend
npm run lint
```

---

## Checklist de saúde

Acesse `GET /api/health` sem autenticação:

```bash
curl http://localhost:8000/api/health
```

Resposta esperada (`200 OK`):
```json
{
  "status": "ok",
  "checks": {
    "database": true,
    "queue_jobs": { "pending": 0 }
  },
  "timestamp": "..."
}
```

Verificações adicionais:

```bash
# Status do banco
php artisan db:show

# Migrations pendentes
php artisan migrate:status | grep No

# Jobs presos na fila
php artisan queue:failed

# Logs de erro recentes
php artisan pail --filter="error"
# ou manualmente:
tail -n 100 storage/logs/laravel.log
```

---

## O que fazer se a fila travar

### Sintomas
- E-mails não são enviados
- Jobs acumulam na tabela `jobs`
- `GET /api/health` retorna `queue_jobs.pending` alto

### Diagnóstico

```bash
# Ver jobs pendentes
php artisan queue:monitor database

# Ver jobs com falha
php artisan queue:failed

# Inspecionar detalhes de um job falho
php artisan queue:failed --id=<ID>
```

### Resolução

```bash
# 1. Reiniciar o worker (encerra após jobs atuais)
php artisan queue:restart

# 2. Iniciar novo worker
php artisan queue:work --tries=3 --sleep=3

# 3. Recolocar jobs falhos na fila
php artisan queue:retry all

# 4. Se houver jobs irrecuperáveis, limpar falhas
php artisan queue:flush

# 5. Em último caso, limpar a fila inteira (cuidado!)
php artisan queue:clear database
```

---

## Procedimento de backup

### Banco SQLite (desenvolvimento)

```bash
# Copiar o arquivo de banco
cp database/database.sqlite database/database.sqlite.bak-$(date +%Y%m%d)
```

### Banco MySQL/Postgres (produção)

```bash
# MySQL
mysqldump -u <DB_USERNAME> -p <DB_DATABASE> > backup-$(date +%Y%m%d).sql

# Postgres
pg_dump -U <DB_USERNAME> <DB_DATABASE> > backup-$(date +%Y%m%d).sql
```

### Arquivos de documentos enviados (storage)

```bash
# Compactar o diretório de uploads
tar -czf storage-backup-$(date +%Y%m%d).tar.gz storage/app/
```

> Configure um cron ou ferramenta de CI/CD para executar backups automaticamente em produção. Os arquivos de backup nunca devem ficar no repositório git.

---

## Scripts disponíveis

### Backend (`composer run <script>`)

| Script | Comando executado | Descrição |
|--------|-------------------|-----------|
| `dev` | Todos em paralelo | Inicia backend + worker + logs + frontend |
| `test` | `config:clear` + `artisan test` | Executa suíte de testes PHP |

### Frontend (`npm run <script>`)

| Script | Descrição |
|--------|-----------|
| `dev` | Inicia servidor Next.js em modo desenvolvimento (porta 3000) |
| `build` | Compila o frontend para produção |
| `start` | Inicia o servidor de produção Next.js |
| `lint` | Roda ESLint no código do frontend |
| `qa:frontend` | Executa testes Playwright (headless) |
| `qa:frontend:headed` | Executa testes Playwright com browser visível |
| `qa:frontend:ui` | Abre a UI interativa do Playwright |
| `qa:frontend:report` | Abre o relatório HTML dos testes |

---

## Observações de segurança

- Nunca exponha o arquivo `.env` em repositórios públicos.
- Em produção, defina `APP_DEBUG=false` para não vazar stack traces.
- Rotacione `APP_KEY` e `JWT_SECRET` apenas se comprometidos; isso invalida sessões ativas.
- O canal de e-mail permanece em modo `log` até que o SMTP institucional seja contratado e homologado.
- A integração com IdP institucional (`INSTITUTIONAL_IDENTITY_ENABLED`) está desabilitada por padrão aguardando contrato com o IFPE.
