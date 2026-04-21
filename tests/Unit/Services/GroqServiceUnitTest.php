<?php

namespace Tests\Unit\Services;

use App\Services\Groq\GroqService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class GroqServiceUnitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.groq.api_key' => 'groq-key',
            'services.groq.model' => 'llama-3.1-8b-instant',
            'services.groq.base_url' => 'https://api.groq.com/openai/v1',
        ]);

        Http::preventStrayRequests();
    }

    public function test_is_configured_requires_both_the_api_key_and_model(): void
    {
        $service = app(GroqService::class);

        $this->assertTrue($service->isConfigured());

        config()->set('services.groq.model', null);
        $this->assertFalse($service->isConfigured());

        config()->set('services.groq.model', 'llama-3.1-8b-instant');
        config()->set('services.groq.api_key', null);
        $this->assertFalse($service->isConfigured());
    }

    public function test_generate_roast_serializes_the_match_summary_as_json_in_the_prompt(): void
    {
        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Un roast propre.']],
                ],
            ], 200),
        ]);

        $roast = app(GroqService::class)->generateRoast([
            'title' => 'Victoire',
            'riot_id' => 'Player#EUW',
            'player' => [
                'champion' => 'Ahri',
                'note' => 'étoile',
            ],
        ]);

        $this->assertSame('Un roast propre.', $roast);

        Http::assertSent(function (Request $request): bool {
            $content = (string) data_get($request->data(), 'messages.1.content');

            return $request->url() === 'https://api.groq.com/openai/v1/chat/completions'
                && str_contains($content, '"riot_id": "Player#EUW"')
                && str_contains($content, '"note": "étoile"');
        });
    }

    public function test_generate_roast_throws_before_sending_requests_when_configuration_is_missing(): void
    {
        config()->set('services.groq.api_key', null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Groq configuration is incomplete.');

        app(GroqService::class)->generateRoast(['title' => 'Victoire']);
    }
}
