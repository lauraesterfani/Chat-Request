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
