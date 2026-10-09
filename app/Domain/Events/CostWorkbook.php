<?php

namespace App\Domain\Events;

final class CostWorkbook
{
    /**
     * @param  list<array{section: string, item: string, detail: string, quantity: int, unit: int, total: int, responsible: string, status: string, pix: string, invoice: string, invoiceUrl: string, comment: string}>  $lines
     * @param  list<array{name: string, price: int, quantity: int, payoutBasisPoints: int}>  $tiers
     * @param  list<string>  $courtesyLists
     */
    public function __construct(
        public string $name,
        public ?string $startsOn,
        public ?string $venue,
        public ?int $capacity,
        public int $barExpected,
        public string $barNotes,
        public array $courtesyLists,
        public array $lines,
        public array $tiers,
    ) {}
}
