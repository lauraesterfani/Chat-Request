# Roadmap de lançamento

## Fase 0 — Estabilização (atual)

Prioridade máxima: fluxo de requerimentos, upload protegido, protocolos únicos, status centralizados, autorização no back-end, dashboard de atrasos, configuração única do Next, seeders fictícios, documentação e testes.

## Fase 1 — Chat real e histórico de atendimento (concluída)

Conversas textuais persistentes vinculadas ao requerimento, participantes, autorização por escopo, paginação, leituras individuais e polling REST foram implementados. WebSockets, anexos e notificações externas continuam planejados para evolução posterior.

## Fase 2 — Operação e regras configuráveis

Respostas pré-configuradas, exigências documentais configuráveis, fluxo completo de status, encaminhamento, prazos/SLA, notificações, decisões/documentos formais, rascunhos, prevenção de duplicidade e linha do tempo.

## Fase 3 — Integrações e escala institucional

Autenticação institucional, possível SUAP, múltiplos campi, calendário acadêmico, catálogo oficial, formulários dinâmicos e distribuição de atendimentos.

## Fase 5 — Governança, experiência e observabilidade

Auditoria, LGPD, permissões detalhadas, segurança avançada de anexos, satisfação, acessibilidade, ajuda contextual, dashboard operacional, relatórios, base de conhecimento, indicadores e monitoramento técnico.

## Fase 4 — Fluxo de requerimentos (núcleo implementado)

Setor responsável, resultado separado, eventos de criação/status/encaminhamento/decisão, endpoints protegidos e linha do tempo do aluno foram adicionados. Regras institucionais de transição, setores formais e devolução ainda precisam de validação do IFPE.

As regras devem ser confirmadas com o IFPE antes da produção. Nenhuma fase futura foi implementada nesta etapa.

## Fase 5 — Prazos, filas e distribuição (núcleo incremental)

Políticas de prazo em rascunho/ativa, snapshot de metas no requerimento, indicadores de prazo corrido, fila server-side e atribuição manual foram adicionados. Calendários de expediente, pausas, ajustes excepcionais, distribuição automática e homologação institucional ainda não estão ativos.

## Fase 6 — Notificações internas e por e-mail (núcleo incremental)

Central interna, contador individual, leitura, preferências por categoria, idempotência e job de e-mail foram adicionados para mensagens públicas e mudanças de status. O canal externo permanece desativado até configuração e homologação de SMTP; não há WhatsApp, SMS, push ou campanhas.

## Fase 7 — Formulários dinâmicos, rascunhos e duplicidade (núcleo incremental)

Formulários versionados por serviço, validação no servidor, rascunhos com proteção contra conflito e prevenção de submissões duplicadas foram adicionados. A configuração de regras acadêmicas e de todos os serviços continua dependente de homologação institucional.

## Fase 8 — Permissões por campus, curso e setor (núcleo incremental)

Escopos explícitos para acesso administrativo e verificações de autorização em requerimentos, documentos e auditoria foram adicionados. A matriz final de campi, cursos, setores e perfis precisa ser aprovada pelo IFPE antes da produção.

## Fase 9 — Auditoria, proteção de dados e recuperação (núcleo incremental)

Trilha de auditoria com metadados protegidos, controles de acesso e documentação de proteção de dados e recuperação foram adicionados. Políticas de retenção, respostas a incidentes e responsabilidades institucionais ainda exigem definição formal.

## Fase 10 — Indicadores, relatórios e satisfação (núcleo incremental)

Indicadores agregados por escopo, coleta voluntária de satisfação e dicionário de métricas foram adicionados. Metas, relatórios oficiais e critérios de interpretação permanecem sujeitos à validação institucional.

## Fase 11 — Catálogo, calendários, acessibilidade e orientação (núcleo incremental)

Catálogo público governado, janelas de abertura, base de conhecimento, acessibilidade inicial, páginas responsivas e orientação de uso foram adicionados. A publicação dos serviços, regras de calendário e revisão completa de acessibilidade permanecem pendentes de homologação.

