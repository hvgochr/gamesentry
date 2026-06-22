<?php

namespace App\Models;

use Database\Factories\DiscordServerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $discord_guild_id
 * @property string $name
 * @property string|null $icon
 * @property string $discord_channel_id
 * @property string $discord_channel_name
 * @property Carbon|null $bot_installed_at
 * @property Carbon|null $settings_synced_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int $watched_players_count
 * @property-read User $user
 * @property-read Collection<int, WatchedPlayer> $watchedPlayers
 * @property-read Collection<int, MatchNotification> $matchNotifications
 */
#[Fillable([
    'user_id',
    'discord_guild_id',
    'name',
    'icon',
    'discord_channel_id',
    'discord_channel_name',
    'bot_installed_at',
    'settings_synced_at',
])]
class DiscordServer extends Model
{
    /** @use HasFactory<DiscordServerFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bot_installed_at' => 'datetime',
            'settings_synced_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<WatchedPlayer, $this>
     */
    public function watchedPlayers(): HasMany
    {
        return $this->hasMany(WatchedPlayer::class);
    }

    /**
     * @return HasMany<MatchNotification, $this>
     */
    public function matchNotifications(): HasMany
    {
        return $this->hasMany(MatchNotification::class);
    }
}
