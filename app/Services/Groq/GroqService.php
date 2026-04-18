<?php

namespace App\Services\Groq;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GroqService
{
    public function isConfigured(): bool
    {
        return filled(config('services.groq.api_key')) && filled(config('services.groq.model'));
    }

    /**
     * @param  array<string, mixed>  $matchSummary
     */
    public function generateRoast(array $matchSummary): string
    {
        $this->ensureConfigured();

        $response = $this->request()->post('/chat/completions', [
            'model' => config('services.groq.model'),
            'temperature' => 0.9,
            'max_completion_tokens' => 120,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => implode("\n", [
                        'You are GameSentry.',
                        'Write a short, funny and playful roast in French for your friends on Discord.',
                        'Keep it 1 or 2 sentences.',
                        'Never use content that is discriminatory, harassing, or based on protected characteristics.',
                        'Do not make up any statistics that are not included in the game summary.',
                    ]),
                ],
                [
                    'role' => 'user',
                    'content' => $this->promptFromSummary($matchSummary),
                ],
            ],
        ]);

        if ($response->failed()) {
            throw new RuntimeException('The Groq roast failed to generate.');
        }

        $content = trim((string) data_get($response->json(), 'choices.0.message.content'));

        if ($content === '') {
            throw new RuntimeException('Groq returned an empty response.');
        }

        return $content;
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl((string) config('services.groq.base_url'))
            ->acceptJson()
            ->timeout(15)
            ->connectTimeout(3)
            ->withToken((string) config('services.groq.api_key'));
    }

    /**
     * @param  array<string, mixed>  $matchSummary
     */
    private function promptFromSummary(array $matchSummary): string
    {
        return json_encode($matchSummary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ?: 'Unavailable match summary.';
    }

    private function ensureConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Groq configuration is incomplete.');
        }
    }
}
