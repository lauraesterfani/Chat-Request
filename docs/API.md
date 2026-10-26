# Documentação da API – Chat Request

> Base URL: `http://localhost:8000/api`  
> Autenticação: JWT via header `Authorization: Bearer <token>`  
> Formato de resposta: `application/json`

---

## Rotas Públicas

Não exigem autenticação.

| Método | Rota | Auth | Role | Descrição |
|--------|------|------|------|-----------|
| GET | `/` | ✗ | — | Status geral da API |
| GET | `/health` | ✗ | — | Sinal público simples de disponibilidade |
| GET | `/health/detailed` | ✓ | staff (TI) | Verifica banco e quantidade de jobs pendentes |
| POST | `/login` | ✗ | — | Login do aluno (throttle: 5 req/min) |
| POST | `/login/staff` | ✗ | — | Login de staff/admin |
| POST | `/validate-token` | ✗ | — | Valida token de matrícula |
| GET | `/courses` | ✗ | — | Lista cursos disponíveis |
| GET | `/type-requests` | ✗ | — | Lista tipos de requerimento |
| GET | `/service-catalog` | ✗ | — | Catálogo de serviços publicados |
| GET | `/knowledge-articles` | ✗ | — | Artigos da base de conhecimento |
| GET | `/privacy-notice/current` | ✗ | — | Aviso aprovado mais recente ou estado `pending_approval` |
| GET | `/type-requests/{typeId}/form` | ✗ | — | Formulário publicado de um tipo de requerimento |

---

## Rotas Autenticadas – Aluno (`auth:api`)

Exigem token JWT de aluno + senha já alterada (`password.changed`).

| Método | Rota | Auth | Role | Descrição |
|--------|------|------|------|-----------|
| GET | `/me` | ✓ | any | Retorna dados do usuário autenticado |
| POST | `/logout` | ✓ | any | Invalida o token atual |
| POST | `/refresh` | ✓ | any | Renova o token JWT |
| POST | `/auth/change-initial-password` | ✓ | aluno | Altera senha no primeiro acesso |
| GET | `/requests` | ✓ | aluno | Lista requerimentos do aluno |
| GET | `/my-requests` | ✓ | aluno | Alias de `/requests` |
| POST | `/requests` | ✓ | aluno | Cria novo requerimento |
| POST | `/requests/duplicates/check` | ✓ | aluno | Busca pedidos abertos semelhantes do próprio aluno antes do envio |
| GET | `/knowledge-articles/authenticated` | ✓ | aluno | Orientações publicadas para usuários autenticados; nunca inclui conteúdo interno |
| GET | `/privacy/data-requests` | ✓ | aluno | Lista as próprias solicitações de privacidade |
| POST | `/privacy/data-requests` | ✓ | aluno | Registra solicitação autenticada de acesso, correção ou avaliação de eliminação |
| GET | `/privacy/data-requests/{id}` | ✓ | aluno | Consulta somente solicitação própria |
| POST | `/documents/upload` | ✓ | aluno | Faz upload de documento vinculado a requerimento |
| GET | `/drafts` | ✓ | aluno | Lista rascunhos do aluno |
| POST | `/drafts` | ✓ | aluno | Salva novo rascunho |
| PUT | `/drafts/{draft}` | ✓ | aluno | Atualiza rascunho existente |
| DELETE | `/drafts/{draft}` | ✓ | aluno | Descarta rascunho |
| GET | `/drafts/{draft}/migration` | ✓ | aluno | Prévia de migração para a versão publicada atual |
| POST | `/drafts/{draft}/migration` | ✓ | aluno | Confirma a migração revisada do rascunho |
| GET | `/enrollments/active` | ✓ | aluno | Lista vínculos ativos do solicitante |
| POST | `/change-enrollment/{id}` | ✓ | aluno | Altera matrícula ativa |
| GET | `/staffs` | ✓ | aluno | Lista servidores (atendentes) |
| GET | `/requests/{id}` | ✓ | any | Detalhe de um requerimento |
| GET | `/requests/{id}/events` | ✓ | any | Linha do tempo / eventos do requerimento |
| GET | `/requests/{requestId}/messages` | ✓ | any | Lista mensagens do chat de um requerimento |
| POST | `/requests/{requestId}/messages` | ✓ | any | Envia mensagem no chat |
| POST | `/requests/{requestId}/messages/read` | ✓ | any | Marca mensagens como lidas |
| GET | `/requests/{id}/satisfaction` | ✓ | aluno | Consulta avaliação de satisfação enviada |
| POST | `/requests/{id}/satisfaction` | ✓ | aluno | Registra avaliação de satisfação |
| GET | `/notifications` | ✓ | any | Lista notificações do usuário |
| GET | `/notifications/count` | ✓ | any | Contador de notificações não lidas |
| POST | `/notifications/{id}/read` | ✓ | any | Marca notificação como lida |
| POST | `/notifications/read-all` | ✓ | any | Marca todas as notificações como lidas |
| GET | `/notification-preferences` | ✓ | any | Consulta preferências de notificação |
| PUT | `/notification-preferences` | ✓ | any | Atualiza preferências de notificação |

