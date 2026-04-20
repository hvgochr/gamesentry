<?php

namespace App\Http\Controllers\Discord;

use App\Http\Controllers\Controller;
use App\Models\DiscordServer;
use App\Services\Discord\DiscordService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use RuntimeException;

class DiscordInstallController extends Controller
{
    public function redirect(Request $request, DiscordService $discord): RedirectResponse
    {
        if (! $discord->isConfigured()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Configure DISCORD_CLIENT_ID, DISCORD_REDIRECT_URI and DISCORD_BOT_TOKEN environment variables to enable Discord integration.',
            ]);

            return to_route('discord.index');
        }

        $state = Str::random(40);

        $request->session()->put('discord.install_state', $state);

        return redirect()->away($discord->installationUrl(
            state: $state,
            guildId: $request->string('guild_id')->toString() ?: null,
        ));
    }

    public function callback(Request $request, DiscordService $discord): RedirectResponse
    {
        if ($request->filled('error')) {
            return $this->redirectWithToast('error', 'The installation of the Discord bot has been cancelled.');
        }

        $expectedState = (string) $request->session()->pull('discord.install_state');
        $receivedState = (string) $request->query('state', '');

        if ($expectedState === '' || ! hash_equals($expectedState, $receivedState)) {
            return $this->redirectWithToast('error', 'The Discord OAuth response is invalid.');
        }

        $guildId = (string) $request->query('guild_id', '');

        if ($guildId === '') {
            return $this->redirectWithToast('error', 'Discord did not return any servers to sync.');
        }

        try {
            $guild = $discord->installedGuild($guildId);
        } catch (RuntimeException $exception) {
            return $this->redirectWithToast('error', $exception->getMessage());
        }

        if (! $guild['bot_installed']) {
            return $this->redirectWithToast('error', 'The Discord bot cannot be found on this server.');
        }

        if ($guild['sync_error'] !== null) {
            return $this->redirectWithToast('error', $guild['sync_error']);
        }

        if ($guild['channels'] === []) {
            return $this->redirectWithToast('error', 'No usable text chat room was found on this server.');
        }

        $claimedElsewhere = DiscordServer::query()
            ->where('discord_guild_id', $guild['id'])
            ->where('user_id', '!=', $request->user()->id)
            ->exists();

        if ($claimedElsewhere) {
            return $this->redirectWithToast('error', 'This Discord server is already linked to another account.');
        }

        $existingServer = DiscordServer::query()
            ->where('discord_guild_id', $guild['id'])
            ->whereBelongsTo($request->user())
            ->first();

        $channel = collect($guild['channels'])->firstWhere(
            'id',
            $existingServer?->discord_channel_id,
        ) ?? $guild['channels'][0];

        DiscordServer::query()->updateOrCreate(
            ['discord_guild_id' => $guild['id']],
            [
                'user_id' => $request->user()->id,
                'name' => $guild['name'],
                'icon' => $guild['icon'],
                'discord_channel_id' => $channel['id'],
                'discord_channel_name' => $channel['name'],
                'bot_installed_at' => now(),
                'settings_synced_at' => now(),
            ],
        );

        return $this->redirectWithToast('success', 'Discord server linked successfully. Check the destination channel if necessary.');
    }

    private function redirectWithToast(string $type, string $message): RedirectResponse
    {
        Inertia::flash('toast', [
            'type' => $type,
            'message' => $message,
        ]);

        return to_route('discord.index');
    }
}
