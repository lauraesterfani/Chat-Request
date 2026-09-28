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

## Acesso de alunos

As contas de alunos são criadas ou importadas pela instituição; não há autocadastro público. O aluno entra em `POST /api/login` com `matricula` e `password`. A matrícula é normalizada para maiúsculas e sem espaços e segue o formato `AAAA` + período + curso + `IG` + número (por exemplo, `20241TSIIG0249`).

Contas com `must_change_password=true` recebem um JWT limitado: podem consultar o próprio estado, trocar a senha em `POST /api/auth/change-initial-password` e encerrar sessão, mas não acessam requerimentos, documentos ou chat. A troca exige `current_password`, `new_password` e `new_password_confirmation`; a nova senha requer oito caracteres, maiúscula, minúscula, número e símbolo. Após a troca, a API devolve um token renovado. Execute `php artisan migrate` para adicionar este controle a um ambiente existente.

## Testes e qualidade

`php artisan test`, `vendor/bin/pint --test`, `cd frontend && npm run lint`, `npx tsc --noEmit` e `npm run build`.

O workflow versionado em `.github/workflows/ci.yml` executa testes PHP isolados, Pint, TypeScript e build com Node 20 em pushes e pull requests. Pint e o lint completo estão registrados como baselines não bloqueantes até que os erros históricos sejam removidos; não devem ser interpretados como aprovação de formatação ou lint.

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

## Fase 11 — Catálogo e orientações públicas

A página pública [`/servicos`](frontend/app/servicos/page.tsx) consulta `GET /api/service-catalog` e `GET /api/knowledge-articles`. Ela mostra somente conteúdos publicados pelo back-end, permite busca textual e filtro por categoria, diferencia atendimento digital de orientação para canal externo e preserva estados explícitos de carregamento, erro e lista vazia. As informações são conteúdo editorial sujeito à revisão/homologação institucional; a página não promete protocolo, prazo ou regra acadêmica que não tenham sido publicados e aprovados.

Os fluxos público, catálogo e área autenticada receberam atalho de teclado para conteúdo principal, foco visível, preferência por movimento reduzido e correção de reflow da página inicial em telas de 320 px. Essa cobertura é uma melhoria técnica localizada e não certifica conformidade WCAG institucional completa.
