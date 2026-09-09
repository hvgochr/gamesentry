<?php

namespace App\Services\Riot;

use App\Enums\Game;
use App\Models\WatchedPlayer;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use RuntimeException;

class MatchSummaryService
{
    public function __construct(private readonly DataDragonService $dataDragon) {}

    /**
     * @param  array<string, mixed>  $match
     * @return array<string, mixed>
     */
    public function normalize(WatchedPlayer $watchedPlayer, array $match): array
    {
        return match ($watchedPlayer->game) {
            Game::LeagueOfLegends => $this->normalizeLeagueOfLegends($watchedPlayer, $match),
            Game::TeamfightTactics => $this->normalizeTeamfightTactics($watchedPlayer, $match),
        };
    }

    /**
     * @param  array<string, mixed>  $summary
     * @return array<string, mixed>
     */
    public function discordEmbed(array $summary): array
    {
        $fields = $this->normalizeEmbedFields($summary['embed_fields'] ?? null);
        $author = [
            'name' => $summary['riot_id'],
        ];

        $champion = data_get($summary, 'player.champion');
        $championImageUrl = null;

        if (is_string($champion) && $champion !== '') {
            $championImageUrl = $this->dataDragon->championImageUrl($champion);
            $author['icon_url'] = $championImageUrl;
        }

        $embed = [
            'title' => $summary['title'],
            'description' => $summary['summary_line'],
            'color' => $summary['color'],
            'author' => $author,
            'fields' => $fields,
            'footer' => [
                'text' => 'Gamesentry',
            ],
            'timestamp' => $summary['finished_at'],
        ];

        if ($championImageUrl !== null) {
            $embed['thumbnail'] = [
                'url' => $championImageUrl,
            ];
        }

        return $embed;
    }

    /**
     * @return list<array{name: string, value: string, inline: bool}>
     */
    private function normalizeEmbedFields(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        $fields = [];

        foreach ($payload as $field) {
            if (! is_array($field)
                || ! is_string($field['name'] ?? null)
                || ! is_string($field['value'] ?? null)) {
                continue;
            }

            $fields[] = [
                'name' => $field['name'],
                'value' => $field['value'],
                'inline' => is_bool($field['inline'] ?? null) ? $field['inline'] : true,
            ];
        }

        return $fields;
    }

