<?php

namespace App\Http\Controllers\Discord;

use App\Http\Controllers\Controller;
use App\Models\DiscordServer;
use App\Services\Discord\DiscordService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;

class DiscordInstallController extends Controller
{
    public function redirect(Request $request, DiscordService $discord): RedirectResponse
    {
        if (! $discord->isConfigured()) {
            return to_route('discord.index')
                ->with('error', 'Configure DISCORD_CLIENT_ID, DISCORD_REDIRECT_URI and DISCORD_BOT_TOKEN environment variables to enable Discord integration.');
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
            return to_route('discord.index')
                ->with('error', 'The installation of the Discord bot has been cancelled.');
        }

        $expectedState = (string) $request->session()->pull('discord.install_state');
        $receivedState = (string) $request->query('state', '');

        if ($expectedState === '' || ! hash_equals($expectedState, $receivedState)) {
            return to_route('discord.index')
                ->with('error', 'The Discord OAuth response is invalid.');
        }

        $guildId = (string) $request->query('guild_id', '');

        if ($guildId === '') {
            return to_route('discord.index')
                ->with('error', 'Discord did not return any servers to sync.');
        }

        try {
            $guild = $discord->installedGuild($guildId);
        } catch (RuntimeException $exception) {
            return to_route('discord.index')
                ->with('error', $exception->getMessage());
        }

        if (! $guild['bot_installed']) {
            return to_route('discord.index')
                ->with('error', 'The Discord bot cannot be found on this server.');
        }

        if ($guild['sync_error'] !== null) {
            return to_route('discord.index')
                ->with('error', $guild['sync_error']);
        }

        if ($guild['channels'] === []) {
            return to_route('discord.index')
                ->with('error', 'No usable text chat room was found on this server.');
        }

        $claimedElsewhere = DiscordServer::query()
            ->where('discord_guild_id', $guild['id'])
            ->where('user_id', '!=', $request->user()->id)
            ->exists();

        if ($claimedElsewhere) {
            return to_route('discord.index')
                ->with('error', 'This Discord server is already linked to another account.');
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

        return to_route('discord.index')
            ->with('status', 'Discord server linked successfully. Check the destination channel if necessary.');
    }
}
