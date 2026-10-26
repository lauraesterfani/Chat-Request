# Roadmap de lançamento

## Objetivo atual: MVP local

O objetivo imediato é um MVP para demonstrações e validação com dados sintéticos, não uma implantação institucional. Para esse escopo, integrações reais, políticas aprovadas pelo IFPE e infraestrutura de produção são pendências de lançamento, não bloqueios para validar localmente. Consulte [`docs/ESCOPO_MVP.md`](docs/ESCOPO_MVP.md) para o conjunto incluído, evidências e roteiro de validação. O sistema continua **NO-GO para dados reais/produção** até cumprir os portões institucionais.

## Fase 0 — Estabilização (concluída)

Prioridade máxima: fluxo de requerimentos, upload protegido, protocolos únicos, status centralizados, autorização no back-end, dashboard de atrasos, configuração única do Next, seeders fictícios, documentação e testes.

## Fase 1 — Chat real e histórico de atendimento (concluída)

Conversas textuais persistentes vinculadas ao requerimento, participantes, autorização por escopo, paginação, leituras individuais e polling REST foram implementados. WebSockets, anexos e notificações externas continuam planejados para evolução posterior.

## Fase 2 — Operação e regras configuráveis (núcleo implementado nas Fases 4–11)

Respostas pré-configuradas por setor, exigências documentais, estados e decisões, encaminhamento, mecanismos de prazo, notificações internas, documentos protegidos, rascunhos, duplicidade e linha do tempo estão implementados. Regras acadêmicas, SLA e mensagens externas ainda dependem de homologação institucional.

## Fase 3 — Integrações e escala institucional (parcial)

Escopos multi-campus, catálogo, calendário de janelas e formulários dinâmicos têm base técnica. Autenticação institucional e SUAP não estão conectados; calendário acadêmico oficial, dados institucionais reais e distribuição automática ainda precisam de implementação/aprovação.

## Fase 5 — Governança, experiência e observabilidade

Auditoria, LGPD, permissões detalhadas, segurança avançada de anexos, satisfação, acessibilidade, ajuda contextual, dashboard operacional, relatórios, base de conhecimento, indicadores e monitoramento técnico.

## Fase 4 — Fluxo de requerimentos (núcleo implementado)

Setor responsável, resultado separado, eventos de criação/status/encaminhamento/decisão, endpoints protegidos e linha do tempo do aluno foram adicionados. As transições agora são explícitas; pendência ao aluno e cancelamento exigem orientação/motivo, conclusão passa pela rota de decisão, e a opção “não aplicável” depende da configuração do serviço. A tela de atendimento separa etapa operacional e resultado e publica a resposta final no histórico do chat. Destinos de encaminhamento são limitados aos setores existentes (`CRADT` e `COORDENACAO`), e devolução exige escopo operacional e setor anterior no histórico. As áreas do IFPE ainda precisam confirmar que esses setores, transições e atribuições representam o processo institucional.

## Fase 5 — Prazos, filas e distribuição (núcleo incremental)

Políticas de prazo em rascunho/ativa, snapshot de metas no requerimento, indicadores de prazo corrido, fila server-side e atribuição manual foram adicionados. Calendários de expediente, pausas, ajustes excepcionais, distribuição automática e homologação institucional ainda não estão ativos.

## Fase 6 — Notificações internas e por e-mail (núcleo incremental)

Central interna, contador individual, leitura, preferências por categoria, idempotência e job de e-mail existem. O e-mail foi bloqueado para endereços ainda não verificados, inclusive na execução de jobs antigos; acesso a notificações vinculadas a requerimentos é revalidado após revogação de escopo. Ainda faltam o fluxo de verificação, cobertura completa dos eventos, E2E com API real em sessões de aluno/equipe, SMTP e política institucional homologados; fase 6 parcial (ver `docs/FASE_6_VALIDACAO.md`).

## Fase 7 — Formulários dinâmicos, rascunhos e duplicidade (núcleo incremental)

