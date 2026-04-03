<?php

namespace App\Models;

use Database\Factories\DiscordServerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    protected function casts(): array
    {
        return [
            'bot_installed_at' => 'datetime',
            'settings_synced_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function watchedPlayers(): HasMany
    {
        return $this->hasMany(WatchedPlayer::class);
    }

    public function matchNotifications(): HasMany
    {
        return $this->hasMany(MatchNotification::class);
    }
}
