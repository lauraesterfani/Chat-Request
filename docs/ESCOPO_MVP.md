# Escopo do MVP do Chat Request

## Objetivo

Este MVP serve para demonstrações e validação funcional com contas e dados fictícios em ambiente local ou de teste. Ele não representa uma liberação para uso com dados reais de alunos nem uma implantação institucional.

## Incluído no MVP técnico

- Login de alunos e equipe com contas provisionadas; não há autocadastro de alunos.
- Criação, consulta e acompanhamento de requerimentos, com protocolo.
- Anexos privados e acesso protegido conforme o papel e o escopo do usuário.
- Histórico e chat do requerimento, respostas pré-configuradas por setor e decisões.
- Formulários, rascunhos e prevenção de envios duplicados.
- Permissões por papel, curso, campus e setor conforme os vínculos sintéticos configurados.
- Notificações internas, catálogo, orientações, indicadores e exportações agregadas disponíveis no código.
- Dados de teste criados por seeders apenas nos ambientes `local` e `testing`.

Use somente as contas fictícias de desenvolvimento descritas no README. Não importe listas reais de matrícula, documentos, atestados ou conversas.

## Validação observada

- Laravel: **183 testes e 1.347 assertions** passaram em SQLite isolado em 26/10/2026.
- Fluxo principal de requerimentos: **16 testes e 106 assertions** passaram.
- TypeScript: `npx tsc --noEmit` passou.
- Pint: os 20 arquivos PHP tocados na validação da Fase 9 passaram na verificação direcionada; a verificação global ainda encontra 48 problemas de estilo em 209 arquivos.
- Playwright e QA visual por fase estão registrados nos documentos de validação das Fases 9–12. Alguns cenários usam APIs simuladas e seis testes de browser foram ignorados por dependências externas; confira `docs/FASE_12_VALIDACAO.md`.
- O ensaio de recuperação local passou com fixtures sintéticas. Ele não valida backup de produção.

## Fora do MVP local

- Produção, piloto com alunos reais ou uso de documentos reais.
- SSO/SUAP, MFA, SMTP real, WhatsApp e integrações institucionais.
- Banco MySQL de homologação, alta disponibilidade, monitoramento e metas de capacidade.
- Scanner antimalware, política de retenção aprovada, descarte automático e restauração de backup real.
- Catálogo institucional homologado com todos os serviços, calendário acadêmico oficial e matriz real de acessos.
- Aceite jurídico/institucional ou declaração de conformidade LGPD.

Esses itens não impedem uma demonstração local controlada, mas são necessários antes de operar o sistema com dados e usuários reais.

## Como reconhecer uma validação do MVP

1. Preparar `.env` local com segredo próprio e banco SQLite descartável.
2. Executar migrations e seeders em ambiente local; não usar banco real.
3. Iniciar Laravel e Next.js conforme README.
4. Entrar com uma conta fictícia, criar requerimento e anexar arquivo sintético.
5. Entrar como equipe autorizada, abrir o mesmo pedido, responder/encaminhar e tomar decisão conforme os fluxos disponíveis.
6. Confirmar que outra conta/curso não consegue abrir pedido ou documento fora do próprio escopo.
7. Verificar a suíte automatizada antes de aceitar uma mudança.

O estado atual é **candidato a MVP local, com regressão automatizada aprovada**. A aceitação final de demonstração depende de executar os fluxos no ambiente local do operador; a validação institucional permanece separada e não é requisito para este MVP.