O núcleo técnico da Fase 7 está implementado e validado localmente. Schemas são versionados e validados no servidor; rascunhos têm revisão otimista, propriedade verificada, anexos privados, retomada entre dispositivos, preservação em falha de rede e migração confirmada entre versões; chaves de idempotência são escopadas por aluno e o envio é repetível. A regra de duplicidade avisa por padrão, oferece o pedido próprio existente e exige confirmação; bloqueio duro exige referência de regra e administrador institucional sem escopo restrito. A comparação atual usa aluno, serviço, vínculo e assunto normalizado, não equivalência semântica. Os fluxos cobertos em Feature, E2E visual e E2E contra API/SQLite isolados estão documentados em `docs/FASE_7_VALIDACAO.md`. Pendências para homologação: mapear oficialmente os 33 serviços e suas exigências; decidir elegibilidade de graduados/desvinculados e regras de duplicidade específicas; homologar schemas acadêmicos. Pedidos legados sem snapshot de matrícula mantêm o vínculo disponível no perfil, sem inferir histórico que não foi armazenado.

## Fase 8 — Permissões por campus, curso e setor (núcleo incremental)

Fase 8 validada tecnicamente, inclusive com fluxo Playwright + Laravel real em desktop e mobile (2/2): concessão agendada, fila limitada ao campus autorizado, detalhe de outro campus negado e revogação que remove acesso após recarga. A fila mobile foi reorganizada em cartões; as mensagens de concessão e revogação permanecem visíveis após atualizar a lista. Concessões têm campus/curso/setor, capacidades, início, expiração e revogação preservada; somente administrador institucional sem escopo restritivo pode delegar ou administrar contas/campi. A mesma concessão precisa conter a capacidade e o escopo, e contas técnicas não recebem acesso acadêmico implícito. Listas, filas, totais, detalhes, documentos, mensagens e respostas pré-configuradas aplicam escopo no servidor. Coordenações sem concessão explícita ficam limitadas ao curso do vínculo do servidor e aos pedidos atualmente sob coordenação; pedidos legados sem snapshot de curso/campus não herdam o perfil atual do aluno. Os endpoints administrativos de campi e associação de cursos existem, mas não foram cadastrados campi reais. Antes de produção, a gestão precisa fornecer e aprovar a matriz institucional, importar os vínculos corretos e revisar os registros legados listados por `php artisan requests:unresolved-access-contexts`. Consulte [`docs/FASE_8_VALIDACAO.md`](docs/FASE_8_VALIDACAO.md) para resultados, capturas e pendências.

## Fase 9 — Auditoria, proteção de dados e recuperação (núcleo técnico validado; homologação parcial)

Trilha de auditoria com metadados protegidos, controles de acesso, histórico de pedidos dos titulares, aviso versionado de privacidade, simulação de retenção somente leitura e ensaio de restauração em fixture isolada foram adicionados. A fase continua parcial: faltam política aprovada de retenção e guarda, testes de descarte controlado, cobertura de dependências/backups e definições formais de resposta a incidentes, encarregado e canais institucionais. Os fluxos não equivalem a conformidade LGPD.

## Fase 10 — Indicadores, relatórios e satisfação (núcleo incremental)

Painel com sete filtros limitados ao escopo visível, tabelas acessíveis, satisfação agregada e exportação privada autenticada XLSX/PDF estão implementados. Cálculo de percentis/coortes/SLA e definição de metas ou interpretação de resultados continuam pendentes de escopo e validação institucional.

## Fase 11 — Catálogo, calendários, acessibilidade e orientação (núcleo incremental)

Catálogo governado, janelas de atendimento com fuso/UTC explícito, tela de gestão de calendário, base de conhecimento versionada, feed de orientações autenticado para alunos (`/knowledge`), telas responsivas e acessibilidade automatizada foram adicionados. Datas e referências reais, conteúdo oficial e acessibilidade manual precisam de homologação.

## Fase 12 — Integrações, homologação e lançamento assistido (preparação técnica)

