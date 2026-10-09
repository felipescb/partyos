<?php

namespace App\Domain\Guests;

use App\Enums\GuestCategory;
use App\Enums\RsvpStatus;
use App\Models\Guest;
use InvalidArgumentException;
use RuntimeException;

final class GuestListCsv
{
    /**
     * @param  iterable<Guest>  $guests
     */
    public function render(iterable $guests): string
    {
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            throw new RuntimeException('Não foi possível gerar o CSV.');
        }

        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['nome', 'telefone', 'email', 'instagram', 'lista', 'rsvp', 'acompanhantes', 'observacao'], ';');

        foreach ($guests as $guest) {
            fputcsv($handle, [
                $guest->name,
                $guest->phone ?? '',
                $guest->email ?? '',
                $guest->instagram ?? '',
                $guest->category->label(),
                $guest->rsvp_status->label(),
                (string) $guest->plus_ones,
                $guest->notes ?? '',
            ], ';');
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv === false ? '' : $csv;
    }

    /**
     * @return list<GuestListRow>
     */
    public function parse(string $csv): array
    {
        $csv = trim($csv);

        if ($csv === '') {
            throw new InvalidArgumentException('O arquivo CSV está vazio.');
        }

        $handle = fopen('php://memory', 'r+');

        if ($handle === false) {
            throw new RuntimeException('Não foi possível ler o arquivo.');
        }

        fwrite($handle, $csv);
        rewind($handle);

        $delimiter = $this->delimiter((string) fgets($handle));
        rewind($handle);

        $headers = fgetcsv($handle, 0, $delimiter);

        if ($headers === false) {
            fclose($handle);

            throw new InvalidArgumentException('Cabeçalho do CSV inválido.');
        }

        $map = $this->headerMap($headers);

        if (! array_key_exists('nome', $map)) {
            fclose($handle);

            throw new InvalidArgumentException('O CSV precisa da coluna nome.');
        }

        $rows = [];

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if ($this->rowIsEmpty($row)) {
                continue;
            }

            $data = [];

            foreach ($map as $column => $index) {
                $data[$column] = isset($row[$index]) ? trim((string) $row[$index]) : '';
            }

            $name = $data['nome'];

            if ($name === '') {
                continue;
            }

            $rows[] = new GuestListRow(
                name: mb_substr($name, 0, 140),
                phone: $this->limit($this->blank($data['telefone'] ?? ''), 40),
                email: $this->limit($this->blank($data['email'] ?? ''), 160),
                instagram: $this->limit($this->blank($data['instagram'] ?? ''), 80),
                category: $this->category($data['lista'] ?? ''),
                rsvp: $this->rsvp($data['rsvp'] ?? ''),
                plusOnes: $this->plusOnes($data['acompanhantes'] ?? ''),
                notes: $this->limit($this->blank($data['observacao'] ?? ''), 2000),
            );
        }

        fclose($handle);

        return $rows;
    }

    private function delimiter(string $headerLine): string
    {
        $headerLine = str_replace("\xEF\xBB\xBF", '', $headerLine);

        return substr_count($headerLine, ';') > substr_count($headerLine, ',') ? ';' : ',';
    }

    /**
     * @param  list<string|null>  $headers
     * @return array<string, int>
     */
    private function headerMap(array $headers): array
    {
        $aliases = [
            'nome' => 'nome',
            'name' => 'nome',
            'convidado' => 'nome',
            'telefone' => 'telefone',
            'phone' => 'telefone',
            'celular' => 'telefone',
            'email' => 'email',
            'e mail' => 'email',
            'instagram' => 'instagram',
            'ig' => 'instagram',
            'lista' => 'lista',
            'categoria' => 'lista',
            'category' => 'lista',
            'list' => 'lista',
            'rsvp' => 'rsvp',
            'status' => 'rsvp',
            'acompanhantes' => 'acompanhantes',
            'plus' => 'acompanhantes',
            'plus ones' => 'acompanhantes',
            'observacao' => 'observacao',
            'obs' => 'observacao',
            'notes' => 'observacao',
        ];

        $map = [];

        foreach ($headers as $index => $header) {
            $key = $aliases[$this->fold((string) $header)] ?? null;

            if ($key !== null && ! array_key_exists($key, $map)) {
                $map[$key] = $index;
            }
        }

        return $map;
    }

    private function category(string $value): ?GuestCategory
    {
        if ($value === '') {
            return null;
        }

        $needle = $this->fold($value);

        foreach (GuestCategory::cases() as $category) {
            if ($needle === $category->value || $needle === $this->fold($category->label())) {
                return $category;
            }
        }

        return null;
    }

    private function rsvp(string $value): RsvpStatus
    {
        if ($value === '') {
            return RsvpStatus::NotSent;
        }

        $needle = $this->fold($value);

        foreach (RsvpStatus::cases() as $status) {
            if ($needle === str_replace('_', ' ', $status->value) || $needle === $status->value || $needle === $this->fold($status->label())) {
                return $status;
            }
        }

        return RsvpStatus::NotSent;
    }

    private function plusOnes(string $value): int
    {
        $digits = preg_replace('/\D/', '', $value) ?? '';

        return min(20, max(0, (int) $digits));
    }

    private function blank(string $value): ?string
    {
        return $value === '' ? null : $value;
    }

    private function limit(?string $value, int $max): ?string
    {
        if ($value === null) {
            return null;
        }

        return mb_substr($value, 0, $max);
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

    private function fold(string $value): string
    {
        $value = str_replace("\xEF\xBB\xBF", '', trim(mb_strtolower($value)));
        $value = strtr($value, [
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c', 'ñ' => 'n',
        ]);

        return trim(preg_replace('/[^a-z0-9]+/', ' ', $value) ?? $value);
    }
}