    /**
     * @param  array<string, mixed>  $match
     * @return array<string, mixed>
     */
    private function normalizeLeagueOfLegends(WatchedPlayer $watchedPlayer, array $match): array
    {
        $participant = $this->findParticipant(data_get($match, 'info.participants', []), $watchedPlayer->riot_puuid);
        $durationSeconds = $this->durationSeconds((int) data_get($match, 'info.gameDuration', 0));
        $cs = (int) data_get($participant, 'totalMinionsKilled', 0)
            + (int) data_get($participant, 'neutralMinionsKilled', 0);
        $kills = (int) data_get($participant, 'kills', 0);
        $deaths = (int) data_get($participant, 'deaths', 0);
        $assists = (int) data_get($participant, 'assists', 0);
        $result = data_get($participant, 'win') ? 'victory' : 'defeat';
        $champion = (string) data_get($participant, 'championName', 'Unknown Champion');
        $finishedAt = CarbonImmutable::createFromTimestampMs(
            (int) data_get($match, 'info.gameEndTimestamp', (int) (microtime(true) * 1000)),
        );

        return [
            'game' => $watchedPlayer->game->value,
            'match_id' => (string) data_get($match, 'metadata.matchId'),
            'riot_id' => "{$watchedPlayer->game_name}#{$watchedPlayer->tag_line}",
            'title' => "{$champion} - {$result}",
            'summary_line' => "{$watchedPlayer->game_name} has achieved a {$result} playing {$champion} with a KDA of {$kills}/{$deaths}/{$assists}.",
            'color' => data_get($participant, 'win') ? 0x22C55E : 0xEF4444,
            'finished_at' => $finishedAt->toIso8601String(),
            'duration_seconds' => $durationSeconds,
            'player' => [
                'champion' => $champion,
                'result' => $result,
                'kda' => "{$kills}/{$deaths}/{$assists}",
                'lane' => (string) (data_get($participant, 'individualPosition')
                    ?: data_get($participant, 'teamPosition')
                    ?: 'Unknown Lane'),
                'cs' => $cs,
                'gold' => (int) data_get($participant, 'goldEarned', 0),
                'vision' => (int) data_get($participant, 'visionScore', 0),
                'queue' => (string) (data_get($match, 'info.gameMode') ?: 'MATCHED'),
            ],
            'embed_fields' => [
                ['name' => 'Result', 'value' => $result, 'inline' => true],
                ['name' => 'KDA', 'value' => "{$kills}/{$deaths}/{$assists}", 'inline' => true],
                ['name' => 'CS', 'value' => (string) $cs, 'inline' => true],
                ['name' => 'Gold', 'value' => (string) data_get($participant, 'goldEarned', 0), 'inline' => true],
                ['name' => 'Vision', 'value' => (string) data_get($participant, 'visionScore', 0), 'inline' => true],
                ['name' => 'Duration', 'value' => $this->formatDuration($durationSeconds), 'inline' => true],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $match
     * @return array<string, mixed>
     */
    private function normalizeTeamfightTactics(WatchedPlayer $watchedPlayer, array $match): array
    {
        $participant = $this->findParticipant(data_get($match, 'info.participants', []), $watchedPlayer->riot_puuid);
        $placement = (int) data_get($participant, 'placement', 8);
        $durationSeconds = (int) round((float) data_get($match, 'info.game_length', 0));
        $finishedAt = CarbonImmutable::createFromTimestampMs(
            (int) data_get($match, 'info.game_datetime', (int) (microtime(true) * 1000)),
        );
        $traits = $this->formatTftTraits(data_get($participant, 'traits', []));
        $units = $this->formatTftUnits(data_get($participant, 'units', []));

        return [
            'game' => $watchedPlayer->game->value,
            'match_id' => (string) data_get($match, 'metadata.match_id'),
            'riot_id' => "{$watchedPlayer->game_name}#{$watchedPlayer->tag_line}",
            'title' => "TFT - #{$placement}/8",
            'summary_line' => "{$watchedPlayer->game_name} finished in #{$placement} place with a team of {$units}.",
            'color' => $placement <= 4 ? 0x22C55E : 0xF59E0B,
            'finished_at' => $finishedAt->toIso8601String(),
            'duration_seconds' => $durationSeconds,
            'player' => [
                'placement' => $placement,
                'level' => (int) data_get($participant, 'level', 0),
                'damage_to_players' => (int) data_get($participant, 'total_damage_to_players', 0),
                'players_eliminated' => (int) data_get($participant, 'players_eliminated', 0),
                'last_round' => (int) data_get($participant, 'last_round', 0),
                'traits' => $traits,
                'units' => $units,
            ],
            'embed_fields' => [
                ['name' => 'Placement', 'value' => "#{$placement}/8", 'inline' => true],
                ['name' => 'Level', 'value' => (string) data_get($participant, 'level', 0), 'inline' => true],
                ['name' => 'Damages', 'value' => (string) data_get($participant, 'total_damage_to_players', 0), 'inline' => true],
                ['name' => 'Kills', 'value' => (string) data_get($participant, 'players_eliminated', 0), 'inline' => true],
                ['name' => 'Last round', 'value' => (string) data_get($participant, 'last_round', 0), 'inline' => true],
                ['name' => 'Traits', 'value' => $traits, 'inline' => false],
                ['name' => 'Comp', 'value' => $units, 'inline' => false],
            ],
        ];
    }

    /**
     * @param  iterable<mixed>  $participants
     * @return array<string, mixed>
     */
    private function findParticipant(iterable $participants, string $puuid): array
    {
        $participant = collect($participants)->firstWhere('puuid', $puuid);

        if ($participant === null) {
            throw new RuntimeException('The player cannot be found in the match details.');
        }

        return $participant;
    }

    private function durationSeconds(int $duration): int
    {
        if ($duration > 100_000) {
            return (int) round($duration / 1000);
        }

        return $duration;
    }

    private function formatDuration(int $durationSeconds): string
    {
        $minutes = intdiv($durationSeconds, 60);
        $seconds = $durationSeconds % 60;

        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    /**
     * @param  iterable<mixed>  $traits
     */
    private function formatTftTraits(iterable $traits): string
    {
        $formatted = collect($traits)
            ->filter(fn (array $trait) => (int) data_get($trait, 'tier_current', 0) > 0)
            ->sortByDesc(fn (array $trait) => sprintf(
                '%02d-%02d',
                (int) data_get($trait, 'style', 0),
                (int) data_get($trait, 'tier_current', 0),
            ))
            ->take(3)
            ->map(fn (array $trait) => sprintf(
                '%s %s',
                Str::headline(str_replace('_', ' ', (string) data_get($trait, 'name', 'Trait'))),
                data_get($trait, 'tier_current', 0),
            ))
            ->implode(', ');

        return $formatted !== '' ? $formatted : 'No distinctive traits';
    }

    /**
     * @param  iterable<mixed>  $units
     */
    private function formatTftUnits(iterable $units): string
    {
        $formatted = collect($units)
            ->sortByDesc(fn (array $unit) => sprintf(
                '%02d-%s',
                (int) data_get($unit, 'tier', 0),
                (string) data_get($unit, 'character_id', ''),
            ))
            ->take(4)
            ->map(fn (array $unit) => sprintf(
                '%s %s',
                Str::headline(str_replace('_', ' ', (string) data_get($unit, 'character_id', 'Unit'))),
                str_repeat('*', max((int) data_get($unit, 'tier', 1), 1)),
            ))
            ->implode(', ');

        return $formatted !== '' ? $formatted : 'Unavailable comp';
    }
}
