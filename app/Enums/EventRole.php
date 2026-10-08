<?php

namespace App\Enums;

enum EventRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Finance = 'finance';
    case Production = 'production';
    case Artist = 'artist';
    case Vendor = 'vendor';
    case Staff = 'staff';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Admin => 'Admin',
            self::Finance => 'Financeiro',
            self::Production => 'Produção',
            self::Artist => 'Artista',
            self::Vendor => 'Fornecedor',
            self::Staff => 'Staff',
        };
    }

    public function canViewFinance(): bool
    {
        return in_array($this, [self::Owner, self::Admin, self::Finance, self::Production], true);
    }

    public function canManageFinance(): bool
    {
        return in_array($this, [self::Owner, self::Admin, self::Finance], true);
    }

    public function canManageOperations(): bool
    {
        return in_array($this, [self::Owner, self::Admin, self::Finance, self::Production], true);
    }

    public function canViewGuests(): bool
    {
        return in_array($this, [self::Owner, self::Admin, self::Production, self::Staff], true);
    }

    public function canManageTeam(): bool
    {
        return in_array($this, [self::Owner, self::Admin], true);
    }
}
