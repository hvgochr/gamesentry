<?php

namespace App\Enums;

enum RoutingRegion: string
{
    case Americas = 'americas';
    case Asia = 'asia';
    case Europe = 'europe';
    case Sea = 'sea';

    public function label(): string
    {
        return match ($this) {
            self::Americas => 'Americas',
            self::Asia => 'Asia',
            self::Europe => 'Europe',
            self::Sea => 'SEA',
        };
    }
}
