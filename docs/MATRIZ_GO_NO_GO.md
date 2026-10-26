# Matriz GO NO GO para lançamento

Esta matriz separa evidência técnica local de decisões institucionais. O estado GO/NO-GO abaixo não autoriza produção; nenhuma aprovação institucional foi registrada neste repositório.

## Decisão para o escopo MVP

Para demonstração local com dados sintéticos, o estado é **candidato a MVP**, com regressão Laravel atual aprovada (183 testes/1.347 assertions) e TypeScript validado. O roteiro e as exclusões estão em [`ESCOPO_MVP.md`](ESCOPO_MVP.md). O NO-GO abaixo se refere a produção ou operação com dados reais, não à validação local do MVP.

| Critério | Estado técnico | Evidência concreta | Decisão responsável | Bloqueio ou pendência |
| --- | --- | --- | --- | --- |
| Criação, protocolo, chat e anexos privados | Testado localmente | `tests/Feature/RequestFlowTest.php`, `tests/Feature/RequestMessageTest.php`, `tests/Feature/DocumentAccessAuditTest.php` | Equipe do projeto e CRADT | Reexecutar no ambiente de homologação e homologar serviços, documentos e protocolo |
| Formulários, rascunhos, idempotência e matrículas | Testado localmente; homologação pendente | `tests/Feature/DynamicFormTest.php`, `tests/Feature/EnrollmentSelectionTest.php`, `tests/Feature/DuplicateRequestPreventionTest.php` | CRADT e gestão acadêmica | Confirmar formulários, elegibilidade, duplicidade e serviços do piloto |
| Autorização por papel, campus, curso e setor | Testado localmente; matriz real pendente | `tests/Feature/StaffAccessScopeTest.php`, `tests/Feature/StaffAccountAuthorizationTest.php` | CRADT e TI | Cadastrar e revisar a matriz institucional; analisar pedidos legados sem snapshot |
| Primeiro acesso e autenticação de aluno | Testado localmente | `tests/Feature/StudentAuthenticationTest.php` | TI institucional | Definir importação, entrega segura de senha inicial e processo de suporte |
| Auditoria operacional | Parcialmente testado localmente | `tests/Feature/StaffAccessScopeTest.php`, `tests/Feature/StaffLoginAuditTest.php`, `tests/Feature/DocumentAccessAuditTest.php` | TI e encarregado de dados | Revisar eventos, controles de banco, escopo e retenção com a instituição |
| Notificações e fila | Testado localmente; transporte real pendente | Testes de `tests/Feature/NotificationTest.php`; configuração de e-mail no `phpunit.xml` usa transporte de teste | CRADT e TI | Homologar SMTP, destino verificado, operação e monitoramento de fila |
| Catálogo, base de conhecimento e janelas | Parcialmente testado localmente | `tests/Feature/ServiceCatalogTest.php`, `tests/Feature/KnowledgeArticleTest.php`; Playwright smoke do catálogo usa API simulada | CRADT e responsáveis editoriais | Aprovar serviços, fontes, conteúdo público, calendário e regras de abertura |
| Indicadores e satisfação | Testado localmente; interpretação pendente | `tests/Feature/MetricsTest.php`, `tests/Feature/SatisfactionResponseTest.php`, `docs/DICIONARIO_INDICADORES.md` | Gestão e encarregado de dados | Aprovar amostra, finalidade, acesso aos comentários e interpretação dos indicadores |
| Build e regressão automatizada | Testado localmente; CI remota não verificada | `.github/workflows/ci.yml`; execução local deve ser registrada em `docs/FASE_12_VALIDACAO.md` | Mantenedor do repositório | Confirmar Actions habilitado, checks exigidos e configuração da revisão de dependências |
| Semântica do banco de produção | Não verificado | `phpunit.xml` fixa SQLite em memória; não há job MySQL/PostgreSQL na CI | TI | Escolher o banco de homologação e executar testes de concorrência/transação nele |
| Segredos e dependências | **Audits locais sem achados; scanner remoto não executado** | `composer audit` e `npm audit` passaram sem vulnerabilidades e agora são gates da CI; workflow inclui Gitleaks em pushes/PRs e Dependency Review para novas dependências em PRs | Mantenedor e TI | Confirmar a execução remota do CI e do Dependency Graph; revisar alertas de segredo sem expor valores |
| Backup e restauração | Ensaio local de fixture passou; restauração de produção/homologação pendente | `bash scripts/phase9-restore-drill.sh` passou em 26/10/2026: cópia SQLite isolada preservou 1 requerimento, 1 esquema, 1 evento e 1 anexo íntegro; 0 jobs, ~2 s. [`RUNBOOK.md`](RUNBOOK.md) e [`RUNBOOK_OPERACAO_LOCAL.md`](RUNBOOK_OPERACAO_LOCAL.md) documentam o procedimento | TI institucional | Repetir no alvo de homologação aprovado incluindo armazenamento e chaves; definir e medir RPO/RTO. O exercício local não demonstra backup restaurável de produção |
| Segurança e privacidade | Controles técnicos parciais; validação institucional pendente | `docs/DADOS_E_PRIVACIDADE.md`, `docs/AVISO_PRIVACIDADE_RASCUNHO.md`, testes de auditoria | Encarregado de dados e TI | Aprovar aviso, finalidade/base aplicável, retenção, direitos do titular e resposta a incidentes |
| Acessibilidade | Verificação automatizada localizada | `frontend/e2e/smoke.spec.ts` executa Axe em home e login e inclui teste de atalho de teclado | Responsáveis do produto e acessibilidade | Avaliar fluxos autenticados, teclado, zoom, leitores de tela e critérios WCAG aplicáveis |
| Identidade institucional/SUAP | Não integrado; adaptador desativado | `docs/INTEGRACAO_IDENTIDADE_INSTITUCIONAL.md`; `tests/Unit/InstitutionalIdentityProviderTest.php` | TI/IFPE | Fornecer contrato, protocolo, ambiente, escopos, identificador estável e autorização. Não habilitar por configuração apenas |
| Carga, disponibilidade e observabilidade | Não executado/configurado | Não há relatório de carga nem limites aprovados no repositório | TI e gestão | Definir ambiente e limites seguros; medir erros, latência, consultas, memória e fila |
| Piloto e treinamento | Materiais preparados; execução não registrada | `docs/ROTEIRO_HOMOLOGACAO_INTEGRADA.md` e guias separados em `docs/GUIA_*.md` | CRADT, TI e gestão | Escolher escopo, responsáveis, suporte, treinamento, fallback e coleta de problemas |
| Plano de publicação e retorno | Parcial | Pré-checagem nesta matriz e procedimentos gerais em `docs/RUNBOOK.md` | TI institucional | Fixar versão/ambiente, backup restaurável, compatibilidade de migrations, abortar e rollback específico |

