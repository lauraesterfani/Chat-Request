# Dados e privacidade

## Finalidade técnica atual

O Chat Request trata dados estritamente necessários para autenticar solicitantes, registrar requerimentos, anexos, mensagens, decisões e operação da equipe. Este documento descreve controles técnicos locais; não substitui análise jurídica, política de retenção ou homologação do IFPE.

| Categoria | Uso | Acesso técnico atual | Observação |
| --- | --- | --- | --- |
| Identificação e vínculo | autenticação e escopo de requerimento | solicitante, CRADT e coordenação no curso autorizado | CPF não é retornado nas respostas de requerimento comuns |
| Mensagens e decisões | atendimento e histórico | participantes autorizados | não registrar conteúdo em `audit_records` |
| Anexos | comprovação do pedido | proprietário e equipe academicamente autorizada | armazenamento privado e autorização de download continuam obrigatórios |
| Eventos e auditoria | rastreabilidade operacional | admin e CRADT | metadados mínimos; sem senha, token, anexo ou mensagem integral |
| Notificações | avisos transacionais | destinatário autenticado | e-mail depende de preferência e transporte homologado |

## Limites e pendências institucionais

- Base legal, prazos de guarda, descarte, atendimento a titulares e comunicação de incidentes exigem decisão institucional.
- Não há exclusão automática de requerimentos, anexos ou rascunhos por esta documentação.
- Auditoria operacional não equivale a certificação de conformidade LGPD.
- Exportações, backups e qualquer transporte externo devem passar por autorização, controle de acesso e revisão antes de produção.
