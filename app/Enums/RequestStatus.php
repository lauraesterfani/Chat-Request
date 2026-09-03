<?php

namespace App\Enums;

enum RequestStatus: string
{
    case PENDING = 'pending';
    case ANALYZING = 'analyzing';
    case WAITING = 'waiting';
    case SOLVING = 'solving';
    case COMPLETED = 'completed';
    case CANCELED = 'canceled';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
