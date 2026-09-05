# Chat Request

Aplicação acadêmica para abertura e acompanhamento de requerimentos do IFPE.

## Tecnologias

- Back-end: Laravel 12, PHP 8.2+, JWT e Eloquent.
- Front-end: Next.js 16, React 19, TypeScript e Tailwind CSS.
- Banco: SQLite para desenvolvimento e MySQL para ambientes compartilhados.

## Instalação

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
php artisan migrate --seed
php artisan serve
```

Em outro terminal: `cd frontend && npm install && npm run dev`. Para SQLite, crie `database/database.sqlite` e use `DB_CONNECTION=sqlite`; configure `DB_*` para MySQL. Nunca use `migrate:fresh` em banco desconhecido.

## Testes e qualidade

`php artisan test`, `vendor/bin/pint --test`, `cd frontend && npm run lint`, `npx tsc --noEmit` e `npm run build`.

## Perfis e estrutura

`student` acessa seus requerimentos; `coordenacao` acessa o curso autorizado; `cradt` e `admin` atendem os requerimentos permitidos. `staff` é técnico e não recebe acesso acadêmico automaticamente. Seeders usam dados fictícios em domínio `.test`.

Código principal: `app/Models`, `app/Http/Controllers`, `app/Policies`, `database`, `routes` e `frontend/app`. O proxy `/api` e `/storage` está em `frontend/next.config.mjs`.

## Chat e histórico

Cada requerimento possui histórico textual paginado em `GET /api/requests/{id}/messages`, envio em `POST /api/requests/{id}/messages` e marcação individual em `POST /api/requests/{id}/messages/read`. O limite é 2.000 caracteres; o remetente vem exclusivamente do JWT. O frontend atualiza por polling de 5 segundos enquanto a aba está visível. Requerimentos concluídos ou cancelados ficam somente para leitura. WebSockets, anexos no chat e notificações externas permanecem fora desta fase.

Regras institucionais ainda precisam de validação formal do IFPE. Consulte [ROADMAP_LANCAMENTO.md](ROADMAP_LANCAMENTO.md).

## Fluxo e linha do tempo

Requerimentos agora possuem setor responsável, resultado opcional e eventos persistentes. `GET /api/requests/{id}/events` exibe a linha do tempo; `POST /api/requests/{id}/forward` encaminha para setor autorizado com justificativa; `POST /api/requests/{id}/decision` registra decisão final, justificativa e resumo. Esta trilha registra o atendimento e não substitui auditoria institucional.

## Fase 5 — Prazos e filas (núcleo)

Políticas de SLA começam como rascunho e só podem ser ativadas por administração autorizada. Requerimentos novos preservam a versão da política ativa (quando houver), com metas em minutos corridos e indicadores server-side. A fila administrativa está disponível em `GET /api/admin/queue`; atribuição manual em `POST /api/requests/{id}/assign`; políticas em `GET/POST /api/sla-policies` e ativação em `POST /api/sla-policies/{id}/activate`. O prazo de 90 dias do formulário institucional não é usado como SLA. Calendários de expediente, pausas configuráveis, distribuição automática e notificações permanecem pendentes de homologação.

## Fase 6 — Notificações (núcleo implementado)

Notificações internas idempotentes são criadas para mensagens públicas da equipe e mudanças de status. A central usa `GET /api/notifications`, contador em `GET /api/notifications/count`, leitura individual em `POST /api/notifications/{id}/read`, leitura em lote em `POST /api/notifications/read-all` e preferências em `GET/PUT /api/notification-preferences`. O componente de sino faz polling leve e respeita o destinatário autenticado. E-mail é enfileirado apenas quando o usuário habilita a categoria; o transporte local/teste deve ser usado até haver SMTP homologado. WhatsApp, SMS, push e campanhas estão fora do escopo.
