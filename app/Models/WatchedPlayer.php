<?php

namespace App\Models;

use App\Enums\Game;
use Database\Factories\WatchedPlayerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $discord_server_id
 * @property Game $game
 * @property string $routing_region
 * @property string $game_name
 * @property string $tag_line
 * @property string $riot_puuid
 * @property string $discord_user_id
 * @property string|null $last_seen_match_id
 * @property Carbon|null $last_polled_at
 * @property Carbon|null $next_poll_at
 * @property int $poll_interval_seconds
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read DiscordServer $discordServer
 * @property-read Collection<int, MatchNotification> $matchNotifications
 */
#[Fillable([
    'discord_server_id',
    'game',
    'routing_region',
    'game_name',
    'tag_line',
    'riot_puuid',
    'discord_user_id',
    'last_seen_match_id',
    'last_polled_at',
    'next_poll_at',
    'poll_interval_seconds',
    'is_active',
])]
class WatchedPlayer extends Model
{
    /** @use HasFactory<WatchedPlayerFactory> */
    use HasFactory;

    protected $attributes = [
        'poll_interval_seconds' => 300,
        'is_active' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'game' => Game::class,
            'last_polled_at' => 'datetime',
            'next_poll_at' => 'datetime',
            'poll_interval_seconds' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<DiscordServer, $this>
     */
    public function discordServer(): BelongsTo
    {
        return $this->belongsTo(DiscordServer::class);
    }

    /**
     * @return HasMany<MatchNotification, $this>
     */
    public function matchNotifications(): HasMany
    {
        return $this->hasMany(MatchNotification::class);
    }

    /**
     * @param  Builder<WatchedPlayer>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<WatchedPlayer>  $query
     */
    #[Scope]
    protected function dueForPolling(Builder $query): void
    {
        $query
            ->where('is_active', true)
            ->whereNotNull('next_poll_at')
            ->where('next_poll_at', '<=', now());
    }
}
