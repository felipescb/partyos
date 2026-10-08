<?php

namespace App\Enums;

enum RevenueStatus: string
{
    case Planned = 'planned';
    case Confirmed = 'confirmed';
    case Received = 'received';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Prevista',
            self::Confirmed => 'Confirmada',
            self::Received => 'Recebida',
            self::Cancelled => 'Cancelada',
        };
    }
}