## Fase 12 — Integrações, homologação e lançamento assistido (preparação técnica)

Foram preparados adaptador de identidade institucional desativado por padrão, health check, CI de regressão, roteiro de homologação, runbook, guias de piloto e matriz GO/NO-GO. Não há integração institucional real, homologação do IFPE, provisionamento de produção ou autorização de lançamento nesta etapa.

---

## Balanço técnico das Fases 6–12 e pendências pré-produção

### O que foi implementado tecnicamente

| Área | Implementado |
|------|-------------|
| **Notificações (F6)** | Central interna, contador, leitura individual e em massa, preferências por categoria, job de e-mail com idempotência |
| **Formulários dinâmicos (F7)** | Schemas versionados por serviço, validação server-side, rascunhos com lock otimista, prevenção de duplicidade |
| **Permissões por escopo (F8)** | Escopos explícitos por campus/setor em requerimentos, documentos e auditoria; middleware `role` por perfil |
| **Auditoria e dados (F9)** | Trilha de auditoria com metadados protegidos, controles de acesso granulares, documentação de proteção de dados |
| **Indicadores e satisfação (F10)** | Métricas agregadas por escopo, coleta voluntária de satisfação, dicionário de métricas no endpoint `/metrics` |
| **Catálogo e calendários (F11)** | Catálogo governado com janelas de abertura, base de conhecimento, pages responsivas, acessibilidade inicial |
| **Infraestrutura e docs (F12)** | Health check com verificação de banco e fila (`GET /api/health`), `docs/API.md`, `docs/RUNBOOK.md`, adaptador de IdP desativado |

### O que ainda falta antes de ir a produção

As pendências abaixo **não são omissões técnicas** — são decisões institucionais ou contratuais que o IFPE precisa formalizar:

| Pendência | Detalhes |
|-----------|---------|
| **Homologação institucional** | Nenhuma fase foi validada pelo IFPE em ambiente real. É necessário ciclo formal de UAT com usuários reais (alunos e servidores CRADT). |
| **SMTP real** | O canal de e-mail permanece em modo `log`. Exige contratação de relay SMTP (ex.: Postfix institucional, SendGrid ou similar) e configuração das variáveis `MAIL_*`. |
| **IdP institucional** | `INSTITUTIONAL_IDENTITY_ENABLED=false`. Requer contrato, documentação do emissor e testes de integração com o provedor de identidade do IFPE. |
| **Integração SUAP** | Não implementada. Depende de API disponível, credenciais e aprovação da TI do IFPE. |
| **Matriz de campi e cursos** | Os escopos de autorização foram implementados, mas a matriz real (quais setores, cursos e campi existem) precisa ser fornecida e cadastrada pela gestão. |
| **Regras de transição de status** | O fluxo de encaminhamento e devolução está implementado, mas as regras institucionais específicas (quem aprova o quê, em qual ordem) precisam ser validadas com a CRADT. |
| **Políticas de SLA** | O mecanismo existe, mas prazos reais (por tipo de requerimento) precisam ser definidos e ativados com a gestão. |
| **Calendário acadêmico** | Janelas de abertura de serviço estão implementadas, mas os calendários oficiais por curso/período ainda não foram configurados. |
| **Publicação do catálogo de serviços** | Os formulários dinâmicos e o catálogo existem, mas nenhum serviço real foi configurado e publicado ainda. |
| **Servidor de produção** | Não há provisionamento de infraestrutura de produção (servidor, banco, proxy reverso, HTTPS, backups automáticos). |
| **Política de retenção e LGPD** | Descrita em documentação, mas não há definição formal de prazos de retenção, responsável pelo DPO ou procedimento de exclusão de dados. |
| **Revisão completa de acessibilidade** | Acessibilidade básica implementada, mas não há auditoria WCAG 2.1 AA completa nem testes com tecnologia assistiva. |
| **Autorização de lançamento (GO/NO-GO)** | Nenhuma das fases acima foi homologada. A matriz GO/NO-GO não pode ser considerada aprovada sem a validação institucional formal. |
