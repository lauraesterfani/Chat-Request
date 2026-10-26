# Fase 11 — Validação da base de conhecimento administrativa

## Escopo entregue nesta fatia

- Listagem administrativa paginada de artigos, com busca e filtros por situação, categoria e público. O endpoint está no grupo protegido por autenticação da equipe e papéis `admin`/`cradt`; artigos internos, rascunhos e itens em revisão não são incluídos na busca pública.
- Formulário administrativo para criar e revisar artigos como rascunho ou em revisão, publicar por ação explícita e desativar um artigo. O formulário aceita texto simples; conteúdo HTML não é executado.
- A publicação exige envio prévio para revisão e confirmação de um administrador global ativo sem escopos. Fonte/referência é texto informativo e não prova aprovação institucional.
- O atalho para a tela está disponível no menu administrativo.

## Evidências automatizadas

Execute no ambiente local com o banco de testes configurado:

```sh
php artisan test --filter=KnowledgeArticleTest
cd frontend
npx playwright test e2e/knowledge-admin.spec.ts --workers=1
npx tsc --noEmit
npm run build
```

O teste de API cobre separação de público/autenticado/interno nas rotas reais, criação em rascunho, sequência de revisão, publicação apenas por administrador global ativo sem escopos, snapshots preservados e bloqueio de conteúdo legado sem revisão. O teste de navegador cobre o fluxo editorial, confirmação, versões publicadas e pendentes, largura desktop/mobile, erros de console/rede e varredura automatizada Axe WCAG 2.1 AA. Axe não substitui revisão manual de teclado e leitor de tela.

## Limites e validação pendente

- Esta entrega não completa toda a Fase 11. Catálogo dos 33 serviços, sua proveniência, disponibilidade e calendário permanecem fora desta fatia.
- A migration aditiva cria versões append-only com autor, ator, revisor, referência informada, estado e horário. Retificação formal, autoria/revisão periódica, fontes controladas, associação a serviço/campo/template e governança por setor ainda exigem desenho institucional.
- A rota do feed autenticado está protegida por autenticação/perfil de aluno, mas ainda não existe uma tela da experiência do aluno que consuma esse conteúdo. O catálogo público permanece isolado e não deve consultar esse método; conteúdos internos nunca entram nesses feeds.
- A listagem e edição continuam disponíveis para `admin` e `cradt`; apenas admin global ativo sem escopos pode publicar/desativar. Não há escopo editorial por curso/setor. A confirmação registra a ação do administrador, mas não verifica nem prova aprovação institucional. Não cadastre orientações reais sem validação formal do setor responsável.
- A revisão manual por teclado e leitor de tela e inspeção visual por `frontend_qa` devem ser registradas antes de considerar esta interface validada visualmente.