---

## Rotas Staff / Admin

### Funções comuns (admin, staff, cradt)

| Método | Rota | Auth | Role | Descrição |
|--------|------|------|------|-----------|
| GET | `/response-templates` | ✓ | admin,staff,cradt | Lista templates de resposta |
| GET | `/response-templates/active` | ✓ | admin,staff,cradt | Lista templates ativos |
| GET | `/response-templates/{id}` | ✓ | admin,staff,cradt | Detalhe de um template |
| POST | `/response-templates` | ✓ | admin,staff,cradt | Cria template de resposta |
| PUT | `/response-templates/{id}` | ✓ | admin,staff,cradt | Atualiza template |
| PATCH | `/response-templates/{id}/status` | ✓ | admin,staff,cradt | Altera status do template |
| GET | `/admin/requests` | ✓ | admin,cradt | Lista requerimentos (visão admin) |
| GET | `/admin/type-requests` | ✓ | admin,cradt | Lista tipos e configurações administrativas de duplicidade |

### Exclusivas Admin / CRADT (`role:admin,cradt`)

| Método | Rota | Auth | Role | Descrição |
|--------|------|------|------|-----------|
| GET | `/metrics` | ✓ | admin,cradt | Indicadores e métricas agregadas |
| PUT | `/requests/{id}` | ✓ | admin,cradt | Atualiza requerimento |
| POST | `/requests/{id}/forward` | ✓ | admin,cradt | Encaminha requerimento para outro setor |
| POST | `/requests/{id}/return` | ✓ | admin,cradt | Devolve requerimento à etapa anterior |
| POST | `/requests/{id}/decision` | ✓ | admin,cradt | Registra decisão final |
| POST | `/type-requests` | ✓ | admin,cradt | Cria tipo de serviço e regra opcional de duplicidade |
| PUT | `/type-requests/{id}` | ✓ | admin,cradt | Atualiza tipo de serviço e regra de duplicidade |
| DELETE | `/type-requests/{id}` | ✓ | admin,cradt | Exclui tipo sem requerimentos associados |
| POST | `/requests/{id}/assign` | ✓ | admin,cradt | Atribui requerimento a atendente |
| GET | `/admin/queue` | ✓ | admin,cradt | Fila de atendimento |
| GET | `/sla-policies` | ✓ | admin,cradt | Lista políticas de SLA |
| POST | `/sla-policies` | ✓ | admin,cradt | Cria política de SLA |
| POST | `/sla-policies/{id}/activate` | ✓ | admin,cradt | Ativa política de SLA |
| POST | `/metrics/exports/{format}` | ✓ | admin,cradt com `view_request` | Gera relatório sintético XLSX ou PDF no storage privado |
| GET | `/metrics/exports/{id}/download` | ✓ | mesmo autor, escopo vigente | Revalida acesso e gera novamente o relatório antes do download |
| GET | `/admin/knowledge-articles` | ✓ | admin,cradt | Lista artigos, versões de trabalho e estados editoriais |
| GET | `/staff-access-scopes` | ✓ | admin,cradt | Lista escopos de acesso |
| POST | `/staff-access-scopes` | ✓ | admin,cradt | Cria escopo de acesso |
| DELETE | `/staff-access-scopes/{id}` | ✓ | admin,cradt | Remove escopo de acesso |
| POST | `/type-requests/{typeId}/form-versions` | ✓ | admin,cradt | Cria nova versão de formulário |
| GET | `/type-requests/{typeId}/form-versions` | ✓ | admin,cradt | Lista versões para comparação e edição administrativa |
| PUT | `/form-versions/{id}` | ✓ | admin,cradt | Edita somente versão em rascunho |
| POST | `/form-versions/{id}/publish` | ✓ | admin,cradt | Publica versão de formulário |
| GET | `/admin/service-catalog` | ✓ | admin,cradt | Catálogo administrativo |
| POST | `/admin/service-catalog` | ✓ | admin,cradt | Cria entrada no catálogo |
| PUT | `/admin/service-catalog/{id}` | ✓ | admin,cradt | Atualiza entrada no catálogo |
| GET | `/admin/service-opening-windows` | ✓ | admin,cradt | Lista janelas de abertura |
| POST | `/admin/service-opening-windows` | ✓ | admin,cradt | Cria janela de abertura |
| PUT | `/admin/service-opening-windows/{id}` | ✓ | admin,cradt | Atualiza janela de abertura |
| POST | `/admin/knowledge-articles` | ✓ | admin,cradt | Cria artigo na base de conhecimento |
| PUT | `/admin/knowledge-articles/{id}` | ✓ | admin,cradt | Salva rascunho, envia para revisão, publica (admin global) ou desativa |
| GET | `/admin/privacy/data-requests` | ✓ | admin global sem escopos | Fila de solicitações de titulares |
| GET | `/admin/privacy/data-requests/{id}` | ✓ | admin global sem escopos | Consulta uma solicitação para análise humana |
| PATCH | `/admin/privacy/data-requests/{id}` | ✓ | admin global sem escopos | Move para análise, responde ou registra rejeição fundamentada |
| GET/POST | `/admin/privacy-notices` | ✓ | admin global sem escopos | Lista/cria versões de rascunho do aviso |
| PUT | `/admin/privacy-notices/{id}` | ✓ | admin global sem escopos | Edita somente rascunho |
| POST | `/admin/privacy-notices/{id}/submit-review` | ✓ | admin global sem escopos | Envia versão para revisão |
| POST | `/admin/privacy-notices/{id}/return-to-draft` | ✓ | admin global sem escopos | Devolve versão em revisão para edição |
| POST | `/admin/privacy-notices/{id}/publish` | ✓ | admin global sem escopos | Publica após campos e referências institucionais obrigatórias |
| POST | `/admin/privacy-notices/{id}/new-version` | ✓ | admin global sem escopos | Cria novo rascunho sem alterar a versão publicada |
| GET | `/audit-records` | ✓ | admin global ativo sem escopos | Busca paginada e auditada; demais contas de equipe recebem 403 e têm tentativa registrada |
| POST | `/admin/retention/simulate` | ✓ | admin global | Estima registros anteriores a um corte temporal e retorna referências pseudonimizadas; não executa descarte nem representa política aprovada |
| GET | `/dashboard/requerimentos` | ✓ | admin,cradt | Dashboard – requerimentos |
| GET | `/dashboard/estatisticas` | ✓ | admin,cradt | Dashboard – estatísticas gerais |
| GET | `/dashboard/graficos/status` | ✓ | admin,cradt | Gráfico – requerimentos por status |
| GET | `/dashboard/graficos/cursos` | ✓ | admin,cradt | Gráfico – requerimentos por curso |

