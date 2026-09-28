# Guia de operação técnica

Este guia é para a equipe responsável pelo ambiente. Ele não autoriza implantação em produção nem alteração de credenciais, DNS, permissões remotas ou integrações externas.

## Verificação inicial

1. Identifique o commit e o ambiente antes de qualquer operação.
2. Consulte `GET /api/health`; a resposta esperada é somente `{"status":"ok"}`.
3. Execute `php artisan migrate:status`, `php artisan test`, TypeScript, build e os testes E2E disponíveis no ambiente compatível.
4. Não use `migrate:fresh`, limpeza de banco ou comandos destrutivos em ambiente desconhecido.

## Worker, fila e e-mail

1. Em homologação, use captura de e-mail ou transporte local; nunca destinatários reais sem autorização.
2. Para fila de banco, execute worker compatível com o ambiente e monitore `jobs` e `failed_jobs`.
3. Falha de e-mail não pode desfazer requerimento, mensagem ou decisão. Reprocessamento deve ser autorizado e idempotente.

## Armazenamento e incidentes

1. Mantenha anexos em disco privado e teste autorização antes de disponibilizar download.
2. Monitore espaço, permissões e falhas de armazenamento sem registrar conteúdo de documentos.
3. Em incidente, preserve evidências técnicas mínimas, revogue acesso quando aplicável e encaminhe comunicação/retencão ao responsável institucional.

## Integrações e configuração

- `LARAVEL_API_ORIGIN` configura o proxy interno do Next por ambiente; não deve conter token.
- A identidade institucional permanece desativada até existir adaptador, escopos, contrato e homologação. Consulte [Integração de identidade institucional](INTEGRACAO_IDENTIDADE_INSTITUCIONAL.md).
- Faça backup e ensaio de restauração somente em ambiente isolado e autorizado. Registre RPO/RTO observados, sem convertê-los em garantia.

Consulte o [runbook de operação local e homologação](RUNBOOK_OPERACAO_LOCAL.md) para incidentes e retorno.
