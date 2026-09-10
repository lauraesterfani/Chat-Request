# Runbook de operação local e homologação

Este roteiro serve para desenvolvimento e homologação com dados fictícios. Ele não autoriza implantação, envio de e-mail real, alteração de DNS, migração em produção ou acesso a integrações institucionais.

## Verificação de saúde

1. Confirme que o back-end responde em `GET /api/` e que o front abre sem erro de build.
2. Execute `php artisan test` e registre a versão do commit validado.
3. Confirme migrations pendentes com `php artisan migrate:status`. Em ambiente compartilhado, revise o alvo e faça backup aprovado antes de executar `php artisan migrate`.
4. Confira os logs sem copiar tokens, CPF, documentos ou conteúdo de mensagens para relatórios.

## Fila e e-mail

1. Em homologação, use transporte de e-mail capturado/local; nunca informe destinatários reais apenas para testar.
2. Para a fila `database`, confirme que os workers usam a mesma conexão do ambiente: `php artisan queue:work --tries=3 --backoff=30`.
3. Verifique pendências na tabela `jobs` e falhas em `failed_jobs`; uma falha de e-mail não deve desfazer um requerimento, mensagem ou decisão.
4. Antes de reiniciar um worker, espere o job atual quando possível. Reprocessamentos precisam ser autorizados e não podem repetir o efeito acadêmico.

## Armazenamento privado

1. Confirme que anexos não são expostos por URL pública sem autorização.
2. Monitore espaço disponível, erros de gravação e permissões do diretório/disco configurado.
3. Em incidente de armazenamento, interrompa uploads, preserve evidências técnicas sem conteúdo acadêmico e comunique o responsável definido pela instituição.

## Incidente de acesso ou dados

1. Revogue a sessão/escopo afetado conforme a capacidade do ambiente e preserve registros de auditoria.
2. Não altere nem apague eventos de atendimento para "corrigir" o incidente.
3. Registre horário, sistema, impacto e ações técnicas sem reproduzir dados pessoais no chamado.
4. Encaminhe avaliação de comunicação, retenção e obrigações legais ao responsável institucional; este projeto não define essas decisões.

## Recuperação e retorno

1. Ensaios de restauração devem ocorrer somente em ambiente isolado, com dados fictícios ou dados autorizados.
2. Valide banco, arquivos privados, migrations e permissões após restaurar.
3. Registre RPO/RTO observados como medição do ensaio, não como garantia institucional.
4. O retorno de código pode ser seguro; retorno de schema ou dados exige plano específico e não deve usar comandos destrutivos automáticos.

## Itens que exigem homologação do IFPE

- Serviços, calendários, janelas de abertura, documentos alternativos e regras acadêmicas.
- SMTP, origem/destino de e-mail, backups, limites de alertas e responsáveis de plantão.
- Integração institucional, SSO, SUAP, domínio, HTTPS, banco e armazenamento de produção.
- Políticas de retenção, privacidade, resposta a incidentes e autorização do piloto.
