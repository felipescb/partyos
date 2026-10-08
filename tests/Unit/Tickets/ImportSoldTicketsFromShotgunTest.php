<?php

namespace Tests\Unit\Tickets;

use App\Domain\Tickets\ImportSoldTicketsFromShotgun;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ImportSoldTicketsFromShotgunTest extends TestCase
{
    #[Test]
    public function it_counts_valid_orders_by_deal_title(): void
    {
        $csv = file_get_contents(base_path('tests/fixtures/shotgun_valid_orders.csv'));

        $imports = app(ImportSoldTicketsFromShotgun::class)->parse($csv);

        $this->assertCount(1, $imports);
        $this->assertSame('Keep Walking', $imports[0]->name);
        $this->assertSame(15, $imports[0]->soldCount);
        $this->assertSame(0, $imports[0]->priceCents);
    }
}
