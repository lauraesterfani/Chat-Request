# Fase 8 — Permissões por campus, curso e setor

## Estado técnico

O núcleo de concessões temporárias e escopos está implementado. As autorizações são avaliadas no servidor e a capacidade precisa pertencer à mesma concessão que corresponde ao curso, campus e setor do requerimento. Escopos expirados ou revogados não concedem acesso; concessões permanecem como restrição explícita para evitar que uma conta volte a receber acesso global automaticamente.

## Evidências verificadas

- `php artisan test --filter=StaffAccessScopeTest`: **14 testes passaram, 99 assertions**. A sequência de API agora comprova acesso negado antes da concessão, acesso à listagem e ao detalhe durante a concessão, e bloqueio da listagem e do detalhe após revogação.
- `php artisan test`: **173 testes passaram, 1.252 assertions** na rodada de 26/10/2026.
- `scripts/run-phase8-access-e2e.sh`: **2/2 passaram** em Chromium desktop e mobile contra Laravel, banco SQLite e armazenamento temporário isolados. O administrador concede pela UI uma permissão com início agendado; a pessoa atendente vê apenas o requerimento do campus autorizado, abre seu detalhe, recebe 403 ao tentar abrir o requerimento do outro campus e, após a revogação pela UI e recarga, a fila fica vazia e o detalhe volta a responder 403.
- A revisão visual identificou e corrigiu duas falhas: mensagens de sucesso eram apagadas pelo recarregamento da lista; a fila tinha colunas sobrepostas em mobile. As mensagens agora permanecem após concessão/revogação; no celular a fila apresenta cartões legíveis e estado vazio.
- `npx tsc --noEmit --incremental false`: passou. ESLint direcionado aos arquivos alterados terminou sem erros; manteve um aviso já apontado pela regra React no `useEffect` de carregamento inicial da tela de concessões. `vendor/bin/pint --test` passou para os arquivos PHP da fase; `git diff --check` passou.
- Capturas pós-correção: `/tmp/chat-request-phase8-api-queue-chromium-desktop.png`, `/tmp/chat-request-phase8-api-queue-chromium-mobile.png` e `/tmp/chat-request-phase8-api-chromium-mobile.png` (estado sem pedidos após a revogação).
- A cobertura também inclui limites por curso/setor e campus, coordenação limitada ao próprio curso, pedidos legados sem snapshot, início/expiração, combinação indevida de capacidade entre concessões, revisão de anexos e gestão restrita de concessões.
- `frontend/e2e/access-scopes.spec.ts` verifica a interface de gestão em navegador, porém usa respostas simuladas para a API. Ele não é evidência de uma sessão completa de ponta a ponta com API real.

## Limites e pendências institucionais

- Campi e vínculos reais não foram cadastrados. A instituição precisa aprovar a matriz de usuários, capacidades, setores, cursos e campi antes do piloto.
- Pedidos legados sem contexto acadêmico confiável devem ser revisados pelo relatório `php artisan requests:unresolved-access-contexts`; o sistema não infere campus ou curso atual para autorizar registros antigos.
- Não existe perfil de administrador local de campus. A delegação continua restrita ao administrador institucional global sem escopos.

Fase 8 está **validada tecnicamente, incluindo navegador/API real em desktop e mobile**. A implantação institucional ainda depende da matriz aprovada, dos vínculos oficiais e da revisão dos pedidos legados. A validação automatizada não substitui essa homologação nem autoriza uso em produção.
