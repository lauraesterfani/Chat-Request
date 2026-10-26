# Validação das métricas da Fase 10

## Escopo desta entrega

Esta validação cobre o contrato agregado, a gravação de satisfação, filtros com escopo, a tela administrativa de métricas e as exportações XLSX/PDF protegidas. Ela não declara a Fase 10 completa nem substitui a homologação institucional dos indicadores.

## Contrato de `GET /api/metrics`

A rota existente aplica primeiro o escopo efetivo de leitura do atendente e só então os filtros. Os filtros opcionais aceitos são `from`, `to` (`AAAA-MM-DD`, em UTC), `course_id`, `campus_id`, `type_id`, `sector` (`CRADT` ou `COORDENACAO`) e `status`.

O intervalo é baseado em `requests.created_at`; o primeiro dia começa à meia-noite UTC e o último inclui o dia inteiro. `by_status` sempre inclui todos os status conhecidos pelo enum, com zero quando não há ocorrências. A resposta informa `generated_at` e `timezone: UTC`.

O contrato mantém as chaves antigas `canceled` e `satisfaction.responses` e também fornece os aliases `cancelled` e `satisfaction.total_responses` usados pela interface atual. A taxa de resposta é calculada como respostas recebidas divididas por requerimentos encerrados no conjunto filtrado; se não houver encerrados, o resultado é `null`, não zero. Hoje a API de satisfação considera concluídos e cancelados elegíveis. Essa regra precisa de validação da instituição.

`first_response_coverage` conta apenas a presença de `first_response_at`. Não representa duração de resposta nem cumprimento de SLA.

`distribution.by_service`, `by_campus`, `by_course` e `by_sector` contam os requerimentos do mesmo conjunto filtrado e da mesma coorte de abertura. Contexto histórico ausente é apresentado como “Não informado”. A exportação XLSX/PDF usa o mesmo filtro e a mesma definição, com distribuições agregadas; não inclui protocolos ou conteúdo individual.

`GET /api/metrics/filters` expõe opções distintas de campus, curso, serviço e setor apenas a partir dos requerimentos visíveis ao atendente autenticado. Assim, filtros não revelam listas de entidades fora do escopo atual.

`cohorts` separa três conjuntos: aberturas no intervalo por `created_at`, encerramentos no intervalo por `resolved_at` e estoque atual sem aplicar o intervalo de datas. Os registros terminais legados sem `resolved_at` são contabilizados à parte e excluídos da coorte de encerramento; nenhum timestamp é corrigido em silêncio. Os filtros organizacionais e de etapa continuam aplicados a cada conjunto.

## Interface e exportações

`frontend/app/(student)/dashboard/admin/metrics/page.tsx` apresenta os sete filtros aceitos pela API, estados de carregamento/erro/vazio, cartões agregados, tabelas acessíveis por etapa, estoque atual, encerramentos, distribuições e nota de satisfação, cobertura de primeira resposta e definições/limitações informadas pelo servidor. O seletor de opções vem do endpoint com escopo; os indicadores permanecem agregados e não exibem dados pessoais.

Os botões XLSX/PDF enviam os filtros ao `POST /api/metrics/exports/{format}` e baixam o arquivo pelo `download_path` retornado, com token Bearer. Os downloads são temporários e autorizados novamente no servidor; a UI não trata uma URL de download como pública. A nova seção apresenta tabelas responsivas e semanticamente acessíveis para serviço, campus, curso e setor.

## Satisfação

O aluno só consulta ou avalia seu próprio requerimento encerrado. A avaliação continua única por requerimento; a chave única do banco é a proteção final contra envios concorrentes. Uma repetição recebe HTTP 409. Comentários não são incluídos nos agregados.

Após uma decisão final registrada pelo atendimento, o sistema cria uma notificação interna opcional para o aluno, com chave idempotente por requerimento e link para os detalhes onde a pesquisa já existe. A notificação não envia e-mail por si só e respeita as regras de preferência e verificação de endereço de e-mail. A UI identifica a categoria “Pesquisa de satisfação”.

## Verificações executadas

`php artisan test --filter='MetricsTest|MetricExportTest'`: **17 testes passaram, 128 assertions**. A cobertura inclui agregação/escopo, opções de filtros escopadas, período inclusivo, cohorts, satisfação, privacidade dos comentários, propriedade/duplicidade, pacote XLSX, PDF, autorização de exportação/download e comparação exata entre distribuições/cohorts da API e da exportação.

`php artisan test --filter='RequestFlowTest|NotificationTest'`: **22 testes e 123 assertions passaram** na rodada atual, incluindo notificação idempotente após decisão final, link para o requerimento, preferência por e-mail não verificado e supressão de notificações/e-mail depois de revogar o escopo. `frontend/e2e/notifications-satisfaction.spec.ts`: **2/2** em desktop e mobile; confirma o convite, categoria, link, filtro, alvo acessível no cabeçalho, sem overflow, sem erros de console/rede e sem violações Axe WCAG 2.1 AA. A tela de notificações ganhou um filtro nomeado para leitor de tela, indicador de não lida válido e cabeçalho compacto no celular.

`npx tsc --noEmit`: passou. `npx eslint "app/(student)/dashboard/admin/metrics/page.tsx" e2e/metrics-dashboard.spec.ts`: passou sem erros ou avisos nesses arquivos. `npx playwright test e2e/metrics-dashboard.spec.ts --workers=1`, contra `next dev` isolado: **4 testes passaram** (Chromium desktop e mobile). O cenário valida sete filtros combinados, tabelas e coortes, exportações XLSX/PDF autenticadas, erro/repetição e estado vazio; Axe WCAG 2.1 AA encontrou zero violações. Os endpoints foram simulados no navegador, portanto isso valida o contrato consumido pela UI e não substitui os testes reais dos controladores/arquivos.

As capturas da rodada estão em `frontend/test-results/metrics-dashboard-painel-d-43c22-aixa-exportação-autenticada-chromium-desktop/metrics-dashboard.png` e `frontend/test-results/metrics-dashboard-painel-d-43c22-aixa-exportação-autenticada-chromium-mobile/metrics-dashboard.png`. O agente `frontend_qa` aprovou a mudança visual em 1440×900 e 390×844: quatro tabelas de distribuição visíveis, sem overflow, zero violações Axe, sem erros de console ou requisições falhadas. O QA usou respostas de API simuladas; os testes Laravel comprovam os agregados reais e a igualdade com a exportação separadamente.

## Limitações ainda abertas na Fase 10

- O painel ainda não apresenta duração/mediana/percentis, cumprimento de SLA ou drill-down. Esses indicadores exigem definição aprovada do relógio (corridas/úteis, pausas, reaberturas) e cobertura suficiente; não foram inferidos nem inventados nesta etapa.
- A cobertura de registros legados e os períodos de edição/reabertura da pesquisa precisam de regra explícita.
- O convite por notificação foi integrado e é idempotente. Controle de taxa de resposta, políticas de edição/reabertura e incentivo da pesquisa ainda precisam de regra institucional.
- Os filtros suportam os setores codificados atualmente. A lista oficial e as métricas institucionais requerem validação da gestão.
- Não foram definidos prazos, metas, regras de desempenho individual ou critérios de anonimato.
- Ainda faltam testes manuais de teclado, zoom e leitor de tela; Axe e revisão visual em Chromium não substituem validação assistiva.
