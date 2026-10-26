# Prontidão para lançamento

> Este parecer é sobre produção institucional. Para demonstração local com dados fictícios, consulte [`ESCOPO_MVP.md`](ESCOPO_MVP.md): o estado é candidato a MVP técnico, sujeito à validação final dos fluxos no ambiente do operador.

## Decisão atual

**NO-GO para produção.** A implementação técnica ainda está em integração, e não há aceite do IFPE nem infraestrutura de produção homologada. Testes locais não substituem ensaio no ambiente escolhido, decisões institucionais ou aprovação formal.

O parecer detalhado, evidências locais, responsáveis e pendências estão em [`MATRIZ_GO_NO_GO.md`](MATRIZ_GO_NO_GO.md) e [`FASE_12_VALIDACAO.md`](FASE_12_VALIDACAO.md).

## Estado técnico conhecido

| Área | Estado | Evidência ou limitação |
| --- | --- | --- |
| Requerimentos, chat, decisões e anexos | Implementado; regressão precisa estar verde no checkout candidato | Suíte Laravel e Playwright; consultar validação versionada mais recente |
| Formulários, rascunhos e duplicidade | Implementado; configuração acadêmica pendente | Testes Laravel/E2E; serviços e regras dependem da CRADT |
| Permissões e auditoria | Implementação local com cobertura automatizada | Ainda requer revisão institucional de matriz e retenção |
| Notificações e satisfação | Implementadas para teste local | SMTP, política, elegibilidade e interpretação não homologados |
| Catálogo, janelas e orientação | Estruturas implementadas | Conteúdo oficial, calendário e fontes precisam de aprovação |
| Métricas e exportação | Núcleo local; cobertura tem limites registrados | Banco, semântica, amostras, formato oficial e uso dependem de validação |
| Identidade institucional/SUAP | Não integrada; adaptador explicitamente desativado | Faltam protocolo, escopos, ambiente de teste, contrato e autorização da TI |
| Recuperação | Ensaio isolado de fixture registrado em `FASE_9_VALIDACAO.md` | Não prova restauração de ambiente/backup de produção, cifragem, chaves ou RPO/RTO aprovados |
| Acessibilidade | Axe automatizado e QA visual independente nas telas públicas, calendário, métricas, artigos e privacidade | Leitor de tela e revisão manual completa ainda não executados |
| Dependências | Audits locais limpos | Composer e npm sem vulnerabilidades após atualização dos lockfiles em 26/10/2026; gates de auditoria adicionados à CI |
| CI | Workflow versionado e verificável localmente | Gitleaks e Dependency Review foram adicionados; execução remota, Dependency Graph e regras de proteção não foram confirmados |
| Produção institucional | Não autorizada | Não há ambiente provisionado, aceite ou decisão GO assinada |

## Portões antes de produção

- Obter aceite e decisões documentadas para serviços do piloto, formulários, documentos, calendários, papéis e conteúdo público.
- Definir banco, fila, storage privado, SMTP, domínio, HTTPS, CORS, segredos, responsáveis e monitoramento do ambiente.
- Executar CI remota e confirmar os checks requeridos; manter os audits locais limpos e revisar achados de segredo sem expor valores.
- Rodar a regressão completa contra o banco e a infraestrutura escolhidos, além de testes de segurança, acessibilidade e carga autorizados.
- Restaurar banco **e** arquivos em ambiente isolado, revisar permissões e filas e registrar duração e integridade observadas.
- Revisar migrations e fazer backup validado antes de qualquer operação de publicação.
- Registrar critérios de abortar, retorno de código e plano específico para incompatibilidade de dados/schema.
- Aprovar privacidade, retenção, atendimento a titulares, incidentes, treinamento, suporte, piloto e plano de rollback.
- Manter SSO/SUAP desativados até o contrato, o adapter homologado, os escopos e a autorização institucional estarem confirmados.

Este documento não declara conformidade jurídica, autorização de piloto ou autorização de deploy.