Foram preparados adaptador de identidade institucional desativado por padrão, health check, CI de regressão, roteiro de homologação, runbook, guias de piloto e matriz GO/NO-GO. Não há integração institucional real, homologação do IFPE, provisionamento de produção ou autorização de lançamento nesta etapa.

---

## Balanço técnico das Fases 6–12 e pendências pré-produção

### O que foi implementado tecnicamente

| Área | Implementado |
|------|-------------|
| **Notificações (F6)** | Central interna, contador, leitura individual e em massa, preferências por categoria, job de e-mail com idempotência |
| **Formulários dinâmicos (F7)** | Schemas versionados por serviço, validação server-side, rascunhos com lock otimista, prevenção de duplicidade |
| **Permissões por escopo (F8)** | Escopos explícitos por campus/curso/setor e capacidade, grants temporais e revogáveis, isolamento de listagens, detalhes, documentos, mensagens, templates e agregados |
| **Auditoria e dados (F9)** | Auditoria protegida, solicitações autenticadas de titulares, aviso versionado e simulação de recuperação |
| **Indicadores e satisfação (F10)** | Métricas com filtros escopados, tabelas, satisfação agregada e exports privados XLSX/PDF |
| **Catálogo e calendários (F11)** | Catálogo, janelas configuráveis com guarda de timezone legado, editor de calendário e artigos autenticados para aluno |
| **Infraestrutura e docs (F12)** | Health check, CI com auditoria de dependências, Gitleaks, Dependency Review, runbooks e adaptador de IdP desativado |

### O que ainda falta antes de ir a produção

O trabalho técnico local está coberto pelos testes descritos nas fases. Permanecem pendências reais de implementação avançada, infraestrutura e decisões institucionais:

| Pendência | Detalhes |
|-----------|---------|
| **Homologação institucional** | Nenhuma fase foi validada pelo IFPE em ambiente real. É necessário ciclo formal de UAT com usuários reais (alunos e servidores CRADT). |
| **SMTP real** | O canal de e-mail permanece em modo `log`. Exige contratação de relay SMTP (ex.: Postfix institucional, SendGrid ou similar) e configuração das variáveis `MAIL_*`. |
| **IdP institucional** | `INSTITUTIONAL_IDENTITY_ENABLED=false`. Requer contrato, documentação do emissor e testes de integração com o provedor de identidade do IFPE. |
| **Integração SUAP** | Não implementada. Depende de API disponível, credenciais e aprovação da TI do IFPE. |
| **Matriz de campi e cursos** | Os escopos de autorização foram implementados, mas a matriz real (quais setores, cursos e campi existem) precisa ser fornecida e cadastrada pela gestão. |
| **Regras de transição de status** | O fluxo de encaminhamento e devolução está implementado, mas as regras institucionais específicas (quem aprova o quê, em qual ordem) precisam ser validadas com a CRADT. |
| **Políticas de SLA** | O mecanismo existe, mas prazos reais (por tipo de requerimento) precisam ser definidos e ativados com a gestão. |
| **Indicadores avançados** | Percentis/coortes, tempos úteis, métricas de fila e relatórios oficiais exigem definição de regras e interpretação antes de implementação. |
| **Calendário acadêmico** | Janelas de abertura de serviço estão implementadas, mas os calendários oficiais por curso/período ainda não foram configurados. |
| **Publicação do catálogo de serviços** | Os formulários dinâmicos e o catálogo existem, mas nenhum serviço real foi configurado e publicado ainda. |
| **Servidor de produção** | Não há provisionamento de infraestrutura de produção (servidor, banco, proxy reverso, HTTPS, backups automáticos). |
| **Política de retenção e LGPD** | Descrita em documentação, mas não há definição formal de prazos de retenção, responsável pelo DPO ou procedimento de exclusão de dados. |
| **Revisão completa de acessibilidade** | Acessibilidade básica implementada, mas não há auditoria WCAG 2.1 AA completa nem testes com tecnologia assistiva. |
| **Autorização de lançamento (GO/NO-GO)** | Nenhuma das fases acima foi homologada. A matriz GO/NO-GO não pode ser considerada aprovada sem a validação institucional formal. |
