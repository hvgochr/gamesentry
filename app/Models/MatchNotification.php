<?php

namespace App\Models;

use App\Enums\Game;
use App\Enums\MatchNotificationStatus;
use Database\Factories\MatchNotificationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $watched_player_id
 * @property int $discord_server_id
 * @property Game $game
 * @property string $riot_match_id
 * @property array<string, mixed>|null $match_payload
 * @property array<string, mixed>|null $discord_embed_payload
 * @property string|null $roast_text
 * @property string|null $discord_delivery_nonce
 * @property string|null $discord_message_id
 * @property MatchNotificationStatus $status
 * @property string|null $failure_reason
 * @property Carbon|null $delivered_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read WatchedPlayer|null $watchedPlayer
 * @property-read DiscordServer $discordServer
 */
#[Fillable([
    'watched_player_id',
    'discord_server_id',
    'game',
    'riot_match_id',
    'match_payload',
    'discord_embed_payload',
    'roast_text',
    'discord_delivery_nonce',
    'discord_message_id',
    'status',
    'failure_reason',
    'delivered_at',
])]
class MatchNotification extends Model
{
    /** @use HasFactory<MatchNotificationFactory> */
    use HasFactory;

    use MassPrunable;

    protected $attributes = [
        'status' => MatchNotificationStatus::Pending->value,
    ];

    /**
     * @return array<string, string>
     */
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

    /**
     * @return BelongsTo<WatchedPlayer, $this>
     */
    public function watchedPlayer(): BelongsTo
    {
        return $this->belongsTo(WatchedPlayer::class);
    }

    /**
     * @return BelongsTo<DiscordServer, $this>
     */
    public function discordServer(): BelongsTo
    {
        return $this->belongsTo(DiscordServer::class);
    }

    /**
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::query()
            ->whereIn('status', [
                MatchNotificationStatus::Sent->value,
                MatchNotificationStatus::Failed->value,
            ])
            ->where('created_at', '<=', now()->subDays($this->retentionDays()));
    }

    private function retentionDays(): int
    {
        return max((int) config('gamesentry.notifications.match_notification_retention_days', 30), 1);
    }
}
