# Relatório de QA do Front-end

## Resultado

APROVADO

## Escopo analisado

- Branch: `codex/response-templates` (working tree com alterações locais da Fase 6)
- Commit ou diff: notificações internas — sino, contador, lista, leitura individual/em lote e preferências.
- Data: 05/09/2026
- Mudanças visíveis esperadas: sino de notificações autenticado, contador individual, painel com estado vazio/lista e ações de leitura.

## Ambiente

- Front-end: `http://127.0.0.1:3000` (HTTP 200)
- Back-end: `http://127.0.0.1:8000/api` (HTTP 200)
- Banco: instância local QA. Foram executadas somente as ações funcionais solicitadas nas contas fictícias (leitura e preferência); a preferência foi restaurada ao valor original.
- Navegador: Google Chrome local, controlado por Playwright. O navegador integrado não estava disponível nesta retomada.
- Viewports: desktop padrão e mobile 390 × 844.

## Fluxos executados

| Fluxo | Perfil | Resultado | Evidência |
|---|---|---|---|
| Renderização do login | Público | Aprovado | Página `/login` renderizou em desktop e mobile; controles e texto visíveis. |
| Validação de credencial inválida | Público | Aprovado | Mensagem de credenciais inválidas apresentada após envio de dados fictícios inválidos. |
| Autenticação de aluno QA | Aluno | Aprovado | Perfil fictício autenticou e abriu `/me`. |
| Autenticação administrativa QA | Admin | Aprovado | Perfil fictício autenticou e abriu `/dashboard/admin`. |
| Painel de notificações vazio | Admin | Aprovado em desktop | Sino abre painel com “Nenhuma notificação.”, preferências e ação “Marcar todas”; as leituras da API retornaram 200. |
| Lista, contador e responsividade | Aluno | Aprovado em desktop e mobile | Duas notificações QA e badge `2` renderizaram; em 390 × 844 o painel ficou integralmente na viewport (x=58, largura=320). |
| Leitura individual e em lote | Aluno | Aprovado | Ação individual seguida de “Marcar todas” deixou `GET /count` em `{"unread":0}` e removeu o badge, preservando os itens no histórico. |
| Preferências e persistência | Aluno | Aprovado | Modal visual abriu; a preferência de e-mail foi alterada, persistiu após reload e foi restaurada ao valor original; `PUT` retornou 200. |
| Rotas de notificações sem sessão | Público | Aprovado | `GET /notifications`, `GET /notifications/count` e `POST /notifications/read-all` retornaram 401. |

## Console e rede

- Erros de console: nenhum erro observado nas telas autenticadas na rodada final.
- Avisos: aviso de performance preexistente do Next Image para `/mascote.png` sem `sizes`.
- Falhas de rede: nenhuma na rodada final. `grid-pattern.svg`, listagem, contador e preferências retornaram 200; chamadas sem credencial às rotas protegidas retornam 401, como esperado.
- Requisições inesperadas: nenhuma no navegador. O log do servidor de desenvolvimento, porém, registra repetidamente `Watchpack Error ... EISDIR` ao observar o caminho UNC/WSL.

## Defeitos encontrados

Nenhum defeito bloqueador reproduzido na rodada final. Os defeitos QA-001 (pop-over mobile) e QA-002 (`grid-pattern.svg` ausente) foram retestados e não se repetiram.

## Testes não executados

- Teste: envio real de e-mail, criação por evento de negócio e paginação acima de cinco itens.
- Motivo: permanecem fora do escopo desta validação visual e exigiriam geração adicional de dados/eventos.
- Impacto: risco residual baixo; o front atual mostrou corretamente lista populada de duas notificações, contador, leitura e preferências.

## Conclusão

- Resultado final: **APROVADO**.
- Bloqueios: nenhum para a Fase 6.
- Riscos restantes: avisos do Watchpack relacionados ao caminho UNC/WSL permanecem no ambiente de desenvolvimento e devem ser tratados separadamente caso prejudiquem hot reload.

---

