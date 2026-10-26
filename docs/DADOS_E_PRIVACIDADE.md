# Inventário técnico e governança de dados — proposta para validação

**Estado:** rascunho técnico, não aprovado pelo IFPE e não parecer jurídico. Revisado em 26/10/2026. O sistema não declara conformidade LGPD por possuir controles técnicos.

## Inventário preliminar

| Categoria | Dados e origem | Finalidade operacional | Acesso/destino atual | Sensibilidade e dependências | Hipótese/base e retenção |
|---|---|---|---|---|---|
| Identidade e vínculo | Nome, e-mail, matrícula, curso/campus e identificador; provisionados pela instituição | Autenticação, vínculo e autorização | Solicitante sobre si; equipe conforme escopo; banco Laravel | Pessoais; dependem da fonte institucional e da sincronização de vínculos | Base legal e prazo: validação do IFPE; não apagar ao mudar o vínculo |
| Credenciais e sessão | Hash da senha; JWT no `sessionStorage` do navegador | Autenticação e controle de sessão | Hash no banco; token no cliente autenticado e no header de API | Alto impacto de segurança; reset e revogação dependem do guard JWT | Não exportar em rotinas de titular; prazo do token conforme configuração, guarda do hash até encerramento de conta conforme política aprovada |
| Requerimentos e formulários | Assunto, descrição, campos de formulário versionado, status, setor, protocolo e matrícula selecionada | Receber, encaminhar, analisar e preservar resultado acadêmico | Aluno vê seus pedidos; servidores veem conforme escopo; banco e cópias de segurança | Podem conter dados acadêmicos/sensíveis digitados pelo solicitante; dependem do schema e histórico | Base legal por serviço e prazo arquivístico a validar; nenhum prazo automático aprovado |
| Anexos e dados de saúde | Arquivos e metadados enviados pelo aluno; atestados podem revelar saúde | Instruir pedido quando a regra do serviço exigir | Storage privado; leitura apenas por proprietário e equipe autorizada; backup precisa incluir arquivos e chaves | **Sensível** quando contém saúde; varredura antimalware não está integrada | Prazo, fundamento e necessidade por serviço a validar; não copiar para eventos nem exports gerais |
| Mensagens, pareceres e decisões | Texto de aluno/equipe, setor, autoria e horário | Comunicação e registro do atendimento | Participantes autorizados e equipe responsável; banco e backup | Texto livre pode incluir dados de terceiros; notas internas não devem ser exportadas ao titular sem revisão | Base/prazo por finalidade a validar; conteúdo integral fica fora dos logs de auditoria |
| Rascunhos e anexos temporários | Campos e documentos ainda não enviados | Permitir continuar preenchimento | Proprietário e serviços internos autorizados | Podem conter os mesmos dados do pedido final; dependem de proteção de sessão/storage | Prazo de retenção e descarte ainda não configurados; não usar o aviso de 90 dias como prazo |
| Auditoria técnica | Ator/ação/recurso, horário, identificador de correlação, resultado e metadados allowlisted | Investigar e rastrear operação/administração | Administrador institucional irrestrito, conforme autorização do backend; banco | Metadados mínimos; IP e identificadores de sessão têm retenção a validar | Prazo de log a validar; sem senha, JWT, conteúdo de mensagem ou documento |
| Notificações | Destinatário, evento, estado de leitura e preferências; e-mail opcional | Informar atividade do próprio pedido | Destinatário autenticado; serviço de e-mail apenas se habilitado/configurado | Conteúdo não deve incluir anexo ou dado sensível desnecessário; SMTP ainda requer homologação | Retenção e fornecedor externo a validar; transporte real desativado até aprovação |
| Métricas e satisfação | Contagens agregadas; avaliação voluntária vinculada a pedido | Operação e avaliação de atendimento | Equipe autorizada; exportações dependem da Fase 10 | Agregados podem reidentificar grupos pequenos; comentários podem conter dado pessoal | Base, limiar de agregação, retenção e eventual exportação a validar |
| Catálogo e artigos | Regras, orientações, referências e conteúdo público/interno | Explicar serviços e atendimento | Conteúdo publicado no site ou visível à equipe conforme módulo | Pode refletir regra institucional desatualizada | Responsável, fonte oficial, revisão e prazo de publicação a validar |
| Exports, logs e backups | Exportações temporárias de métricas, auditoria, relatórios operacionais e cópias de recuperação | Operação e recuperação | Export de métricas fica em storage local privado e expira; demais destinos/backups não estão homologados | Cópias herdam sensibilidade dos dados; métricas em grupos pequenos podem reidentificar; perda de chaves pode impedir recuperação | Destino, criptografia, acesso, prazo, expurgo e RPO/RTO precisam de aprovação da TI |

## Hipóteses para análise institucional

Este inventário não escolhe uma hipótese legal para cada serviço: a finalidade acadêmica concreta, competência institucional, necessidade de dado sensível e base aplicável precisam ser avaliadas pelo encarregado e áreas responsáveis. O princípio de necessidade deve ser aplicado campo a campo; CPF, RG, assinatura, atestado ou dados familiares não devem ser pedidos universalmente. A ciência de um aviso não substitui automaticamente a definição de base legal.

## Regras técnicas atuais e limites

- Anexos acadêmicos ficam em storage privado e sua abertura passa por autorização do servidor. O E2E de envio com arquivo e recuperação do detalhe usa um SQLite e storage isolados fictícios.
- O histórico de atendimento e a auditoria restrita são trilhas diferentes. A aplicação e triggers do SQLite/MySQL bloqueiam operações normais de atualização/exclusão e guardam resultado/correlação. Um operador privilegiado ainda pode alterar triggers/schema ou restaurar o banco; não é imutável contra administrador da infraestrutura nem substitui auditoria institucional.
- Não existe política de retenção aprovada, job de eliminação ou exportação automática de dados de titular. O fluxo técnico de solicitação de privacidade não deve prometer apagar prova de processo acadêmico.
- MFA para equipe, SSO institucional e scanner antimalware não estão ativados; dependem da arquitetura, contratos e serviço aprovados pela TI.
- Backups precisam cobrir banco, arquivos privados e chaves/configuração protegidas. Cópia de banco sozinha não recupera anexos.

## Fontes oficiais consultadas

- Lei nº 13.709/2018 (texto compilado), Planalto: https://www.planalto.gov.br/ccivil_03/_ato2015-2018/2018/lei/l13709compilado.htm
- Resolução CONSUP/IFPE nº 124/2022 e legislação interna de proteção de dados, página oficial do IFPE: https://portal.ifpe.edu.br/acesso-a-informacao/protecao-de-dados-lgpd/legislacao-interna/
- PDF oficial da Resolução nº 124/2022: https://portal.ifpe.edu.br/wp-content/uploads/repositoriolegado/portal/documentos/resolucao-124-2022-aprova-a-politica-geral-de-protecao-de-dados-pessoais-do-ifpe.pdf

Essas fontes confirmam a existência da LGPD e da política geral institucional, mas não aprovam este inventário, os prazos, o aviso abaixo nem as configurações do Chat Request. A área competente deve revisar a versão vigente das normas antes de homologar o produto.

**Conferência das fontes:** URLs oficiais abertas novamente em 26/10/2026. A página de legislação interna do IFPE também lista o Programa de Governança em Privacidade e o Aviso de Privacidade institucional; use esses documentos na revisão, sem copiar canais ou bases para o sistema sem aprovação específica.