### Exclusivas T.I. (`role:staff`)

| Método | Rota | Auth | Role | Descrição |
|--------|------|------|------|-----------|
| GET | `/staff-admins` | ✓ | staff | Lista usuários staff-admin |
| POST | `/staff-admins` | ✓ | staff | Cria usuário staff-admin |
| DELETE | `/staff-admins/{id}` | ✓ | staff | Remove usuário staff-admin |
| POST | `/staffs` | ✓ | staff | Cadastra atendente |
| DELETE | `/staffs/{id}` | ✓ | staff | Remove atendente |
| POST | `/reset-password` | ✓ | staff_admins | Troca de senha no primeiro acesso; exige senha temporária atual e revoga o token usado |
| GET | `/admins` | ✓ | staff_admins | Lista admins |

Todas as respostas da API incluem `X-Request-ID`, gerado pelo servidor. Eventos de auditoria guardam o mesmo identificador e `outcome` (`success`, `failure`, `denied` ou `unknown`); o cliente não pode escolher esse ID. A trilha rejeita `UPDATE` e `DELETE` via modelo e triggers no SQLite/MySQL. Isso protege contra operações normais da aplicação, não contra administrador de banco capaz de remover triggers ou restaurar outra cópia; não é armazenamento WORM.

Os logins de aluno e equipe limitam falhas por conta (cinco por minuto, chave HMAC); não aplicam bloqueio agregado por IP, para evitar bloquear um campus inteiro atrás de NAT. O endpoint de primeiro acesso da equipe `/reset-password` requer token administrativo, troca pendente, senha temporária atual e uma nova senha forte; após salvar, invalida o token anterior.

