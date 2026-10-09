<?php

namespace App\Domain\Events;

use RuntimeException;
use ZipArchive;

final class ReadCostWorkbook
{
    public function fromPath(string $path): CostWorkbook
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new RuntimeException('Não foi possível abrir a planilha.');
        }

        $strings = $this->sharedStrings($zip);
        $costs = $this->grid($zip, 'xl/worksheets/sheet1.xml', $strings);
        $sales = $this->grid($zip, 'xl/worksheets/sheet2.xml', $strings);
        $zip->close();

        return new CostWorkbook(
            name: $this->eventName($costs),
            startsOn: $this->startsOn($costs),
            venue: $this->venue($costs),
            capacity: $this->labeledInt($sales, 'Publico Total'),
            barExpected: $this->cents((string) ($this->labeledNumber($sales, 'Faturamento Bar') ?? 0)),
            barNotes: $this->barNotes($sales),
            courtesyLists: $this->courtesyLists($sales),
            lines: $this->costLines($costs),
            tiers: $this->tiers($sales),
        );
    }

    /**
     * @return list<string>
     */
    private function sharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');

        if ($xml === false) {
            return [];
        }

        $strings = [];
        $document = $this->document($xml);

        foreach ($document->getElementsByTagName('si') as $item) {
            $text = '';

            foreach ($item->getElementsByTagName('t') as $node) {
                $text .= $node->textContent;
            }

            $strings[] = $text;
        }

        return $strings;
    }

    /**
     * @param  list<string>  $strings
     * @return array<int, array<int, string>>
     */
    private function grid(ZipArchive $zip, string $name, array $strings): array
    {
        $xml = $zip->getFromName($name);

        if ($xml === false) {
            throw new RuntimeException('A planilha não tem a aba esperada.');
        }

        $rows = [];
        $document = $this->document($xml);

        foreach ($document->getElementsByTagName('c') as $cell) {
            $ref = $cell->getAttribute('r');

            if ($ref === '') {
                continue;
            }

            [$column, $row] = $this->coordinate($ref);
            $rows[$row][$column] = $this->cellValue($cell, $strings);
        }

        return $rows;
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     */
    private function eventName(array $rows): string
    {
        foreach ($rows as $row) {
            $value = trim($row[2] ?? '');

            if ($value !== '') {
                return $value;
            }
        }

        return 'Evento';
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     */
    private function startsOn(array $rows): ?string
    {
        foreach ($rows as $row) {
            if (preg_match('/(\d{2})\.(\d{2})\.(\d{4})/', $row[2] ?? '', $matches) === 1) {
                return $matches[3].'-'.$matches[2].'-'.$matches[1];
            }
        }

        return null;
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     */
    private function venue(array $rows): ?string
    {
        foreach ($rows as $row) {
            if (preg_match('/\d{4}\s*-\s*(.+)$/', trim($row[2] ?? ''), $matches) === 1) {
                $venue = trim($matches[1]);

                return $venue !== '' ? $venue : null;
            }
        }

        return null;
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     * @return list<array{section: string, item: string, detail: string, quantity: int, unit: int, total: int, responsible: string, status: string, pix: string, invoice: string, invoiceUrl: string, comment: string}>
     */
    private function costLines(array $rows): array
    {
        $lines = [];
        $section = 'outros';
        $columns = $this->defaultCostColumns();

        foreach ($rows as $row) {
            $lead = trim($row[1] ?? '');

            if ($lead === '') {
                continue;
            }

            if ($this->sectionKey($lead) !== null) {
                $section = $this->sectionKey($lead);

                continue;
            }

            if (in_array('item', array_map(fn (string $value): string => mb_strtolower(trim($value)), $row), true)) {
                $columns = $this->costColumns($row);

                continue;
            }

            if (! is_numeric($lead) || trim($row[$columns['item']] ?? '') === '') {
                continue;
            }

            $lines[] = [
                'section' => $section,
                'item' => trim($row[$columns['item']] ?? ''),
                'detail' => $this->cell($row, $columns['detail']),
                'quantity' => (int) round((float) $this->cell($row, $columns['quantity'])),
                'unit' => $this->cents($this->cell($row, $columns['unit'])),
                'total' => $this->cents($this->cell($row, $columns['total'])),
                'responsible' => $this->cell($row, $columns['responsible']),
                'status' => $this->cell($row, $columns['status']),
                'pix' => $this->cell($row, $columns['pix']),
                'invoice' => $this->cell($row, $columns['invoice']),
                'invoiceUrl' => $this->cell($row, $columns['invoiceUrl']),
                'comment' => $this->cell($row, $columns['comment']),
            ];
        }

        return $lines;
    }

    /**
     * @param  array<int, string>  $row
     * @return array{item: int, detail: int, quantity: int, unit: int, total: int, responsible: int, status: int, pix: int, invoice: int, invoiceUrl: int, comment: int}
     */
    private function costColumns(array $row): array
    {
        $columns = $this->defaultCostColumns();

        foreach ($row as $index => $label) {
            $key = $this->headerKey($label);

            if ($key !== '' && array_key_exists($key, $columns)) {
                $columns[$key] = $index;
            }
        }

        return $columns;
    }

    /**
     * @return array{item: int, detail: int, quantity: int, unit: int, total: int, responsible: int, status: int, pix: int, invoice: int, invoiceUrl: int, comment: int}
     */
    private function defaultCostColumns(): array
    {
        return [
            'item' => 2,
            'detail' => 3,
            'quantity' => 4,
            'unit' => 5,
            'total' => 6,
            'responsible' => 7,
            'status' => 8,
            'pix' => 0,
            'invoice' => 0,
            'invoiceUrl' => 0,
            'comment' => 0,
        ];
    }

    private function headerKey(string $label): string
    {
        $text = mb_strtolower(trim($label));

        return match (true) {
            str_starts_with($text, 'item') => 'item',
            str_contains($text, 'tipo') => 'detail',
            $text === 'qtd' => 'quantity',
            str_starts_with($text, 'unit') => 'unit',
            str_starts_with($text, 'total') => 'total',
            str_contains($text, 'respons') => 'responsible',
            str_contains($text, 'status'), str_contains($text, 'estatuto') => 'status',
            str_contains($text, 'pix') => 'pix',
            $text === 'nf' => 'invoice',
            str_contains($text, 'link') => 'invoiceUrl',
            str_contains($text, 'comment') => 'comment',
            default => '',
        };
    }

    private function sectionKey(string $title): ?string
    {
        $text = $this->fold($title);

        foreach ([
            'artistico' => 'artistico',
            'staff' => 'staff',
            'compras' => 'compras',
            'equipamentos' => 'equipamentos',
            'servicos' => 'servicos',
            'material' => 'material',
            'divulgacao' => 'divulgacao',
            'bebidas' => 'bebidas',
            'logistica' => 'logistica',
        ] as $needle => $key) {
            if (str_contains($text, $needle)) {
                return $key;
            }
        }

        return null;
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     * @return list<array{name: string, price: int, quantity: int, payoutBasisPoints: int}>
     */
    private function tiers(array $rows): array
    {
        $tiers = [];
        $reading = false;

        foreach ($rows as $row) {
            $name = trim($row[2] ?? '');

            if (mb_strtolower($name) === 'ingresso') {
                $reading = true;

                continue;
            }

            if (! $reading || $name === '' || mb_strtolower($name) === 'totais') {
                if ($reading && mb_strtolower($name) === 'totais') {
                    break;
                }

                continue;
            }

            $total = $this->cents($row[5] ?? '');
            $net = $this->cents($row[6] ?? '');

            $tiers[] = [
                'name' => $name,
                'price' => $this->cents($row[3] ?? ''),
                'quantity' => (int) round((float) ($row[4] ?? 0)),
                'payoutBasisPoints' => $this->payout($name, $total, $net),
            ];
        }

        return $tiers;
    }

    private function payout(string $name, int $total, int $net): int
    {
        if ($total > 0) {
            return $net >= $total ? 10000 : (int) round($net * 10000 / $total);
        }

        $text = mb_strtolower(trim($name));

        if ($text === 'porta' || str_contains($text, 'promocional')) {
            return 10000;
        }

        return 9000;
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     */
    private function labeledNumber(array $rows, string $label): ?float
    {
        $wanted = $this->fold($label);

        foreach ($rows as $row) {
            foreach ($row as $column => $value) {
                if ($this->fold($value) !== $wanted) {
                    continue;
                }

                $number = $row[$column + 1] ?? '';

                if (is_numeric($number)) {
                    return (float) $number;
                }
            }
        }

        return null;
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     */
    private function labeledInt(array $rows, string $label): ?int
    {
        $number = $this->labeledNumber($rows, $label);

        return $number === null ? null : (int) round($number);
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     */
    private function barNotes(array $rows): string
    {
        $public = $this->labeledInt($rows, 'Publico Total');
        $average = $this->labeledNumber($rows, 'Ticket Medio');
        $vips = $this->labeledInt($rows, 'No VIPs');
        $parts = [];

        if ($public !== null && $average !== null) {
            $parts[] = $public.' pessoas × R$ '.rtrim(rtrim(number_format($average, 2, ',', '.'), '0'), ',');
        }

        if ($vips !== null) {
            $parts[] = $vips.' VIPs na hipótese';
        }

        return implode('. ', $parts);
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     * @return list<string>
     */
    private function courtesyLists(array $rows): array
    {
        $lists = [];
        $collecting = false;

        foreach ($rows as $row) {
            $label = trim($row[11] ?? '');

            if ($this->fold($label) === 'vips') {
                $collecting = true;

                continue;
            }

            if (! $collecting || $label === '') {
                continue;
            }

            if ($this->fold($label) === 'total') {
                return $lists;
            }

            $lists[] = $label;
        }

        return $lists;
    }

    /**
     * @param  array<int, string>  $row
     */
    private function cell(array $row, int $column): string
    {
        if ($column < 1) {
            return '';
        }

        return trim($row[$column] ?? '');
    }

    private function cents(string $raw): int
    {
        if (trim($raw) === '' || ! is_numeric($raw)) {
            return 0;
        }

        return (int) round(((float) $raw) * 100);
    }

    private function fold(string $value): string
    {
        $text = mb_strtolower(trim($value));

        return strtr($text, [
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a',
            'é' => 'e', 'ê' => 'e',
            'í' => 'i',
            'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ú' => 'u',
            'ç' => 'c',
        ]);
    }

    /**
     * @param  list<string>  $strings
     */
    private function cellValue(\DOMElement $cell, array $strings): string
    {
        if ($cell->getAttribute('t') === 's') {
            $index = (int) $cell->getElementsByTagName('v')->item(0)?->textContent;

            return $strings[$index] ?? '';
        }

        if ($cell->getAttribute('t') === 'inlineStr') {
            return $cell->getElementsByTagName('t')->item(0)?->textContent ?? '';
        }

        return $cell->getElementsByTagName('v')->item(0)?->textContent ?? '';
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function coordinate(string $ref): array
    {
        preg_match('/([A-Z]+)(\d+)/', $ref, $matches);
        $column = 0;

        foreach (str_split($matches[1]) as $letter) {
            $column = ($column * 26) + (ord($letter) - 64);
        }

        return [$column, (int) $matches[2]];
    }

    private function document(string $xml): \DOMDocument
    {
        $document = new \DOMDocument;
        $document->loadXML($xml);

        return $document;
    }
}
