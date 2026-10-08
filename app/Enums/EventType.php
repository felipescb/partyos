<?php

namespace App\Enums;

enum EventType: string
{
    case Party = 'party';
    case Rave = 'rave';
    case Show = 'show';
    case Festival = 'festival';
    case Dinner = 'dinner';
    case Corporate = 'corporate';
    case Exhibition = 'exhibition';
    case Wedding = 'wedding';
    case PrivateEvent = 'private';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Party => 'Festa',
            self::Rave => 'Rave',
            self::Show => 'Show',
            self::Festival => 'Festival',
            self::Dinner => 'Jantar',
            self::Corporate => 'Evento corporativo',
            self::Exhibition => 'Exposição',
            self::Wedding => 'Casamento',
            self::PrivateEvent => 'Evento privado',
            self::Other => 'Outro',
        };
    }
}
