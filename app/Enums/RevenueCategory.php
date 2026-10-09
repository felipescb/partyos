<?php

namespace App\Enums;

enum RevenueCategory: string
{
    case Tickets = 'tickets';
    case Door = 'door';
    case Bar = 'bar';
    case Merch = 'merch';
    case Sponsorship = 'sponsorship';
    case Partners = 'partners';
    case Sales = 'sales';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Tickets => 'Ingressos',
            self::Door => 'Porta',
            self::Bar => 'Bar',
            self::Merch => 'Merch',
            self::Sponsorship => 'Patrocínio',
            self::Partners => 'Parceiros',
            self::Sales => 'Vendas',
            self::Other => 'Outras',
        };
    }

    public function isManual(): bool
    {
        return $this !== self::Tickets;
    }

    public function colorSlug(): string
    {
        return match ($this) {
            self::Bar => 'bar',
            self::Sponsorship => 'marketing',
            self::Merch => 'compras',
            self::Door => 'staff',
            self::Partners => 'artistas',
            self::Sales => 'outros',
            self::Tickets, self::Other => 'none',
        };
    }
}
