<?php

namespace Tests\Feature;

use App\Models\WatchedPlayer;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\DevCommands;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DevelopmentRuntimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_development_starts_the_scheduler_and_framework_processes(): void
    {
        $commands = array_column(DevCommands::commands(), 'command', 'name');

        $this->assertSame('php artisan schedule:work', $commands['scheduler']);
        $this->assertStringContainsString('artisan serve', $commands['server']);
        $this->assertStringContainsString('artisan queue:listen', $commands['queue']);
        $this->assertStringContainsString('artisan pail', $commands['logs']);
        $this->assertSame('npm run dev', $commands['vite']);
    }

    public function test_polling_uses_the_database_queue_and_shared_unique_locks(): void
    {
        config(['queue.default' => 'database', 'cache.default' => 'database']);
        Http::preventStrayRequests();
        Http::fake(['*.api.riotgames.com/*' => Http::response([])]);

        $player = WatchedPlayer::factory()->dueForPolling()->create();
        WatchedPlayer::factory()->inactive()->create();

        $this->artisan('gamesentry:dispatch-watched-player-polls')->assertSuccessful();
        $this->artisan('gamesentry:dispatch-watched-player-polls')->assertSuccessful();

        $this->assertDatabaseCount('jobs', 1);
        Http::assertNothingSent();

        $this->artisan('queue:work', ['connection' => 'database', '--once' => true])->assertSuccessful();

        $this->assertDatabaseCount('jobs', 0);
        $this->assertDatabaseCount('failed_jobs', 0);
        $this->assertTrue($player->refresh()->next_poll_at->isFuture());
        Http::assertSentCount(1);
    }

    public function test_scheduler_runs_polling_and_updates_the_database_heartbeat(): void
    {
        config(['queue.default' => 'database', 'cache.default' => 'database']);
        $this->travelTo(now()->startOfDay());
        WatchedPlayer::factory()->dueForPolling()->create();

        $events = app(Schedule::class)->events();
        $polling = collect($events)->first(fn ($event) => str_contains($event->command ?? '', 'gamesentry:dispatch-watched-player-polls'));

        $this->assertNotNull($polling);
        $this->assertTrue($polling->withoutOverlapping);
        $this->artisan('gamesentry:dispatch-watched-player-polls')->assertSuccessful();
        $this->assertDatabaseCount('jobs', 1);

        $heartbeat = collect($events)->first(fn ($event) => $event->description === 'gamesentry:scheduler-heartbeat');
        $this->assertNotNull($heartbeat);
        $heartbeat->run($this->app);

        $this->assertSame(now()->toIso8601String(), Cache::get('gamesentry:scheduler:last-run-at'));
    }
}
