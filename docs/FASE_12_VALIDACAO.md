# Validação da Fase 12

## Estado

**Parcial; produção em NO-GO.** Este registro separa o que foi configurado, testado localmente, não executado e dependente de autorização institucional. Ele não é um aceite do IFPE.

No momento da revisão, a base era `718c1aa` (`main`), com alterações de outras tarefas ainda não integradas no working tree. Não foi criada uma versão candidata/release, e nenhuma alteração remota, deploy ou branch protection foi realizada nesta tarefa.

## Resultado dos blocos 12A–12F

| Bloco | Resultado | Evidência atual | Próxima condição de saída |
| --- | --- | --- | --- |
| **12A — Auditoria e decisões** | Parcial | [`MATRIZ_GO_NO_GO.md`](MATRIZ_GO_NO_GO.md) mapeia critério, evidência, responsável e bloqueio; [`ROTEIRO_HOMOLOGACAO_INTEGRADA.md`](ROTEIRO_HOMOLOGACAO_INTEGRADA.md) enumera os cenários de aceite | Responsáveis do IFPE registrarem decisão/fonte sobre serviços, regras, perfis, conteúdo, integração e critérios do piloto |
| **12B — Identidade/SUAP** | Bloqueado externamente; modo seguro preparado | `InstitutionalIdentityProvider` e testes unitários para integração desativada e falha segura; [`INTEGRACAO_IDENTIDADE_INSTITUCIONAL.md`](INTEGRACAO_IDENTIDADE_INSTITUCIONAL.md) lista protocolo, emissor, audience, escopos e processo de vínculo necessários | TI fornecer contrato/ambiente/autorizações; só então implementar e validar adapter. Não há integração real nem se afirma que SUAP exponha uma API específica |
| **12C — Build, ambientes e CI** | CI configurada com auditorias de dependências; execução remota não verificada | [`.github/workflows/ci.yml`](../.github/workflows/ci.yml) roda Composer/npm audit, PHP, TypeScript, lint, build, Playwright, Gitleaks e Dependency Review em PRs. Testes PHP fixam SQLite em memória e fila síncrona | Confirmar GitHub Actions e Dependency Graph, verificar execução remota, definir banco/infra de homologação e acrescentar teste compatível com a semântica desse banco |
| **12D — Aceite, carga e recuperação** | Recuperação local de fixture demonstrada; restante parcial/não executado | `bash scripts/phase9-restore-drill.sh` passou em 26/10/2026: restaurou cópia isolada com 1 pedido, 1 schema, 1 evento, 1 anexo íntegro e 0 jobs; duração observada nesta rodada 2 s. O roteiro integrado lista fluxos de aluno/equipe e falhas | Repetir restore no ambiente de homologação aprovado, incluir chaves/cifragem e validar filas. Carga, RPO/RTO aprovados, leitor de tela e ciclo integrado via API real não foram medidos neste trabalho |
| **12E — Operação, piloto e treinamento** | Documentação preparada; piloto/treinamento não executados | [`RUNBOOK.md`](RUNBOOK.md), [`RUNBOOK_OPERACAO_LOCAL.md`](RUNBOOK_OPERACAO_LOCAL.md), [`GUIA_ALUNO.md`](GUIA_ALUNO.md), [`GUIA_ATENDENTE.md`](GUIA_ATENDENTE.md), [`GUIA_COORDENACAO_ADMIN.md`](GUIA_COORDENACAO_ADMIN.md), [`GUIA_OPERACAO_TECNICA.md`](GUIA_OPERACAO_TECNICA.md) | Definir responsáveis, contatos, alertas, suporte, escopo do piloto, treinamento e checklist de entrada/saída com a instituição |
| **12F — GO/NO-GO e entrega** | NO-GO | [`MATRIZ_GO_NO_GO.md`](MATRIZ_GO_NO_GO.md) registra itens bloqueados e responsáveis; [`PRONTIDAO_LANCAMENTO.md`](PRONTIDAO_LANCAMENTO.md) não autoriza produção | Identificar artefato/commit e alvo exatos, aprovar backup, migrations, smoke tests, critério de abortar e rollback compatível; decisão final cabe aos responsáveis institucionais |

## CI versionada

O workflow aplica permissões somente de leitura. As ações de checkout, setup de Node, Gitleaks e Dependency Review estão fixadas a SHAs de releases verificadas. Gitleaks não comenta PR nem publica artifact; a execução em `pull_request` tem somente token de leitura, inclusive para PRs de forks. Dependency Review roda somente em PRs e depende do Dependency Graph estar disponível no GitHub. Gitleaks varre histórico nos eventos `push` e `pull_request`.

`vendor/bin/pint --test` continua não bloqueante porque falhou localmente no baseline completo: 48 questões de estilo em 209 arquivos após o Pint 1.32.1; os arquivos PHP tocados nesta rodada passaram na verificação direcionada. `npm run lint` passou com código de saída 0; nesta rodada permanecem 73 avisos e nenhum erro. Esta configuração versionada não prova que Actions executou ou que os checks são obrigatórios no repositório remoto.

## Verificações executadas nesta revisão

