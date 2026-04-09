<?php

declare(strict_types=1);

namespace App\Services\Riot;

use App\Enums\Game;
use App\Services\Riot\Exceptions\RiotRateLimitException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class RiotApiService
{
    public function isConfigured(Game $game): bool
    {
        return filled($this->apiKey($game));
    }

    /**
     * @return array{puuid: string, gameName?: string, tagLine?: string}|null
     */
    public function resolveAccountByRiotId(
        Game $game,
        string $routingRegion,
        string $gameName,
        string $tagLine,
    ): ?array {
        $this->ensureConfigured($game);

        $response = $this->request($game, $routingRegion)->get(
            '/riot/account/v1/accounts/by-riot-id/'
            .rawurlencode($gameName).'/'.rawurlencode($tagLine),
        );

        if ($response->status() === 404) {
            return null;
        }

        if ($response->status() === 429) {
            throw new RiotRateLimitException(
                retryAfterSeconds: $this->retryAfterSeconds($response),
            );
        }

        if ($response->failed()) {
            throw new RuntimeException('Unable to resolve this Riot ID.');
        }

        return $response->json();
    }

    public function latestMatchId(Game $game, string $routingRegion, string $puuid): ?string
    {
        return $this->recentMatchIds($game, $routingRegion, $puuid, 1)[0] ?? null;
    }

    /**
     * @return list<string>
     */
    public function recentMatchIds(Game $game, string $routingRegion, string $puuid, int $count = 10): array
    {
        $this->ensureConfigured($game);

        $response = $this->request($game, $routingRegion)->get(
            $this->latestMatchesPath($game, $puuid),
            [
                'start' => 0,
                'count' => max($count, 1),
            ],
        );

        if ($response->status() === 404) {
            return [];
        }

        $this->throwForFailedResponse($response, 'Unable to retrieve this player\'s recent matches.');

        return $response->collect()->map(fn (mixed $matchId) => (string) $matchId)->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function match(Game $game, string $routingRegion, string $matchId): array
    {
        $this->ensureConfigured($game);

        $response = $this->request($game, $routingRegion)->get($this->matchPath($game, $matchId));

        if ($response->status() === 404) {
            throw new RuntimeException('This Riot match can\'t be found.');
        }

        $this->throwForFailedResponse($response, 'Unable to retrieve the match details.');

        return $response->json();
    }

    private function request(Game $game, string $routingRegion): PendingRequest
    {
        return Http::baseUrl("https://{$routingRegion}.api.riotgames.com")
            ->acceptJson()
            ->timeout(10)
            ->connectTimeout(3)
            ->withHeaders([
                'X-Riot-Token' => $this->apiKey($game),
            ]);
    }

    private function latestMatchesPath(Game $game, string $puuid): string
    {
        return match ($game) {
            Game::LeagueOfLegends => '/lol/match/v5/matches/by-puuid/'.rawurlencode($puuid).'/ids',
            Game::TeamfightTactics => '/tft/match/v1/matches/by-puuid/'.rawurlencode($puuid).'/ids',
        };
    }

    private function matchPath(Game $game, string $matchId): string
    {
        return match ($game) {
            Game::LeagueOfLegends => '/lol/match/v5/matches/'.rawurlencode($matchId),
            Game::TeamfightTactics => '/tft/match/v1/matches/'.rawurlencode($matchId),
        };
    }

    private function apiKey(Game $game): ?string
    {
        return match ($game) {
            Game::LeagueOfLegends => config('services.riot.lol_key'),
            Game::TeamfightTactics => config('services.riot.tft_key'),
        };
    }

    private function ensureConfigured(Game $game): void
    {
        if (! $this->isConfigured($game)) {
            throw new RuntimeException(
                match ($game) {
                    Game::LeagueOfLegends => 'The League of Legends Riot api key is missing.',
                    Game::TeamfightTactics => 'The TFT Riot api key is missing.',
                },
            );
        }
    }

    private function throwForFailedResponse(Response $response, string $message): void
    {
        if ($response->status() === 429) {
            throw new RiotRateLimitException(
                retryAfterSeconds: $this->retryAfterSeconds($response),
            );
        }

        if ($response->failed()) {
            throw new RuntimeException($message);
        }
    }

    private function retryAfterSeconds(Response $response): ?int
    {
        $retryAfter = $response->header('Retry-After');

        return is_numeric($retryAfter) ? (int) $retryAfter : null;
    }
}
