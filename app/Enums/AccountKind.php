<?php

namespace App\Enums;

enum AccountKind: string
{
    case Organizer = 'organizer';
    case Participant = 'participant';

    public function label(): string
    {
        return match ($this) {
            self::Organizer => 'Cria e participa de eventos',
            self::Participant => 'Só participa de eventos',
        };
    }
}
