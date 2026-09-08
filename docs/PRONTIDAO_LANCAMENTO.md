# Prontidão para lançamento

## Estado técnico local

| Área | Estado | Evidência |
| --- | --- | --- |
| Requerimentos, chat e anexos | implementado | testes de fluxo, chat e documentos |
| Notificações | implementado localmente | testes de notificações e QA do sino |
| Formulários e rascunhos | implementado localmente | testes de formulário dinâmico e QA |
| Escopos e auditoria | implementado localmente | testes de escopo e auditoria |
| Métricas e catálogo | inicial | endpoints e testes locais |
| Produção institucional | não autorizado | depende de homologação do IFPE |

## Portões antes de produção

- Configurar Node Linux no WSL/CI e executar build e lint no ambiente de entrega.
- Configurar banco, fila, armazenamento privado e transporte de e-mail homologados.
- Validar matriz de permissões, regras de documentos, catálogo, calendário e retenção com o IFPE.
- Executar testes de regressão, revisão de segurança e validação visual em ambiente equivalente ao de produção.
- Revisar migrations e realizar backup antes de qualquer implantação.

Este documento não autoriza deploy nem declara conformidade institucional.
