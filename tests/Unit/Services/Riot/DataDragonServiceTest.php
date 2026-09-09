<?php

namespace Tests\Unit\Services\Riot;

use App\Services\Riot\DataDragonService;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class DataDragonServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.riot.data_dragon_version', '16.17.1');
        Http::preventStrayRequests();
    }

    public function test_it_builds_a_champion_image_url_with_the_configured_version(): void
    {
        $this->assertSame(
            'https://ddragon.leagueoflegends.com/cdn/16.17.1/img/champion/Aatrox.png',
            app(DataDragonService::class)->championImageUrl('Aatrox'),
        );

        Http::assertNothingSent();
    }

    public function test_it_requires_a_configured_version(): void
    {
        config()->set('services.riot.data_dragon_version');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The Riot Data Dragon version is missing.');

        app(DataDragonService::class)->championImageUrl('Aatrox');
    }
}
