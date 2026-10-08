<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Scheduled = 'scheduled';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Agendado',
            self::Paid => 'Pago',
            self::Cancelled => 'Cancelado',
        };
    }
}
