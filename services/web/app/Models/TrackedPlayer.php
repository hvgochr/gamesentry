<?php

namespace App\Models;

use Database\Factories\TrackedPlayerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TrackedPlayer extends Model
{
    /** @use HasFactory<TrackedPlayerFactory> */
    use HasFactory;

    public const GAME_LABELS = [
        'lol' => 'League of Legends',
        'tft' => 'Teamfight Tactics',
    ];

    public const ROUTING_REGION_BY_REGION = [
        'americas' => 'americas',
        'asia' => 'asia',
        'europe' => 'europe',
        'sea' => 'sea',
        'br' => 'americas',
        'br1' => 'americas',
        'eune' => 'europe',
        'eun1' => 'europe',
        'euw' => 'europe',
        'euw1' => 'europe',
        'jp' => 'asia',
        'jp1' => 'asia',
        'kr' => 'asia',
        'la1' => 'americas',
        'la2' => 'americas',
        'lan' => 'americas',
        'las' => 'americas',
        'me1' => 'europe',
        'na' => 'americas',
        'na1' => 'americas',
        'oc1' => 'sea',
        'oce' => 'sea',
        'ru' => 'europe',
        'ru1' => 'europe',
        'sg2' => 'sea',
        'tr' => 'europe',
        'tr1' => 'europe',
        'tw2' => 'sea',
        'vn2' => 'sea',
    ];

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
        return collect(self::GAME_LABELS)
            ->map(
                fn (string $label, string $value): array => [
                    'value' => $value,
                    'label' => $label,
                ],
            )
            ->values()
            ->all();
    }

    public static function labelForGame(string $game): string
    {
        return self::GAME_LABELS[$game] ?? Str::headline(str_replace('-', ' ', $game));
    }

    public static function routingRegionFor(?string $region): ?string
    {
        if ($region === null) {
            return null;
        }

        $normalizedRegion = Str::lower(trim($region));

        return self::ROUTING_REGION_BY_REGION[$normalizedRegion] ?? null;
    }

    public function discordServer(): BelongsTo
    {
        return $this->belongsTo(DiscordServer::class);
    }
}
