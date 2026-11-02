<?php

namespace App\Enums;

enum ItemKind: string
{
    case Todo = 'todo';
    case Meeting = 'meeting';
    case Task = 'task';

    public function isTimeBound(): bool
    {
        return $this !== self::Todo;
    }
}
