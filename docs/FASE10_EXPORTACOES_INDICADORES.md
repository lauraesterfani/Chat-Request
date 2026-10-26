# Exportações de indicadores — contrato e validação

## Estado

O módulo isolado `MetricExportController` + `MetricExportService` oferece geração síncrona de XLSX e PDF a partir do mesmo conjunto de filtros do endpoint atual de métricas: `from`, `to`, `course_id`, `campus_id`, `type_id`, `sector` e `status`.

O `RequestAccessService` restringe a consulta antes de calcular qualquer agregado. Exportar e baixar requer um administrador/CRADT ativo com capacidade efetiva `view_request`. O download só pode ser feito pelo autor, exige que a capacidade permaneça ativa e recalcula o relatório usando o escopo vigente antes de responder.

Os arquivos e metadados temporários ficam no disco `local` (raiz privada em `storage/app/private`), expiram após 20 minutos e são removidos ao baixar ou durante a limpeza oportunista de artefatos vencidos. Os eventos de solicitação e download registram somente UUID do artefato, formato e nomes dos filtros; não registram conteúdo nem valores de filtro.

Cada relatório contém contagens sintéticas, coortes separadas de abertura, encerramento e estoque atual, distribuição por serviço/campus/curso/setor da coorte aberta no período, filtros legíveis, instante de geração em UTC, definições e limitações. API e exportação testam igualdade de seus dados agregados. Não inclui identificadores de alunos, matrícula, protocolo, assunto, descrição, comentário ou conteúdo de requerimentos. A geração síncrona rejeita mais de 5.000 requerimentos com HTTP 422 e orientação para restringir os filtros.

## Formatos e dependências

O projeto não possui PhpSpreadsheet, Dompdf ou outra biblioteca de geração de relatórios instalada no `composer.lock`. A extensão `ZipArchive` e `XMLWriter` estão disponíveis neste ambiente. A documentação oficial de PhpSpreadsheet declara compatibilidade com PHP 8.2, mas a instalação requer também extensões como `ext-gd`; ela não está presente neste PHP. Dompdf também não está instalado. Não foram alterados manifests nem adicionadas dependências nesta fatia.

O XLSX é um pacote OOXML com uma planilha e valores gravados como `inlineStr`; não há fórmulas calculadas a partir de dados externos. O PDF é um documento PDF 1.4 de uma ou mais páginas, com fonte base Helvetica/WinAnsi. Nenhum dos formatos é uma extensão ou renomeação de CSV/HTML.

Fontes oficiais consultadas:

- [Requisitos e instalação do PhpSpreadsheet](https://github.com/PHPOffice/PhpSpreadsheet/blob/master/docs/index.md)
- [Composer requirements do PhpSpreadsheet](https://github.com/PHPOffice/PhpSpreadsheet/blob/master/composer.json)
- [Requisitos do Dompdf](https://github.com/dompdf/dompdf/blob/master/composer.json)

## Rotas integradas

As rotas estão em `routes/api.php`, dentro do grupo com autenticação, senha atualizada e papel `admin,cradt` usado por `/api/metrics`:

```php
Route::post('/metrics/exports/{format}', [MetricExportController::class, 'store'])
    ->whereIn('format', ['xlsx', 'pdf']);
Route::get('/metrics/exports/{id}/download', [MetricExportController::class, 'download'])
    ->whereUuid('id');
```

Os filtros oferecidos pela UI vêm de `GET /api/metrics/filters` e são derivados do mesmo escopo de requerimentos visíveis ao usuário.

## Cobertura automatizada

`MetricExportTest` cobre pacote ZIP/OOXML XLSX, células textuais resistentes a fórmula, ausência de dados pessoais e descrições, assinatura/estrutura mínima PDF, geração e download auditados, revogação de permissão entre geração e download, ausência de capacidade `view_request`, autenticação/role/inatividade e rejeição acima do limite síncrono (**9 testes, 52 assertions**). `MetricsTest` valida também as opções de filtro limitadas por escopo (**8 testes, 57 assertions**). Playwright do painel passou **4/4** em desktop/mobile, com Axe sem violações; o restante da suíte passou 58 casos no build final.

Limitações do produto/indicador permanecem no cabeçalho do próprio arquivo: taxa de satisfação usa requerimentos concluídos ou cancelados como elegíveis e precisa de homologação institucional; primeira resposta mede presença de `first_response_at`, não tempo; não há dias úteis, pausas de SLA, percentis ou coortes. Resultados são agregados e não devem ser usados para decisões individuais.
