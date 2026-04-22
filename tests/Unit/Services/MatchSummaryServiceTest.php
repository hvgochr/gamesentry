<?php

namespace Tests\Unit\Services;

use App\Enums\Game;
use App\Models\WatchedPlayer;
use App\Services\Riot\MatchSummaryService;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class MatchSummaryServiceTest extends TestCase
{
    public function test_it_normalizes_a_league_of_legends_match(): void
    {
        $summary = (new MatchSummaryService)->normalize(
            $this->leagueWatchedPlayer(),
            [
                'metadata' => [
                    'matchId' => 'EUW1_123',
                ],
                'info' => [
                    'gameDuration' => 1_800_000,
                    'gameEndTimestamp' => 1_715_000_000_000,
                    'gameMode' => 'CLASSIC',
                    'participants' => [
                        [
                            'puuid' => 'riot-puuid-lol',
                            'kills' => 12,
                            'deaths' => 3,
                            'assists' => 9,
                            'win' => true,
                            'championName' => 'Ahri',
                            'totalMinionsKilled' => 180,
                            'neutralMinionsKilled' => 20,
                            'goldEarned' => 15000,
                            'visionScore' => 35,
                            'individualPosition' => 'MIDDLE',
                        ],
                    ],
                ],
            ],
        );

        $this->assertSame('lol', $summary['game']);
        $this->assertSame('EUW1_123', $summary['match_id']);
        $this->assertSame('TrackedPlayer#EUW1', $summary['riot_id']);
        $this->assertSame('Ahri - victory', $summary['title']);
        $this->assertSame(1800, $summary['duration_seconds']);
        $this->assertSame('200', data_get($summary, 'embed_fields.2.value'));
    }

    public function test_it_normalizes_a_teamfight_tactics_match(): void
    {
        $summary = (new MatchSummaryService)->normalize(
            $this->tftWatchedPlayer(),
            [
                'metadata' => [
                    'match_id' => 'TFTMATCH_123',
                ],
                'info' => [
                    'game_length' => 2100.1,
                    'game_datetime' => 1_715_000_000_000,
                    'participants' => [
                        [
                            'puuid' => 'riot-puuid-tft',
                            'placement' => 2,
                            'level' => 9,
                            'total_damage_to_players' => 112,
                            'players_eliminated' => 5,
                            'last_round' => 34,
                            'traits' => [
                                ['name' => 'trait_warrior', 'tier_current' => 2, 'style' => 3],
                                ['name' => 'trait_mage', 'tier_current' => 1, 'style' => 2],
                            ],
                            'units' => [
                                ['character_id' => 'annie', 'tier' => 2],
                                ['character_id' => 'ezreal', 'tier' => 1],
                            ],
                        ],
                    ],
                ],
            ],
        );

        $this->assertSame('tft', $summary['game']);
        $this->assertSame('TFTMATCH_123', $summary['match_id']);
        $this->assertSame('TftPlayer#NA1', $summary['riot_id']);
        $this->assertSame('TFT - #2/8', $summary['title']);
        $this->assertSame(2100, $summary['duration_seconds']);
        $this->assertStringContainsString('Trait Warrior 2', data_get($summary, 'player.traits'));
        $this->assertStringContainsString('Annie **', data_get($summary, 'player.units'));
    }

    public function test_discord_embed_uses_the_summary_fields(): void
    {
        $embed = (new MatchSummaryService)->discordEmbed([
            'title' => 'Ahri - victory',
            'summary_line' => 'TrackedPlayer won.',
            'color' => 0x22C55E,
            'finished_at' => '2026-04-20T20:00:00+00:00',
            'embed_fields' => [
                ['name' => 'Result', 'value' => 'victory', 'inline' => true],
            ],
        ]);

        $this->assertSame('Ahri - victory', $embed['title']);
        $this->assertSame('TrackedPlayer won.', $embed['description']);
        $this->assertSame('Gamesentry', data_get($embed, 'footer.text'));
        $this->assertSame('victory', data_get($embed, 'fields.0.value'));
    }

    public function test_it_throws_when_the_watched_player_is_missing_from_the_match(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The player cannot be found in the match details.');

        (new MatchSummaryService)->normalize(
            $this->leagueWatchedPlayer(),
            [
                'metadata' => [
                    'matchId' => 'EUW1_123',
                ],
                'info' => [
                    'gameDuration' => 1800,
                    'participants' => [],
                ],
            ],
        );
    }

    private function leagueWatchedPlayer(): WatchedPlayer
    {
        return new WatchedPlayer([
            'game' => Game::LeagueOfLegends->value,
            'game_name' => 'TrackedPlayer',
            'tag_line' => 'EUW1',
            'riot_puuid' => 'riot-puuid-lol',
        ]);
    }

    private function tftWatchedPlayer(): WatchedPlayer
    {
        return new WatchedPlayer([
            'game' => Game::TeamfightTactics->value,
            'game_name' => 'TftPlayer',
            'tag_line' => 'NA1',
            'riot_puuid' => 'riot-puuid-tft',
        ]);
    }
}
