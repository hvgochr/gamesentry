<?php

namespace App\Console;

class ScheduleInterval
{
    public static function cronEveryMinutes(int $minutes): string
    {
        $minutes = min(max($minutes, 1), 60);

        return $minutes === 1 ? '* * * * *' : "*/{$minutes} * * * *";
    }
}
