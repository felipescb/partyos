<?php

namespace App\Enums;

enum ScenarioKind: string
{
    case Bad = 'bad';
    case Conservative = 'conservative';
    case Expected = 'expected';
    case Ok = 'ok';
    case Great = 'great';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Bad => 'Ruim',
            self::Conservative => 'Conservador',
            self::Expected => 'Esperado',
            self::Ok => 'OK',
            self::Great => 'Ótimo',
            self::Custom => 'Personalizado',
        };
    }

    public function scalePercent(): int
    {
        return match ($this) {
            self::Bad => 60,
            self::Conservative => 80,
            self::Expected => 100,
            self::Ok => 110,
            self::Great => 140,
            self::Custom => 100,
        };
    }
}