Leituras da fila/detalhe de solicitações de privacidade, tentativas negadas e simulações de retenção são auditadas com metadados mínimos; descrição e resposta privada não são copiadas para a trilha. O upload aceita PDF/JPEG/PNG coerentes entre MIME e extensão até 10 MiB, e os detalhes servem arquivos somente do disco privado. `php artisan documents:migrate-to-private` apenas simula por padrão; `--apply` copia arquivos legados, confere SHA-256 e apaga a cópia pública. Faça backup e valide storage autorizado antes de executar a opção.

---

## Exemplos de Request / Response

### `POST /api/requests`

Cria um novo requerimento. Requer token de aluno com senha já alterada. A regra padrão é avisar sobre outro pedido aberto do mesmo aluno, serviço, matrícula e assunto normalizado. `duplicate_window_days` opcional limita a busca; quando ausente, considera todos os pedidos ainda abertos. O modo `warn` exige confirmação explícita e permite continuar; `block` só pode ser ativado com referência de regra homologada por administrador institucional sem escopo restrito. Um `409 Conflict` contém somente IDs, protocolos e resumos dos pedidos próprios correspondentes. `Idempotency-Key` trata retries técnicos separadamente: a chave é única por aluno na operação de criação e protegida por índice composto no banco; uma repetição com a mesma carga retorna o pedido existente e uma carga diferente recebe conflito.

**Request**
```http
POST /api/requests
Authorization: Bearer <jwt_token>
Content-Type: application/json

{
  "type_id": "8f7f2fbd-ec5d-4ae7-b075-98e11dd22000",
  "subject": "Aproveitamento de disciplina",
  "description": "Necessito de aproveitamento de disciplina cursada em outra instituição.",
  "enrollment_id": "e7b32779-ea30-4940-bac3-8c5bba336ea0",
  "document_ids": ["2d970bfc-7f31-4c16-9dd9-9df177d6996e"]
}
```

**Response `201 Created`**
```json
{
  "message": "Sucesso",
  "id": "4d7feb9a-1aa3-4810-949f-01649ba847bb"
}
```

`form_schema_version_id` and `form_responses` are required when the service has a published schema. Schemas are read at `GET /api/type-requests/{typeId}/form`; only an authorized editor can create, update, list or publish versions through `/api/type-requests/{typeId}/form-versions` and `/api/form-versions/{id}`. Published versions are immutable. Draft requests use `POST/GET /api/drafts`, `PUT/DELETE /api/drafts/{id}` and optimistic `revision` checks; migration is a separate preview-and-confirm operation. Drafts do not receive a protocol or enter the service queue.

`POST /api/requests/duplicates/check` accepts `type_id`, `subject` and optional `enrollment_id`; it returns `mode` and up to five matching requests owned by the authenticated student. The endpoint does not reveal another student's requests. The final `POST /api/requests` repeats the check transactionally and accepts `duplicate_acknowledged: true` only to continue in warning mode.

---

### Transições e decisões de requerimentos

`PUT /api/requests/{id}` aceita somente transições operacionais válidas. `pending` pode ir para `analyzing`; `analyzing` pode ir para `waiting` ou `solving`; `waiting` pode voltar para `analyzing` ou seguir para `solving`; `solving` pode ir para `waiting`. Estados abertos podem ir para `canceled` somente com motivo em `observation`. `waiting` exige uma orientação visível ao aluno. `completed` e `canceled` são estados finais nesta versão; resultado final deve ser gravado em `POST /api/requests/{id}/decision`.

A decisão exige `result`, `justification` e `conclusion_summary`. Um deferimento é bloqueado quando o serviço exige documento e nenhum documento está vinculado. `result: "na"` só é permitido para serviços configurados com `requires_decision: false`; esse campo é `true` por padrão. A resposta final é registrada como mensagem no histórico do atendimento.

### `GET /api/notifications`

Lista notificações do usuário autenticado (aluno ou staff).

**Request**
```http
GET /api/notifications?page=1&per_page=20
Authorization: Bearer <jwt_token>
```

**Response `200 OK`**
```json
{
  "data": [
    {
      "id": 55,
      "type": "status_changed",
      "message": "Seu requerimento REQ-2026-00128 foi encaminhado para análise.",
      "read_at": null,
      "created_at": "2026-09-28T18:30:00-03:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 1
  }
}
```

---

### `POST /api/requests/{id}/satisfaction`

Registra avaliação de satisfação após resolução. Apenas aluno, uma vez por requerimento.

