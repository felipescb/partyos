<?php

namespace App\Enums;

enum OrgRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Dono',
            self::Admin => 'Admin',
            self::Member => 'Membro',
        };
    }
}
