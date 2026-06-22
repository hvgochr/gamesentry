<?php

namespace App\Services\Discord;

use App\Services\Discord\Exceptions\DiscordDuplicateNonceException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class DiscordService
{
    private const string ApiBaseUrl = 'https://discord.com/api/v10';

    /**
     * @var list<int>
     */
    private const array SupportedChannelTypes = [0, 5];

    public function isConfigured(): bool
    {
        return filled(config('services.discord.client_id'))
            && filled(config('services.discord.redirect'))
            && filled(config('services.discord.bot_token'));
    }

    public function installationUrl(string $state, ?string $guildId = null): string
    {
        $this->ensureConfigured();

        return 'https://discord.com/oauth2/authorize?'.http_build_query(array_filter([
            'client_id' => config('services.discord.client_id'),
            'redirect_uri' => config('services.discord.redirect'),
            'response_type' => 'code',
            'scope' => 'bot applications.commands',
            'permissions' => config('services.discord.bot_permissions'),
            'state' => $state,
            'guild_id' => $guildId,
            'disable_guild_select' => $guildId === null ? null : 'true',
            'prompt' => 'consent',
        ], fn (mixed $value) => $value !== null));
    }

    /**
     * @return array{
     *     id: string,
     *     name: string,
     *     icon: string|null,
     *     icon_url: string|null,
     *     bot_installed: bool,
     *     channels: list<array{id: string, name: string}>,
     *     sync_error: string|null
     * }
     */
    public function installedGuild(
        string $guildId,
        ?string $fallbackName = null,
        ?string $fallbackIcon = null,
    ): array {
        $this->ensureConfigured();

        $guildResponse = $this->botRequest()->get("/guilds/{$guildId}");

        if (in_array($guildResponse->status(), [403, 404], true)) {
            return [
                'id' => $guildId,
                'name' => $fallbackName ?? $guildId,
                'icon' => $fallbackIcon,
                'icon_url' => $this->guildIconUrl($guildId, $fallbackIcon),
                'bot_installed' => false,
                'channels' => [],
                'sync_error' => null,
            ];
        }

        if ($guildResponse->failed()) {
            throw new RuntimeException('Unable to verify Discord guild installation status.');
        }

        $guild = $guildResponse->json();

        if (! is_array($guild)
            || ! is_string($guild['id'] ?? null)
            || ! is_string($guild['name'] ?? null)
            || (! is_string($guild['icon'] ?? null) && ($guild['icon'] ?? null) !== null)) {
            throw new RuntimeException('Discord returned an invalid guild response.');
        }

        $channelsResponse = $this->botRequest()->get("/guilds/{$guildId}/channels");
        $channels = [];
        $syncError = null;

        if ($channelsResponse->successful()) {
            $channels = $this->normalizeChannels($channelsResponse->json());
        } else {
            $syncError = "Unable to sync the {$guild['name']} guild channels.";
        }

        return [
            'id' => (string) $guild['id'],
            'name' => $guild['name'],
            'icon' => $guild['icon'] ?? null,
            'icon_url' => $this->guildIconUrl((string) $guild['id'], $guild['icon'] ?? null),
            'bot_installed' => true,
            'channels' => $channels,
            'sync_error' => $syncError,
        ];
    }

    public function guildMemberExists(string $guildId, string $discordUserId): bool
    {
        $this->ensureConfigured();

        $response = $this->botRequest()->get("/guilds/{$guildId}/members/{$discordUserId}");

        if ($response->successful()) {
            return true;
        }

        if ($response->status() === 404) {
            return false;
        }

        throw new RuntimeException('Unable to verify Discord guild member existence.');
    }

    /**
     * @param  array<string, mixed>  $embed
     */
    public function sendMatchNotification(
        string $channelId,
        string $discordUserId,
        string $roast,
        array $embed,
        string $nonce,
    ): ?string {
        $this->ensureConfigured();

        $response = $this->botRequest()->post("/channels/{$channelId}/messages", [
            'content' => "<@{$discordUserId}> {$roast}",
            'allowed_mentions' => [
                'users' => [$discordUserId],
            ],
            'embeds' => [$embed],
            'nonce' => $nonce,
            'enforce_nonce' => true,
        ]);

        if ($this->isDuplicateNonceResponse($response)) {
            throw new DiscordDuplicateNonceException;
        }

        if ($response->failed()) {
            throw new RuntimeException('Unable to send Discord match notification.');
        }

        $messageId = $response->json('id');

        return is_string($messageId) && $messageId !== '' ? $messageId : null;
    }

    public function guildIconUrl(string $guildId, ?string $iconHash): ?string
    {
        if (blank($iconHash)) {
            return null;
        }

        return "https://cdn.discordapp.com/icons/{$guildId}/{$iconHash}.png?size=128";
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    private function normalizeChannels(mixed $payload): array
    {
        if (! is_array($payload)) {
            throw new RuntimeException('Discord returned an invalid channels response.');
        }

        $channels = [];

        foreach ($payload as $channel) {
            if (! is_array($channel)
                || ! is_int($channel['type'] ?? null)
                || ! in_array($channel['type'], self::SupportedChannelTypes, true)
                || ! is_string($channel['id'] ?? null)
                || ! is_string($channel['name'] ?? null)) {
                continue;
            }

            $channels[] = [
                'id' => $channel['id'],
                'name' => $channel['name'],
                'position' => is_int($channel['position'] ?? null) ? $channel['position'] : PHP_INT_MAX,
            ];
        }

        usort(
            $channels,
            static fn (array $left, array $right): int => $left['position'] <=> $right['position'],
        );

        return array_map(
            static fn (array $channel): array => [
                'id' => $channel['id'],
                'name' => $channel['name'],
            ],
            $channels,
        );
    }

    private function botRequest(): PendingRequest
    {
        $this->ensureConfigured();

        return Http::baseUrl(self::ApiBaseUrl)
            ->acceptJson()
            ->timeout(10)
            ->connectTimeout(3)
            ->withHeaders([
                'Authorization' => 'Bot '.config('services.discord.bot_token'),
            ]);
    }

    private function isDuplicateNonceResponse(Response $response): bool
    {
        if ($response->status() !== 400 || (int) $response->json('code') !== 50035) {
            return false;
        }

        $nonceErrors = $response->json('errors.nonce._errors', []);

        if (! is_array($nonceErrors)) {
            return false;
        }

        foreach ($nonceErrors as $error) {
            if (data_get($error, 'code') === 'ENFORCE_UNIQUE') {
                return true;
            }
        }

        return false;
    }

    private function ensureConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Discord service is not properly configured.');
        }
    }
}