## Decisão atual

**NO-GO para produção.** A decisão é conservadora porque não há aceite institucional, restauração validada, banco de produção exercitado nem matriz oficial de serviços e acessos. Isso não invalida os testes locais; significa que eles não demonstram esses requisitos externos.

## Condições que bloqueiam o lançamento

- Qualquer acesso indevido, protocolo duplicado, anexo público, perda de histórico ou decisão incorreta.
- Ausência de backup restaurado e de um procedimento de retorno adequado ao ambiente escolhido.
- Falta de homologação de serviços, formulários, documentos, janelas, papéis, privacidade ou integrações obrigatórias.
- Falta de infraestrutura aprovada, operação de filas/armazenamento/e-mail e responsáveis de suporte.

## Pré-checagem para um futuro ensaio autorizado

1. Registrar commit, versão candidata, ambiente e responsáveis.
2. Confirmar que banco, armazenamento, fila e e-mail são isolados e usam dados fictícios.
3. Conferir migrations pendentes e compatibilidade antes de aplicá-las; não usar comandos destrutivos.
4. Criar e restaurar backup de banco e arquivos no alvo isolado; verificar integridade e registrar duração observada.
5. Executar testes, build, smoke tests e cenários de aceite no mesmo ambiente de homologação.
6. Registrar resultados e decisões assinadas pelos responsáveis institucionais. Só então avaliar GO/NO-GO.

Consulte [`FASE_12_VALIDACAO.md`](FASE_12_VALIDACAO.md) para os resultados técnicos efetivamente observados e as limitações ainda sem evidência.
