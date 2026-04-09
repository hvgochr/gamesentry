<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\MatchNotificationStatus;
use App\Models\DiscordServer;
use App\Models\MatchNotification;
use App\Models\WatchedPlayer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $serverIds = DiscordServer::query()
            ->whereBelongsTo($user)
            ->pluck('id');

        $watchedPlayers = WatchedPlayer::query()
            ->whereIn('discord_server_id', $serverIds);

        $notifications = MatchNotification::query()
            ->whereIn('discord_server_id', $serverIds);

        $recentNotifications = MatchNotification::query()
            ->with(['discordServer:id,name', 'watchedPlayer:id,game_name,tag_line'])
            ->whereIn('discord_server_id', $serverIds)
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (MatchNotification $notification) => [
                'id' => $notification->id,
                'game' => $notification->game->value,
                'riot_match_id' => $notification->riot_match_id,
                'status' => $notification->status->value,
                'server_name' => $notification->discordServer?->name,
                'player_name' => $notification->watchedPlayer === null
                    ? null
                    : "{$notification->watchedPlayer->game_name}#{$notification->watchedPlayer->tag_line}",
                'roast_text' => $notification->roast_text,
                'failure_reason' => $notification->failure_reason,
                'delivered_at' => $notification->delivered_at?->toIso8601String(),
                'created_at' => $notification->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        return Inertia::render('dashboard', [
            'stats' => [
                'servers_count' => $serverIds->count(),
                'watched_players_count' => (clone $watchedPlayers)->count(),
                'active_watched_players_count' => (clone $watchedPlayers)->where('is_active', true)->count(),
                'sent_notifications_count' => (clone $notifications)
                    ->where('status', MatchNotificationStatus::Sent->value)
                    ->count(),
                'pending_notifications_count' => (clone $notifications)
                    ->where('status', MatchNotificationStatus::Pending->value)
                    ->count(),
                'failed_notifications_count' => (clone $notifications)
                    ->where('status', MatchNotificationStatus::Failed->value)
                    ->count(),
            ],
            'recentNotifications' => $recentNotifications,
        ]);
    }
}
