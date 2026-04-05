<?php

namespace App\Models;

use App\Enums\Game;
use App\Enums\MatchNotificationStatus;
use Database\Factories\MatchNotificationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'watched_player_id',
    'discord_server_id',
    'game',
    'riot_match_id',
    'match_payload',
    'discord_embed_payload',
    'roast_text',
    'status',
    'failure_reason',
    'delivered_at',
])]
class MatchNotification extends Model
{
    /** @use HasFactory<MatchNotificationFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => MatchNotificationStatus::Pending->value,
    ];

    protected function casts(): array
    {
        return [
            'game' => Game::class,
            'match_payload' => 'array',
            'discord_embed_payload' => 'array',
            'status' => MatchNotificationStatus::class,
            'delivered_at' => 'datetime',
        ];
    }

    public function watchedPlayer(): BelongsTo
    {
        return $this->belongsTo(WatchedPlayer::class);
    }

    public function discordServer(): BelongsTo
    {
        return $this->belongsTo(DiscordServer::class);
    }
}
