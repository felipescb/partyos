<?php

namespace App\Enums;

enum EventStatus: string
{
    case Draft = 'draft';
    case Planning = 'planning';
    case OnSale = 'on_sale';
    case Confirmed = 'confirmed';
    case InProduction = 'in_production';
    case Live = 'live';
    case Finished = 'finished';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Planning => 'Planejando',
            self::OnSale => 'Em venda',
            self::Confirmed => 'Confirmado',
            self::InProduction => 'Em produção',
            self::Live => 'Ao vivo',
            self::Finished => 'Finalizado',
            self::Cancelled => 'Cancelado',
        };
    }
}
