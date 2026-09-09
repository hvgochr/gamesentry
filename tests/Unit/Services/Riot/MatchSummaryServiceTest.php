<?php

namespace Tests\Unit\Services\Riot;

use App\Services\Riot\MatchSummaryService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MatchSummaryServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.riot.data_dragon_version', '16.17.1');
        Http::preventStrayRequests();
    }

    public function test_league_embed_contains_the_riot_id_and_champion_images(): void
    {
        $embed = app(MatchSummaryService::class)->discordEmbed([
            'game' => 'lol',
            'riot_id' => 'Player#EUW',
            'title' => 'Aatrox - victory',
            'summary_line' => 'Player won with Aatrox.',
            'color' => 0x22C55E,
            'finished_at' => '2026-09-09T12:00:00+00:00',
            'player' => [
                'champion' => 'Aatrox',
            ],
            'embed_fields' => [],
        ]);

        $championImageUrl = 'https://ddragon.leagueoflegends.com/cdn/16.17.1/img/champion/Aatrox.png';

        $this->assertSame([
            'name' => 'Player#EUW',
            'icon_url' => $championImageUrl,
        ], $embed['author']);
        $this->assertSame(['url' => $championImageUrl], $embed['thumbnail']);
        Http::assertNothingSent();
    }

    public function test_tft_embed_contains_the_riot_id_without_a_champion_image(): void
    {
        $embed = app(MatchSummaryService::class)->discordEmbed([
            'game' => 'tft',
            'riot_id' => 'Player#EUW',
            'title' => 'TFT - #1/8',
            'summary_line' => 'Player finished first.',
            'color' => 0x22C55E,
            'finished_at' => '2026-09-09T12:00:00+00:00',
            'player' => [
                'placement' => 1,
            ],
            'embed_fields' => [],
        ]);

        $this->assertSame(['name' => 'Player#EUW'], $embed['author']);
        $this->assertArrayNotHasKey('thumbnail', $embed);
        Http::assertNothingSent();
    }
}