| Comando/checagem | Resultado observado | Limite |
| --- | --- | --- |
| `npm run lint` | **Passou**, exit code 0; 73 avisos, 0 erros | Avisos de lint permanecem; a maioria vem de páginas/fluxos legados e regras novas do eslint-config-next |
| `vendor/bin/pint --test` | **Falhou**, 48 questões em 209 arquivos | O step Pint segue não bloqueante; os arquivos PHP alterados na tarefa passaram após correção localizada; não foi aplicado formatador em massa |
| `bash scripts/phase9-restore-drill.sh` | **Passou**, 1 pedido/schema/evento/anexo restaurados e 0 jobs; 2 s observados | Fixture SQLite local, sem backup de produção, chaves ou metas aprovadas |
| `composer audit --locked --no-interaction` | **Passou**, nenhum advisory | Lockfile atualizado em 26/10/2026; gate adicionado à CI |
| `npm audit --audit-level=low` | **Passou**, zero vulnerabilidades | Lockfile atualizado em 26/10/2026; gate adicionado à CI |
| `npx tsc --noEmit` e build isolada Next | **Passaram** com Next.js 16.4.3 | TypeScript foi incluído na build; build sem aviso de raiz do workspace após fixar a raiz Turbopack em `frontend/` |
| Playwright completo | **64 passaram, 6 foram ignorados por integrações externas ausentes**; desktop e mobile | Inclui recuperação de rascunho após falha de rede, convite de satisfação, notificações, catálogo e fluxos listados. Cenários que requerem API setorial/externa são executados pelo script de Fase 7 ou ambiente integrado |
| API E2E da Fase 7 | **4 execuções passaram**: envio com protocolo e anexo; retomada de rascunho com formulário de disciplinas entre celular e computador; canal externo bloqueado sem protocolo; sessão expirada sem perda do rascunho | SQLite e storage temporários, contas sintéticas `.test`; executado por `scripts/run-phase7-api-e2e.sh` em desktop e mobile |
| `php artisan test` | **Passou em 26/10/2026**: 183 testes, 1.347 assertions | SQLite isolado conforme `phpunit.xml`; não substitui validação em MySQL/PostgreSQL de homologação |
| GitHub Actions / Dependency Review / Gitleaks remoto | **Não executado/verificado** | Exige push/PR e configuração do repositório; esta tarefa não enviou alterações |
| Carga e acessibilidade manual/leitor de tela | **Não executados** | Requer ambiente e critérios acordados; Axe automatizado agora cobre telas selecionadas de calendário, métricas, base de conhecimento, aviso/solicitações de privacidade e outros fluxos documentados, mas não substitui validação manual |

## Segurança e fork PRs

- Workflow usa `pull_request`, nunca `pull_request_target`; não eleva permissões para código não confiável.
- `GITHUB_TOKEN` tem leitura de conteúdo e PR; comentários e upload de artifacts do Gitleaks estão desativados.
- O scanner de dependências compara alterações em PRs; a habilitação do Dependency Graph no GitHub deve ser confirmada por mantenedor. Esse gate detecta dependências vulneráveis introduzidas na mudança e não substitui a remediação do baseline.
- Composer e npm audit agora são gates de CI porque ambos os lockfiles passaram sem vulnerabilidades na validação local de 26/10/2026. A execução remota ainda precisa ser confirmada.

## Baseline de dependências observado

Os comandos foram executados em 26/10/2026 contra os lockfiles atuais. Nenhum pacote foi atualizado nesta tarefa.

| Ecossistema | Resultado após remediação | Atualização aplicada | Validação |
| --- | --- | --- | --- |
| Composer | **0 advisories** | `laravel/framework` 12.70.1, `phpunit/phpunit` 11.5.57 e dependências compatíveis atualizadas no lockfile | `composer audit --locked --no-interaction`; suíte PHP completa: 165 testes, 1.160 assertions |
| npm | **0 vulnerabilidades** | Next.js e `eslint-config-next` 16.4.3, Axios 1.20.0, dependências transitivas corrigidas; `nookies` removido por não haver uso no código e por trazer `cookie` vulnerável | `npm audit --audit-level=low`; lint, TypeScript, build de produção e E2E |

Os lockfiles atuais passaram nos audits locais em 26/10/2026. Isso não substitui a atualização contínua e a execução dos scanners no repositório remoto.
- O modo read-only impede escrita do token nos fluxos; GitHub pode restringir ainda mais tokens para forks.
- Nenhum segredo foi lido ou copiado para o workflow. Gitleaks usa a versão da ação upstream com redação de achados; resultados remotos ainda precisam ser examinados e segredos eventualmente encontrados devem ser rotacionados por procedimento autorizado.

## Plano de publicação ainda necessário

Antes de preparar release candidate, preencher e aprovar em ambiente de homologação:

1. versão/commit e alvo de implantação;
2. serviços, público, matriz de campi/cursos/setores e integrações obrigatórias;
3. banco e storage escolhidos, migrations compatíveis e backup de ambos restaurado;
4. worker, scheduler, SMTP capturado/homologado, saúde e observabilidade;
5. regressão completa, E2E integrado, acessibilidade manual e teste de carga limitado;
6. passos de abortar e rollback de aplicação/schema/dados, incluindo limites não reversíveis;
7. responsáveis que assinam cada decisão GO/NO-GO.

Nenhum valor institucional de RPO/RTO, disponibilidade, capacidade, SLA, calendário, retenção, endereço de suporte ou meta de desempenho foi inventado neste documento.
