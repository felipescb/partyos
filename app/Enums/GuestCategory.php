<?php

namespace App\Enums;

enum GuestCategory: string
{
    case Vip = 'vip';
    case Artist = 'artist';
    case Press = 'press';
    case Friend = 'friend';
    case Production = 'production';
    case Partner = 'partner';
    case Guest = 'guest';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Vip => 'VIP',
            self::Artist => 'Artista',
            self::Press => 'Imprensa',
            self::Friend => 'Amigo',
            self::Production => 'Produção',
            self::Partner => 'Parceiro',
            self::Guest => 'Convidado',
            self::Other => 'Outros',
        };
    }
}
