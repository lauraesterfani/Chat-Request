# Matriz GO NO GO para lançamento

Esta matriz registra prontidão técnica local. Não substitui autorização do IFPE e não autoriza produção.

| Critério | Evidência local | Estado | Responsável para decisão | Bloqueio para produção |
| --- | --- | --- | --- | --- |
| Criação, protocolo, chat e anexos | testes de feature | técnico local | equipe do projeto | homologação de serviços e regras |
| Autorização e escopos | testes de escopo e auditoria | técnico local | CRADT e TI | matriz institucional de perfis |
| Primeiro acesso do aluno | `StudentAuthenticationTest` e QA | técnico local | TI institucional | processo de importação e senha inicial |
| Notificações | testes locais e QA do sino | técnico local | CRADT e TI | SMTP, e-mail verificado e operação de fila |
| Formulários, catálogo e janelas | testes locais | parcial | CRADT | conteúdo, calendários e fontes aprovadas |
| Satisfação e métricas | testes locais | técnico local | gestão | definição de uso, público e amostra |
| Base de conhecimento | testes locais | técnico local | responsáveis editoriais | textos e referências aprovados |
| Banco, backups e recuperação | runbook disponível | pendente | TI institucional | ensaio de restauração e RPO/RTO aprovados |
| Segurança e privacidade | inventário e auditoria local | pendente | encarregado/TI | avaliação institucional e retenção |
| Acessibilidade | QA pontual desktop/mobile | parcial | responsáveis do projeto | auditoria WCAG/manual ampliada |
| Integrações institucionais | não implementadas por falta de contrato | pendente | TI/IFPE | acesso, contrato e homologação |
| CI | workflow versionado | pronto para ativação | mantenedor do repositório | execução remota e branch protection |

## Condições de NO GO

O lançamento deve ser bloqueado se houver acesso indevido a requerimentos ou documentos, protocolo duplicado, exposição pública de anexos, decisão incorreta, migração sem backup aprovado, falha de recuperação não analisada ou integração obrigatória sem contrato homologado.

## Pré checagem de publicação

1. Identificar commit e ambiente exatos.
2. Confirmar backup e procedimento de retorno aprovados.
3. Conferir migrations pendentes sem usar comandos destrutivos.
4. Executar testes, build e smoke tests no ambiente autorizado.
5. Confirmar worker, scheduler, fila, armazenamento privado e e-mail capturado/homologado.
6. Registrar decisão GO ou NO GO por responsáveis institucionais.

## Itens explicitamente pendentes

- Homologação de catálogo, janelas, documentos alternativos e regras acadêmicas.
- SSO, SUAP e qualquer integração externa.
- Dados reais, convites, e-mails reais, deploy, DNS e alterações de permissões remotas.
- Aceite de acessibilidade, privacidade, retenção, backup e piloto.
