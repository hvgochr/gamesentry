<?php

namespace App\Models;

use App\Enums\Game;
use Database\Factories\WatchedPlayerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function discordServer(): BelongsTo
    {
        return $this->belongsTo(DiscordServer::class);
    }

    public function matchNotifications(): HasMany
    {
        return $this->hasMany(MatchNotification::class);
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    #[Scope]
    protected function dueForPolling(Builder $query): void
    {
        $query->active()->whereNotNull('next_poll_at')->where('next_poll_at', '<=', now());
    }
}