**Request**
```http
POST /api/requests/128/satisfaction
Authorization: Bearer <jwt_token>
Content-Type: application/json

{
  "score": 5,
  "comment": "Atendimento rápido e eficiente."
}
```

**Response `201 Created`**
```json
{
  "id": 11,
  "request_id": 128,
  "score": 5,
  "comment": "Atendimento rápido e eficiente.",
  "created_at": "2026-09-28T22:10:00-03:00"
}
```

---

### `GET /api/metrics`

Retorna indicadores agregados. Requer role `admin` ou `cradt`.

**Request**
```http
GET /api/metrics?from=2026-09-01&to=2026-09-28&scope=campus
Authorization: Bearer <jwt_token_staff>
```

**Response `200 OK`**
```json
{
  "total_requests": 340,
  "resolved": 295,
  "pending": 30,
  "overdue": 15,
  "avg_resolution_days": 3.2,
  "satisfaction_avg": 4.6,
  "by_status": {
    "pending": 30,
    "in_progress": 15,
    "resolved": 295
  }
}
```

---

## Health Check

**`GET /api/health`** — sem autenticação; retorna somente `{"status":"ok"}`.

**`GET /api/health/detailed`** — exige autenticação de staff da TI e senha inicial já alterada.

**Response `200 OK`** (banco acessível)
```json
{
  "status": "ok",
  "checks": {
    "database": true,
    "queue_jobs": { "pending": 0 }
  },
  "timestamp": "2026-09-28T22:14:28-03:00"
}
```

**Response `503 Service Unavailable`** (banco inacessível)
```json
{
  "status": "degraded",
  "checks": {
    "database": false,
    "queue_jobs": { "pending": 0 }
  },
  "timestamp": "2026-09-28T22:14:28-03:00"
}
```
# Vínculo acadêmico no requerimento

O servidor registra `course_id` e, quando há uma única matrícula ativa compatível, `enrollment_id` ao criar um rascunho. Esses valores não são aceitos do cliente e passam ao requerimento no envio. O curso registrado, não o curso atual do perfil, define o escopo da coordenação para pedidos novos. Em pedidos legados com `course_id` nulo, o acesso continua usando o curso atual do aluno até revisão histórica; esse dado antigo não deve ser tratado como prova do curso no momento do envio.

## Revisão de rascunho após publicação de formulário

`GET /api/drafts/{id}/migration` mostra a versão publicada atual, respostas que podem ser preservadas, respostas incompatíveis que serão removidas e campos obrigatórios ainda vazios. Só o dono do rascunho pode consultar a prévia. `POST /api/drafts/{id}/migration` com `{ "revision": 3 }` confirma a mudança, recalcula a proposta no servidor e incrementa a revisão em uma transação. Revisão desatualizada recebe `409`; rascunhos descartados ou enviados não podem ser migrados. A API não troca a versão implicitamente em `PUT /api/drafts/{id}`. Após a migração, o aluno completa os novos campos e revisa o requerimento antes de enviá-lo.

## Anexos em rascunhos

O aluno envia cada arquivo com `POST /api/documents/upload` (`arquivo`, multipart). A resposta inclui `id`, nome original e metadados; o arquivo fica no disco privado. `POST/PUT /api/drafts` aceita a lista `document_ids` do próprio aluno e devolve `attached_documents` para a retomada do rascunho. `GET /api/drafts/{id}/documents/{document}` entrega o arquivo apenas ao dono enquanto o rascunho estiver aberto. O envio final de `POST /api/requests` exige que a lista `document_ids` coincida com a lista salva no rascunho; a criação vincula esses documentos ao pedido na mesma transação. Um documento cujo arquivo físico sumiu é rejeitado. Remover um anexo do rascunho remove a referência, não apaga o arquivo privado imediatamente. A limpeza manual conservadora está descrita em `docs/DOCUMENT_CLEANUP.md`; o prazo institucional de retenção ainda precisa ser definido.

## Matrícula acadêmica do requerimento

`GET /api/enrollments/active` lista apenas as matrículas ativas do aluno autenticado e indica `requires_selection`. Quando há mais de uma, `POST /api/drafts` e o envio direto em `POST /api/requests` exigem `enrollment_id`. O rascunho preserva o vínculo; `PUT /api/drafts/{id}` não permite trocar uma matrícula já escolhida. No envio com rascunho, `enrollment_id`, quando fornecido, deve coincidir com o snapshot salvo. Um rascunho legado ambíguo precisa receber a seleção antes do envio.
