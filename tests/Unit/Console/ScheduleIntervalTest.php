<?php

namespace Tests\Unit\Console;

use App\Console\ScheduleInterval;
use PHPUnit\Framework\TestCase;

class ScheduleIntervalTest extends TestCase
{
    public function test_it_generates_every_minute_cron_expression(): void
    {
        $this->assertSame('* * * * *', ScheduleInterval::cronEveryMinutes(1));
    }

    public function test_it_clamps_values_below_one_minute(): void
    {
        $this->assertSame('* * * * *', ScheduleInterval::cronEveryMinutes(0));
        $this->assertSame('* * * * *', ScheduleInterval::cronEveryMinutes(-10));
    }

    public function test_it_generates_step_cron_expression(): void
    {
        $this->assertSame('*/10 * * * *', ScheduleInterval::cronEveryMinutes(10));
    }

    public function test_it_clamps_values_above_sixty_minutes(): void
    {
        $this->assertSame('*/60 * * * *', ScheduleInterval::cronEveryMinutes(120));
    }
}
