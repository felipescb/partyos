<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Backlog = 'backlog';
    case Todo = 'todo';
    case Doing = 'doing';
    case Blocked = 'blocked';
    case Done = 'done';

    public function label(): string
    {
        return match ($this) {
            self::Backlog => 'Backlog',
            self::Todo => 'A fazer',
            self::Doing => 'Em andamento',
            self::Blocked => 'Bloqueada',
            self::Done => 'Concluída',
        };
    }
}
