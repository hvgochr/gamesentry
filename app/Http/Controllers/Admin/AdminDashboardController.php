<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Game;
use App\Enums\MatchNotificationStatus;
use App\Http\Controllers\Controller;
use App\Models\DiscordServer;
use App\Models\MatchNotification;
use App\Models\User;
use App\Models\WatchedPlayer;
use App\Services\Plans\PlanLimitService;
use App\Services\Riot\RiotPollingService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function __invoke(RiotPollingService $polling, PlanLimitService $limits): Response
    {
        return Inertia::render('admin/dashboard', [
            'stats' => $this->stats(),
            'users' => $this->users($limits),
            'recentFailedNotifications' => $this->recentFailedNotifications(),
            'servers' => $this->servers(),
            'activeWatchedPlayers' => $this->activeWatchedPlayers(),
            'failedJobs' => $this->failedJobs(),
            'queueBacklog' => $this->queueBacklog(),
            'scheduler' => $this->scheduler(),
            'riotBackoff' => $this->riotBackoff($polling),
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function stats(): array
    {
        return [
            'users_count' => User::query()->count(),
            'paused_users_count' => User::query()->whereNotNull('paused_at')->count(),
            'servers_count' => DB::table('discord_servers')->count(),
            'active_watched_players_count' => WatchedPlayer::query()->active()->count(),
            'pending_jobs_count' => DB::table('jobs')->whereNull('reserved_at')->count(),
            'failed_jobs_count' => DB::table('failed_jobs')->count(),
            'failed_notifications_count' => MatchNotification::query()
                ->where('status', MatchNotificationStatus::Failed->value)
                ->count(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function users(PlanLimitService $limits): array
    {
        return $this->listOf(User::query()
            ->withCount(['discordServers', 'watchedPlayers'])
            ->withSum([
                'dailyUsageCounters as groq_calls_today' => fn ($query) => $query->whereDate('usage_date', today()),
            ], 'groq_calls')
            ->latest()
            ->limit(25)
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'plan' => $user->plan->value,
                'paused_at' => $user->paused_at?->toIso8601String(),
                'created_at' => $user->created_at?->toIso8601String(),
                'servers_count' => $user->discord_servers_count,
                'watched_players_count' => $user->watched_players_count,
                'groq_calls_today' => (int) $user->groq_calls_today,
                'groq_daily_limit' => $limits->groqDailyLimit($user),
            ])
            ->all());
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recentFailedNotifications(): array
    {
        return $this->listOf(MatchNotification::query()
            ->with(['discordServer.user:id,name,email', 'watchedPlayer:id,game_name,tag_line'])
            ->where('status', MatchNotificationStatus::Failed->value)
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (MatchNotification $notification) => [
                'id' => $notification->id,
                'game' => $notification->game->value,
                'riot_match_id' => $notification->riot_match_id,
                'server_name' => $notification->discordServer->name,
                'user_name' => $notification->discordServer->user->name,
                'player_name' => $notification->watchedPlayer === null
                    ? null
                    : "{$notification->watchedPlayer->game_name}#{$notification->watchedPlayer->tag_line}",
                'failure_reason' => $notification->failure_reason,
                'created_at' => $notification->created_at?->toIso8601String(),
            ])
            ->all());
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function servers(): array
    {
        return $this->listOf(DiscordServer::query()
            ->with('user:id,name,email')
            ->withCount('watchedPlayers')
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (DiscordServer $server) => [
                'id' => $server->id,
                'name' => $server->name,
                'discord_guild_id' => $server->discord_guild_id,
                'user_name' => $server->user->name,
                'user_email' => $server->user->email,
                'watched_players_count' => $server->watched_players_count,
                'updated_at' => $server->updated_at?->toIso8601String(),
            ])
            ->all());
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function activeWatchedPlayers(): array
    {
        return $this->listOf(WatchedPlayer::query()
            ->with(['discordServer:id,user_id,name', 'discordServer.user:id,name,email'])
            ->active()
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (WatchedPlayer $player) => [
                'id' => $player->id,
                'game' => $player->game->value,
                'name' => "{$player->game_name}#{$player->tag_line}",
                'server_name' => $player->discordServer->name,
                'user_name' => $player->discordServer->user->name,
                'next_poll_at' => $player->next_poll_at?->toIso8601String(),
            ])
            ->all());
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function failedJobs(): array
    {
        return $this->listOf(DB::table('failed_jobs')
            ->latest('failed_at')
            ->limit(10)
            ->get()
            ->map(fn (object $job) => [
                'id' => (int) $job->id,
                'uuid' => (string) $job->uuid,
                'connection' => (string) $job->connection,
                'queue' => (string) $job->queue,
                'name' => $this->failedJobName((string) $job->payload),
                'failed_at' => Carbon::parse((string) $job->failed_at)->toIso8601String(),
                'exception' => str((string) $job->exception)->before("\n")->limit(180)->toString(),
            ])
            ->all());
    }

    /**
     * @return list<array{queue: string, total: int, pending: int, reserved: int, delayed: int, oldest_created_at: string|null}>
     */
    private function queueBacklog(): array
    {
        $now = now()->timestamp;

        return $this->listOf(DB::table('jobs')
            ->select('queue')
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when reserved_at is null then 1 else 0 end) as pending')
            ->selectRaw('sum(case when reserved_at is not null then 1 else 0 end) as reserved')
            ->selectRaw('sum(case when available_at > ? then 1 else 0 end) as delayed', [$now])
            ->selectRaw('min(created_at) as oldest_created_at')
            ->groupBy('queue')
            ->orderBy('queue')
            ->get()
            ->map(fn (object $queue) => [
                'queue' => (string) $queue->queue,
                'total' => (int) $queue->total,
                'pending' => (int) $queue->pending,
                'reserved' => (int) $queue->reserved,
                'delayed' => (int) $queue->delayed,
                'oldest_created_at' => $queue->oldest_created_at === null
                    ? null
                    : Carbon::createFromTimestamp((int) $queue->oldest_created_at)->toIso8601String(),
            ])
            ->all());
    }

    /**
     * @return array{last_run_at: string|null, is_recent: bool}
     */
    private function scheduler(): array
    {
        $lastRunAt = Cache::get('gamesentry:scheduler:last-run-at');
        $lastRun = is_string($lastRunAt) ? Carbon::parse($lastRunAt) : null;

        return [
            'last_run_at' => $lastRun?->toIso8601String(),
            'is_recent' => $lastRun !== null && $lastRun->greaterThanOrEqualTo(now()->subMinutes(2)),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function riotBackoff(RiotPollingService $polling): array
    {
        return $this->listOf(collect(Game::cases())
            ->map(function (Game $game) use ($polling) {
                $backoffUntil = $polling->backoffUntil($game);

                return [
                    'game' => $game->value,
                    'label' => match ($game) {
                        Game::LeagueOfLegends => 'League of Legends',
                        Game::TeamfightTactics => 'Teamfight Tactics',
                    },
                    'dispatch_limit_per_minute' => $polling->dispatchLimit($game),
                    'active_players_count' => WatchedPlayer::query()
                        ->active()
                        ->where('game', $game->value)
                        ->count(),
                    'due_players_count' => WatchedPlayer::query()
                        ->dueForPolling()
                        ->where('game', $game->value)
                        ->count(),
                    'backoff_until' => $backoffUntil?->toIso8601String(),
                    'is_backing_off' => $backoffUntil !== null && $backoffUntil->isFuture(),
                ];
            })
            ->all());
    }

    private function failedJobName(string $payload): string
    {
        $decoded = json_decode($payload, true);

        if (! is_array($decoded)) {
            return 'Unknown job';
        }

        return (string) data_get($decoded, 'displayName', 'Unknown job');
    }

    /**
     * @template TValue
     *
     * @param  array<int, TValue>  $items
     * @return list<TValue>
     */
    private function listOf(array $items): array
    {
        return array_values($items);
    }
}
