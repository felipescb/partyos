<?php

namespace Tests\Unit\Finance;

use App\Domain\Finance\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_it_parses_brazilian_money(): void
    {
        $this->assertSame(100050, Money::parse('1.000,50'));
        $this->assertSame(100000, Money::parse('1000'));
        $this->assertSame(1050, Money::parse('10,5'));
        $this->assertSame(80000, Money::parse('R$ 800,00'));
        $this->assertSame(0, Money::parse(''));
    }

    public function test_it_formats_reais_without_float_drift(): void
    {
        $this->assertSame('R$ 1.000,50', Money::format(100050));
        $this->assertSame('-R$ 18,25', Money::format(-1825));
        $this->assertSame('US$ 10,00', Money::format(1000, 'USD'));
    }

    public function test_percent_parsing_uses_basis_points(): void
    {
        $this->assertSame(2500, Money::parse('25'));
        $this->assertSame(2550, Money::parse('25,50'));
        $this->assertSame('25%', Money::formatPercent(2500));
        $this->assertSame('10,50%', Money::formatPercent(1050));
    }

    public function test_portion_rounds_half_up(): void
    {
        $this->assertSame(10750, Money::portion(107500, 1000));
        $this->assertSame(26875, Money::portion(107500, 2500));
        $this->assertSame(-2500, Money::portion(10000, -2500));
        $this->assertSame(2500, Money::portion(-10000, -2500));
    }
}
