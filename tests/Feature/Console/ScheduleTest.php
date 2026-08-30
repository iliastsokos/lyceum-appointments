<?php

namespace Tests\Feature\Console;

use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class ScheduleTest extends TestCase
{
    public function test_maintenance_commands_are_scheduled_daily(): void
    {
        $events = app(Schedule::class)->events();

        $commands = collect($events)->map(fn ($event) => $event->command)->filter();

        $this->assertTrue($commands->contains(fn ($command) => str_contains($command, 'imports:clean-pending')));
        $this->assertTrue($commands->contains(fn ($command) => str_contains($command, 'db:backup')));
    }
}
