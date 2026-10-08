<?php

namespace App\Domain\Tickets;

use App\Domain\Finance\Money;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use RuntimeException;

final class ImportSoldTicketsFromShotgun
{
    /**
     * @return list<ImportedTierSales>
     */
    public function parse(string $csvContent): array
    {
        $csvContent = trim($csvContent);

        if ($csvContent === '') {
            throw new InvalidArgumentException('O arquivo CSV está vazio.');
        }

        $handle = fopen('php://memory', 'r+');

        if ($handle === false) {
            throw new RuntimeException('Não foi possível ler o arquivo.');
        }

        fwrite($handle, $csvContent);
        rewind($handle);

        $headers = fgetcsv($handle);

        if ($headers === false) {
            fclose($handle);

            throw new InvalidArgumentException('Cabeçalho do CSV inválido.');
        }

        $map = $this->headerMap($headers);
        $required = ['deal_title', 'status'];

        foreach ($required as $key) {
            if (! array_key_exists($key, $map)) {
                fclose($handle);

                throw new InvalidArgumentException('CSV Shotgun incompleto: falta a coluna '.strtoupper(str_replace('_', ' ', $key)).'.');
            }
        }

        /** @var array<string, array{sold: int, price_cents: int, starts_at: ?Carbon, ends_at: ?Carbon}> $buckets */
        $buckets = [];

        while (($row = fgetcsv($handle)) !== false) {
            if ($this->rowIsEmpty($row)) {
                continue;
            }

            $data = $this->rowAssoc($map, $row);

            if (! $this->isCountableSale($data)) {
                continue;
            }

            $name = trim($data['deal_title']);

            if ($name === '') {
                continue;
            }

            $key = mb_strtolower($name);

            if (! isset($buckets[$key])) {
                $buckets[$key] = [
                    'name' => $name,
                    'sold' => 0,
                    'price_cents' => $this->priceCentsFromRow($data),
                    'starts_at' => $this->parseDateTime($data['start'] ?? null),
                    'ends_at' => $this->parseDateTime($data['end'] ?? null),
                ];
            }

            $buckets[$key]['sold']++;

            $rowPrice = $this->priceCentsFromRow($data);

            if ($rowPrice > 0) {
                $buckets[$key]['price_cents'] = $rowPrice;
            }
        }

        fclose($handle);

        if ($buckets === []) {
            throw new InvalidArgumentException('Nenhum ingresso válido encontrado no CSV.');
        }

        $imports = [];

        foreach ($buckets as $bucket) {
            $imports[] = new ImportedTierSales(
                name: $bucket['name'],
                soldCount: $bucket['sold'],
                priceCents: $bucket['price_cents'],
                startsAt: $bucket['starts_at'],
                endsAt: $bucket['ends_at'],
            );
        }

        usort($imports, fn (ImportedTierSales $a, ImportedTierSales $b): int => strcasecmp($a->name, $b->name));

        return $imports;
    }

    /**
     * @param  list<string|null>  $headers
     * @return array<string, int>
     */
    private function headerMap(array $headers): array
    {
        $map = [];

        foreach ($headers as $index => $header) {
            $normalized = mb_strtolower(trim((string) $header));
            $normalized = str_replace(' ', '_', $normalized);
            $map[$normalized] = $index;
        }

        return $map;
    }

    /**
     * @param  array<string, int>  $map
     * @param  list<string|null>  $row
     * @return array<string, string>
     */
    private function rowAssoc(array $map, array $row): array
    {
        $data = [];

        foreach ($map as $key => $index) {
            $data[$key] = trim((string) ($row[$index] ?? ''));
        }

        return $data;
    }

    /**
     * @param  list<string|null>  $row
     */
    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, string>  $data
     */
    private function isCountableSale(array $data): bool
    {
        if (($data['refund_date'] ?? '') !== '') {
            return false;
        }

        $status = mb_strtolower($data['status']);

        return in_array($status, ['valid', 'valido', 'válido', 'paid', 'confirmed'], true);
    }

    /**
     * @param  array<string, string>  $data
     */
    private function priceCentsFromRow(array $data): int
    {
        $client = trim($data['client_price'] ?? '');
        $price = trim($data['price'] ?? '');

        if ($client !== '' && $client !== '0') {
            return Money::parse($client);
        }

        if ($price !== '' && $price !== '0') {
            return Money::parse($price);
        }

        return 0;
    }

    private function parseDateTime(?string $value): ?Carbon
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
