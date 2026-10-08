<?php

namespace App\Enums;

enum RsvpStatus: string
{
    case NotSent = 'not_sent';
    case Sent = 'sent';
    case Seen = 'seen';
    case Confirmed = 'confirmed';
    case Declined = 'declined';
    case CheckedIn = 'checked_in';

    public function label(): string
    {
        return match ($this) {
            self::NotSent => 'Convite não enviado',
            self::Sent => 'Enviado',
            self::Seen => 'Visualizado',
            self::Confirmed => 'Confirmado',
            self::Declined => 'Recusado',
            self::CheckedIn => 'Check-in',
        };
    }
}
