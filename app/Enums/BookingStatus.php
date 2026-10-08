<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Inquiry = 'inquiry';
    case Negotiating = 'negotiating';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Inquiry => 'Consulta',
            self::Negotiating => 'Negociando',
            self::Confirmed => 'Confirmado',
            self::Cancelled => 'Cancelado',
        };
    }
}
