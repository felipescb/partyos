<?php

namespace App\Enums;

enum AccountKind: string
{
    case Organizer = 'organizer';
    case Participant = 'participant';

    public function label(): string
    {
        return match ($this) {
            self::Organizer => 'Cria eventos',
            self::Participant => 'Participa de eventos',
        };
    }
}
