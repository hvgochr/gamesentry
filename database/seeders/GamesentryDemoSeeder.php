<?php

namespace Database\Seeders;

use App\Models\DiscordServer;
use App\Models\MatchNotification;
use App\Models\User;
use App\Models\WatchedPlayer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class GamesentryDemoSeeder extends Seeder
{
    public function run(): void
    {
        $demoUser = User::query()->updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        $demoUser->discordServers()->delete();

        $leagueServer = DiscordServer::factory()
            ->for($demoUser)
            ->create([
                'discord_guild_id' => '123456789012345601',
                'name' => 'Gamesentry HQ',
                'discord_channel_id' => '223456789012345601',
                'discord_channel_name' => 'match-roasts',
            ]);

        $tftServer = DiscordServer::factory()
            ->for($demoUser)
            ->staleSettings()
            ->create([
                'discord_guild_id' => '123456789012345602',
                'name' => 'TFT Lab',
                'discord_channel_id' => '223456789012345602',
                'discord_channel_name' => 'tft-highlights',
            ]);

        $offlineServer = DiscordServer::factory()
            ->for($demoUser)
            ->withoutBot()
            ->create([
                'discord_guild_id' => '123456789012345603',
                'name' => 'Offline Practice Realm',
                'discord_channel_id' => '223456789012345603',
                'discord_channel_name' => 'bot-setup',
            ]);

        $this->seedLeagueServer($leagueServer);
        $this->seedTftServer($tftServer);
        $this->seedOfflineServer($offlineServer);
    }

    private function seedLeagueServer(DiscordServer $discordServer): void
    {
        $shiro = WatchedPlayer::factory()
            ->for($discordServer)
            ->leagueOfLegends()
            ->dueForPolling()
            ->create([
                'game_name' => 'Shiro',
                'tag_line' => 'EUW',
                'discord_user_id' => '300000000000000001',
            ]);

        $nova = WatchedPlayer::factory()
            ->for($discordServer)
            ->leagueOfLegends()
            ->neverPolled()
            ->create([
                'game_name' => 'NovaMid',
                'tag_line' => 'FR1',
                'discord_user_id' => '300000000000000002',
            ]);

        $benchwarmer = WatchedPlayer::factory()
            ->for($discordServer)
            ->leagueOfLegends()
            ->inactive()
            ->create([
                'game_name' => 'BenchWarmer',
                'tag_line' => 'NA1',
                'discord_user_id' => '300000000000000003',
            ]);

        MatchNotification::factory()
            ->for($shiro, 'watchedPlayer')
            ->for($discordServer, 'discordServer')
            ->sent()
            ->count(3)
            ->create();

        MatchNotification::factory()
            ->for($nova, 'watchedPlayer')
            ->for($discordServer, 'discordServer')
            ->failed()
            ->count(1)
            ->create([
                'failure_reason' => 'Discord rejected the notification delivery after a retry.',
            ]);

        MatchNotification::factory()
            ->for($nova, 'watchedPlayer')
            ->for($discordServer, 'discordServer')
            ->pending()
            ->count(1)
            ->create();

        MatchNotification::factory()
            ->for($benchwarmer, 'watchedPlayer')
            ->for($discordServer, 'discordServer')
            ->sent()
            ->count(1)
            ->create();
    }

    private function seedOfflineServer(DiscordServer $discordServer): void
    {
        WatchedPlayer::factory()
            ->for($discordServer)
            ->leagueOfLegends()
            ->inactive()
            ->count(2)
            ->create();
    }

    private function seedTftServer(DiscordServer $discordServer): void
    {
        $carouselKing = WatchedPlayer::factory()
            ->for($discordServer)
            ->teamfightTactics()
            ->dueForPolling()
            ->create([
                'game_name' => 'CarouselKing',
                'tag_line' => 'TFT',
                'discord_user_id' => '300000000000000011',
            ]);

        $econMaster = WatchedPlayer::factory()
            ->for($discordServer)
            ->teamfightTactics()
            ->neverPolled()
            ->create([
                'game_name' => 'EconMaster',
                'tag_line' => 'LP',
                'discord_user_id' => '300000000000000012',
            ]);

        MatchNotification::factory()
            ->for($carouselKing, 'watchedPlayer')
            ->for($discordServer, 'discordServer')
            ->sent()
            ->count(2)
            ->create();

        MatchNotification::factory()
            ->for($econMaster, 'watchedPlayer')
            ->for($discordServer, 'discordServer')
            ->pending()
            ->count(1)
            ->create();
    }
}
