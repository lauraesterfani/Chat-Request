<?php

namespace App\Services;

use RuntimeException;
use ZipArchive;

class MetricExportFileRenderer
{
    /** @param array<string, mixed> $report */
    public function render(string $format, array $report): string
    {
        return match ($format) {
            'xlsx' => $this->xlsx($report),
            'pdf' => $this->pdf($report),
            default => throw new RuntimeException('Formato de exportação inválido.'),
        };
    }

    /** @param array<string, mixed> $report */
    private function xlsx(array $report): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('A extensão PHP ZipArchive é necessária para gerar XLSX.');
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'metric-export-');
        if ($tempPath === false) {
            throw new RuntimeException('Não foi possível preparar o arquivo XLSX.');
        }

        try {
            $zip = new ZipArchive;
            if ($zip->open($tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Não foi possível criar o pacote XLSX.');
            }
            $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
            $zip->addFromString('_rels/.rels', $this->rootRelationshipsXml());
            $zip->addFromString('xl/workbook.xml', $this->workbookXml());
            $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationshipsXml());
            $zip->addFromString('xl/worksheets/sheet1.xml', $this->worksheetXml($this->rows($report)));
            $zip->close();

            $contents = file_get_contents($tempPath);
            if ($contents === false) {
                throw new RuntimeException('Não foi possível ler o arquivo XLSX gerado.');
            }

            return $contents;
        } finally {
            @unlink($tempPath);
        }
    }

    /** @param array<string, mixed> $report */
    private function pdf(array $report): string
    {
        $lines = [];
        foreach ($this->rows($report) as $row) {
            $line = implode(' | ', array_filter($row, fn ($value) => $value !== ''));
            foreach ($this->wrap($line, 92) as $wrapped) {
                $lines[] = $wrapped;
            }
        }
        $pages = array_chunk($lines, 48);
        if ($pages === []) {
            $pages = [['Relatório sem dados.']];
        }

        return $this->pdfDocument($pages);
    }

    /** @param array<string, mixed> $report
     * @return array<int, array<int, string>>
     */
    private function rows(array $report): array
    {
        $summary = $report['summary'];
        $rows = [
            ['Chat Request — Indicadores sintéticos'],
            ['Gerado em (UTC)', (string) $report['generated_at']],
            ['Filtros aplicados'],
        ];
        foreach ($report['filters'] as $label => $value) {
            $rows[] = [(string) $label, (string) $value];
        }
        $rows[] = [''];
        $rows[] = ['Indicador', 'Valor'];
        $rows[] = ['Requerimentos no recorte', (string) $summary['total']];
        $rows[] = ['Abertos', (string) $summary['open']];
        $rows[] = ['Concluídos', (string) $summary['completed']];
        $rows[] = ['Cancelados', (string) $summary['canceled']];
        $rows[] = ['Com primeira resposta humana', (string) $summary['requests_with_first_human_response']];
        $rows[] = ['Elegíveis para taxa de satisfação', (string) $summary['eligible_closed_requests']];
        $rows[] = ['Respostas de satisfação', (string) $summary['satisfaction_responses']];
        $rows[] = ['Taxa de resposta de satisfação (%)', $summary['satisfaction_response_rate_percent'] === null ? 'Não disponível' : (string) $summary['satisfaction_response_rate_percent']];
        $rows[] = ['Média de satisfação (1 a 5)', $summary['average_satisfaction_rating'] === null ? 'Não disponível' : (string) $summary['average_satisfaction_rating']];
        $rows[] = ['Contagem por etapa'];
        foreach ($summary['by_status'] as $status => $count) {
            $rows[] = [(string) $status, (string) $count];
        }
        $rows[] = ['Satisfação por nota'];
        foreach ($summary['satisfaction_by_rating'] as $rating => $count) {
            $rows[] = ['Nota '.$rating, (string) $count];
        }
        $rows[] = ['Distribuições de requerimentos por coorte de abertura'];
        foreach ([
            'Serviço' => $summary['distribution']['by_service'] ?? [],
            'Campus' => $summary['distribution']['by_campus'] ?? [],
            'Curso' => $summary['distribution']['by_course'] ?? [],
            'Setor' => $summary['distribution']['by_sector'] ?? [],
        ] as $dimension => $items) {
            $rows[] = [$dimension, 'Quantidade'];
            foreach ($items as $item) {
                $rows[] = [(string) $item['label'], (string) $item['count']];
            }
        }
        $rows[] = ['Coortes separadas'];
        $rows[] = ['Abertos no período', (string) $summary['cohorts']['opened_in_period']['count']];
        $rows[] = ['Concluídos/cancelados no período por resolved_at', (string) $summary['cohorts']['resolved_in_period']['count']];
        $rows[] = ['Estoque atual no escopo', (string) $summary['cohorts']['current_stock']['count']];
        $rows[] = ['Definições e limitações'];
        foreach (array_merge($report['definitions'], $report['limitations']) as $definition) {
            $rows[] = [(string) $definition];
        }

        return $rows;
    }

    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>';
    }

    private function rootRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
    }

    private function workbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Indicadores" sheetId="1" r:id="rId1"/></sheets></workbook>';
    }

    private function workbookRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>';
    }

    /** @param array<int, array<int, string>> $rows */
    private function worksheetXml(array $rows): string
    {
        $xml = new \XMLWriter;
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('worksheet');
        $xml->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $xml->startElement('sheetData');
        foreach ($rows as $rowIndex => $cells) {
            $xml->startElement('row');
            $xml->writeAttribute('r', (string) ($rowIndex + 1));
            foreach ($cells as $columnIndex => $value) {
                $xml->startElement('c');
                $xml->writeAttribute('r', $this->columnName($columnIndex + 1).($rowIndex + 1));
                // All report values, including numbers and user-controlled filter values, are text.
                // Inline strings cannot be interpreted as formulas by spreadsheet applications.
                $xml->writeAttribute('t', 'inlineStr');
                $xml->startElement('is');
                $xml->startElement('t');
                $xml->writeAttribute('xml:space', 'preserve');
                $xml->text($this->cleanXmlText($value));
                $xml->endElement();
                $xml->endElement();
                $xml->endElement();
            }
            $xml->endElement();
        }
        $xml->endElement();
        $xml->endElement();
        $xml->endDocument();

        return $xml->outputMemory();
    }

    private function columnName(int $column): string
    {
        $name = '';
        while ($column > 0) {
            $column--;
            $name = chr(65 + ($column % 26)).$name;
            $column = intdiv($column, 26);
        }

        return $name;
    }

    private function cleanXmlText(string $value): string
    {
        return preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', $value) ?? '';
    }

    /** @return array<int, string> */
    private function wrap(string $line, int $limit): array
    {
        $words = preg_split('/\s+/u', trim($line), -1, PREG_SPLIT_NO_EMPTY) ?: [''];
        $lines = [];
        $current = '';
        foreach ($words as $word) {
            $candidate = $current === '' ? $word : $current.' '.$word;
            if (mb_strlen($candidate) > $limit && $current !== '') {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $candidate;
            }
        }
        $lines[] = $current;

        return $lines;
    }

    /** @param array<int, array<int, string>> $pages */
    private function pdfDocument(array $pages): string
    {
        $fontId = 3 + (count($pages) * 2);
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids ['.implode(' ', array_map(fn ($index) => (3 + $index * 2).' 0 R', array_keys($pages))).'] /Count '.count($pages).' >>',
        ];
        foreach ($pages as $index => $lines) {
            $pageId = 3 + $index * 2;
            $streamId = $pageId + 1;
            $commands = "BT\n/F1 10 Tf\n50 742 Td\n14 TL\n";
            foreach ($lines as $line) {
                $encoded = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $line);
                $encoded = $encoded === false ? '' : $encoded;
                $encoded = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $encoded);
                $encoded = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $encoded) ?? '';
                $commands .= '('.$encoded.") Tj\nT*\n";
            }
            $commands .= 'ET';
            $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 '.$fontId.' 0 R >> >> /Contents '.$streamId.' 0 R >>';
            $objects[$streamId] = '<< /Length '.strlen($commands).">>\nstream\n".$commands."\nendstream";
        }
        $objects[$fontId] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];
        foreach ($objects as $id => $object) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id." 0 obj\n".$object."\nendobj\n";
        }
        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        for ($id = 1; $id <= count($objects); $id++) {
            $pdf .= sprintf('%010d 00000 n ', $offsets[$id])."\n";
        }
        $pdf .= 'trailer << /Size '.(count($objects) + 1).' /Root 1 0 R >>'."\nstartxref\n".$xrefOffset."\n%%EOF\n";

        return $pdf;
    }
}