# Rodada de QA — Fase 7: formulários dinâmicos e rascunhos

## Resultado

**APROVADO**

## Ambiente e perfis

- Front-end: `http://127.0.0.1:3000`.
- API: `http://127.0.0.1:8000/api`.
- Perfil: aluno QA fictício.
- Navegador: Google Chrome local, controlado por Playwright.
- Viewports: desktop 1280 × 720 e mobile 390 × 844.

## Fluxos executados

| Fluxo | Resultado | Evidência |
|---|---|---|
| Tipos de requerimento | Aprovado | A página `/requests/new` carregou 15 opções por `GET /api/type-requests` com HTTP 200. |
| Seleção e formulário dinâmico | Aprovado | A seleção de “Ementa de Disciplina” carregou Assunto e Descrição por `GET /api/type-requests/{id}/form` com HTTP 200. |
| Rascunho no desktop | Aprovado | O estado exibiu “Rascunho salvo”; após reload, assunto e descrição fictícios permaneceram preenchidos. |
| Envio fictício | Aprovado | `POST /api/requests` retornou 201 e a interface redirecionou para a página de detalhes criada. |
| Controles no mobile | Aprovado | Em 390 × 844, seletor, campos e botão ficaram visíveis e utilizáveis; `scrollWidth` foi igual a 390, sem overflow horizontal. |
| Rascunho no mobile | Aprovado | A alteração fictícia acionou `POST /api/drafts` com 201 e exibiu “Rascunho salvo”. |

## Console, rede e evidências

- Não houve erros de console na rodada da Fase 7.
- Rotas usadas por rascunho, formulário e criação responderam com HTTP 200, 201 ou redirecionamento esperado.
- Evidências visuais: `C:\\temp\\chat-request-qa\\phase7-new-mobile.png` e `C:\\temp\\chat-request-qa\\phase7-submitted-desktop.png`.
- O envio criou somente um requerimento de conteúdo explicitamente fictício na conta QA.

## Testes não executados

- Upload obrigatório e validação de arquivos: o tipo utilizado não exigia anexo; a validação de upload pertence ao fluxo especializado já coberto em fase anterior.
- Conflito entre duas abas: não foi exercitado nesta rodada, pois exigiria manter duas edições concorrentes do mesmo rascunho QA.

## Conclusão

**Fase 7 aprovada para avanço**, considerando os fluxos visuais e funcionais solicitados.

---

# Rodada de QA — Fase 8: escopos de acesso

## Resultado

**APROVADO**

## Ambiente e perfil

- Front-end: `http://127.0.0.1:3000`.
- Perfil: administrador QA fictício.
- Navegador: Google Chrome local, controlado por Playwright.
- Viewports: desktop 1280 × 720 e mobile 390 × 844.

## Fluxos executados

| Fluxo | Resultado | Evidência |
|---|---|---|
| Carregamento da tela | Aprovado | `/dashboard/admin/access-scopes` abriu com `GET /api/staff-access-scopes` em HTTP 200. |
| Estado vazio | Aprovado | A tela informou “Nenhum escopo cadastrado.” sem falha visual. |
| Formulário | Aprovado | Campos de usuário, setor, curso, expiração e motivo ficaram visíveis e aceitaram dados fictícios sem envio. |
| Capacidades | Aprovado | Seis checkboxes renderizaram; `view_request` iniciou marcado e `reply_request` respondeu ao clique nos dois viewports. |
| Responsividade | Aprovado | Desktop e mobile ficaram sem overflow horizontal (`scrollWidth` igual à largura da viewport). |

## Console, rede e evidências

- Nenhum erro de console ou falha de rede observado.
- Não foi enviado nem revogado escopo: o teste limitou-se à interação visual do formulário, preservando a base QA.
- Evidências: `C:\\temp\\chat-request-qa\\phase8-access-scopes-desktop.png` e `C:\\temp\\chat-request-qa\\phase8-access-scopes-mobile.png`.

## Conclusão

**Fase 8 aprovada para avanço** quanto às mudanças visuais solicitadas.
