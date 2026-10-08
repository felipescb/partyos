<?php

namespace App\Enums;

enum CostStatus: string
{
    case Planned = 'planned';
    case Quoted = 'quoted';
    case Negotiating = 'negotiating';
    case Contracted = 'contracted';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Planejado',
            self::Quoted => 'Orçado',
            self::Negotiating => 'Negociando',
            self::Contracted => 'Contratado',
            self::PartiallyPaid => 'Parcialmente pago',
            self::Paid => 'Pago',
            self::Cancelled => 'Cancelado',
        };
    }
}
