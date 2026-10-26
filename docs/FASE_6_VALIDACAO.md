# Fase 6 — notificações: auditoria de conclusão

**Estado: parcial.** A central interna e preferências existem, mas a fase não atende a todos os requisitos do prompt 6. O acesso às notificações vinculadas a requerimentos é revalidado; ainda não há fluxo para confirmar endereços de e-mail, cobertura integral de eventos nem homologação do transporte institucional.

| Requisito | Evidência atual | Estado / lacuna |
|---|---|---|
| Lista paginada, filtros, contador, leitura individual e preferências | `NotificationController`, `NotificationTest` | Implementado; falta conferir concorrência de “marcar todas” e limite estável da seleção |
| Idempotência de notificações internas | `NotificationService`, índice único em `notification_records.idempotency_key` | Implementado no serviço; falta teste de concorrência/rollback dos eventos emissores |
| Envio em fila após commit e falha sem desfazer a operação | `SendNotificationEmail`, `NotificationService` | Parcial: dispatch após commit e teste com Queue fake; retries, falha SMTP e recuperação operacional precisam de testes explícitos |
| E-mail somente para destinatário verificado | Migration de verificação de staff, preferência e job | Corrigido nesta revisão: preferência não pode ser ativada sem verificação e job revalida verificação, estado ativo de staff e preferência. Teste `NotificationTest` passou (5 testes, 27 assertions) |
| Fluxo para verificar endereço | Não existe endpoint/tela verificada no projeto | Ausente. A proteção atual impede envio, mas ainda não há caminho para o usuário confirmar o endereço |
| Revalidar acesso ao pedido antes do envio e da leitura | `NotificationController` aplica `RequestAccessService` à lista, contagem, leitura e “marcar todas”; `SendNotificationEmail` revalida o acesso imediatamente antes do envio | Implementado para notificações vinculadas a requerimentos. Se o escopo da equipe for revogado, o conteúdo deixa de aparecer e qualquer e-mail pendente é ignorado; coberto por `NotificationTest`. Notificações sem requerimento continuam independentes do escopo acadêmico |
| Matriz evento/destinatário/canal e marco de ativação | Chamadas existentes em controllers | Parcial: não há catálogo central completo, destinatários elegíveis por setor nem controle de ativação para prevenir disparo retroativo em todos os eventos |
| E-mail transacional seguro | `Mail::raw` em `SendNotificationEmail` | Parcial: não inclui anexos/JWT; ainda precisa de URL-base autenticada configurada no servidor, template discreto e evidência de escape/texto simples |
| UI da central em desktop/celular, polling e pausa em aba inativa | `frontend/e2e/notifications-satisfaction.spec.ts` e QA visual independente | Convite, filtro, layout, Axe, console e rede passaram em desktop/celular; esse E2E usa API simulada. Falta teste de polling com aba inativa e um fluxo integrado de duas sessões com API real |

## Mudanças nesta revisão

- O endereço do aluno ou da equipe precisa estar confirmado antes de ativar preferência de e-mail.
- O job verifica novamente a confirmação, a preferência atual e, para equipe, se a conta continua ativa; jobs obsoletos são marcados como ignorados.
- Lista, contagem e leitura agora ocultam notificações vinculadas ao requerimento quando o usuário de equipe deixa de ter acesso; jobs de e-mail também verificam a autorização atual antes de enviar.
- Foi acrescentado `email_verified_at` para contas de equipe, preservando registros existentes como não verificados.
- Não foi implementado o envio do link/código de confirmação. Até isso existir, e-mail é deliberadamente indisponível para essas contas; notificações internas continuam funcionando.

## Verificação executada

- `php artisan test --filter=NotificationTest`: **6 passaram, 30 assertions**.
- `vendor/bin/pint` nos arquivos PHP alterados: aplicado.
- `php artisan test`: **173 passaram, 1.244 assertions** na rodada completa de 26/10/2026.
- `git diff --check`: passou.

## Bloqueios restantes

1. Criar e testar confirmação de e-mail com token de uso único, validade curta, rate limit e respostas que não revelem existência de conta.
2. Ampliar cobertura de revogação para alterações de curso/unidade e medir a concorrência entre “marcar todas” e novas notificações.
3. Cobrir todos os eventos requeridos, preferências, concorrência, retries e falhas usando mail catcher isolado.
4. Rodar cenário integrado E2E CRADT/aluno em sessões separadas e validar desktop/celular.
5. Homologar remetente, URL-base, política de canais e SMTP com a instituição; nenhum e-mail real foi enviado nesta revisão.
