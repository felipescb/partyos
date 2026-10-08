<?php

namespace App\Enums;

enum TicketSalesPlatform: string
{
    case Shotgun = 'shotgun';
    case Gandaya = 'gandaya';

    public function label(): string
    {
        return match ($this) {
            self::Shotgun => 'Shotgun',
            self::Gandaya => 'Gandaya',
        };
    }
}
