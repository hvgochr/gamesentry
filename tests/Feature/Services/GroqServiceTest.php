<?php

namespace Tests\Feature\Services;

use App\Services\Groq\GroqService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class GroqServiceTest extends TestCase
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

    public function test_generate_roast_returns_trimmed_content_and_sends_the_summary_payload(): void
    {
        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => '  Un roast bien sale.  ']],
                ],
            ], 200),
        ]);

        $roast = app(GroqService::class)->generateRoast([
            'title' => 'Ahri - victory',
            'player' => ['champion' => 'Ahri'],
        ]);

        $this->assertSame('Un roast bien sale.', $roast);

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://api.groq.com/openai/v1/chat/completions'
                && ($request->data()['model'] ?? null) === 'llama-3.1-8b-instant'
                && str_contains((string) data_get($request->data(), 'messages.1.content'), '"title": "Ahri - victory"');
        });
    }

    public function test_generate_roast_throws_when_groq_returns_an_http_error(): void
    {
        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([], 500),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The Groq roast failed to generate.');

        app(GroqService::class)->generateRoast(['title' => 'Ahri - victory']);
    }

    public function test_generate_roast_throws_when_groq_returns_an_empty_message(): void
    {
        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => '   ']],
                ],
            ], 200),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Groq returned an empty response.');

        app(GroqService::class)->generateRoast(['title' => 'Ahri - victory']);
    }

    public function test_generate_roast_throws_when_configuration_is_missing(): void
    {
        config()->set('services.groq.api_key', null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Groq configuration is incomplete.');

        app(GroqService::class)->generateRoast(['title' => 'Ahri - victory']);
    }
}
