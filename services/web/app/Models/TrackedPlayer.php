<?php

namespace App\Models;

use App\Enums\PlatformRegion;
use App\Enums\RiotGame;
use App\Enums\RoutingRegion;
use Database\Factories\TrackedPlayerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackedPlayer extends Model
{
    /** @use HasFactory<TrackedPlayerFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'discord_server_id',
        'game',
        'riot_name',
        'riot_tagline',
        'puuid',
        'region',
        'routing_region',
        'summoner_id',
        'discord_user_id',
        'last_seen_match_id',
        'last_polled_at',
        'riot_synced_at',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'game' => RiotGame::class,
            'region' => PlatformRegion::class,
            'routing_region' => RoutingRegion::class,
            'is_active' => 'boolean',
            'last_polled_at' => 'datetime',
            'riot_synced_at' => 'datetime',
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function gameOptions(): array
    {
        return RiotGame::options();
    }

    public static function labelForGame(string $game): string
    {
        return RiotGame::tryFrom($game)?->label() ?? $game;
    }

    /**
     * @return array<int, array{value: string, label: string, routingRegion: string}>
     */
    public static function regionOptions(): array
    {
        return PlatformRegion::options();
    }

    public static function routingRegionFor(?string $region): ?string
    {
        if ($region === null) {
            return null;
        }

        return PlatformRegion::tryFrom($region)?->routingRegion()->value;
    }

    public function discordServer(): BelongsTo
    {
        return $this->belongsTo(DiscordServer::class);
    }
}
