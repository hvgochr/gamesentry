<?php

namespace App\Jobs;

use App\Enums\MatchNotificationStatus;
use App\Models\MatchNotification;
use App\Models\WatchedPlayer;
use App\Services\Discord\DiscordService;
use App\Services\Discord\Exceptions\DiscordDuplicateNonceException;
use App\Services\Groq\GroqService;
use App\Services\Riot\Exceptions\RiotRateLimitException;
use App\Services\Riot\MatchSummaryService;
use App\Services\Riot\RiotApiService;
use App\Services\Riot\RiotPollingService;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ProcessMatchNotificationJob implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 3;

    public int $timeout = 90;

    /**
     * @var list<int>
     */
    public array $backoff = [10, 60, 180];

    public function __construct(public int $matchNotificationId) {}

    public function uniqueId(): string
    {
        return "match-notification:{$this->matchNotificationId}";
    }

    /**
     * @return list<object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("match-notification:{$this->matchNotificationId}"))
                ->releaseAfter(5)
                ->expireAfter(180),
        ];
    }

    public function handle(
        RiotApiService $riot,
        RiotPollingService $polling,
        MatchSummaryService $summary,
        GroqService $groq,
        DiscordService $discord,
    ): void {
        $notification = MatchNotification::query()
            ->with(['watchedPlayer.discordServer', 'discordServer'])
            ->find($this->matchNotificationId);

        if ($notification === null || $notification->status === MatchNotificationStatus::Sent) {
            return;
        }

        $watchedPlayer = $notification->watchedPlayer;
        $discordServer = $notification->discordServer ?? $watchedPlayer?->discordServer;

        if ($watchedPlayer === null || $discordServer === null) {
            return;
        }

        $delivery = $this->prepareDelivery(
            $notification,
            $watchedPlayer,
            $riot,
            $polling,
            $summary,
            $groq,
        );

        try {
            $messageId = $discord->sendMatchNotification(
                $discordServer->discord_channel_id,
                $watchedPlayer->discord_user_id,
                $delivery['roast'],
                $delivery['embed'],
                $delivery['nonce'],
            );
        } catch (DiscordDuplicateNonceException) {
            $messageId = $notification->discord_message_id;
        }

        $notification->forceFill([
            'status' => MatchNotificationStatus::Sent,
            'failure_reason' => null,
            'delivered_at' => $notification->delivered_at ?? now(),
            'discord_message_id' => $messageId ?? $notification->discord_message_id,
        ])->save();
    }

    public function failed(?Throwable $exception): void
    {
        if ($exception === null) {
            return;
        }

        Log::error('Match notification delivery failed.', [
            'match_notification_id' => $this->matchNotificationId,
            'error' => $exception->getMessage(),
        ]);

        $notification = MatchNotification::query()->find($this->matchNotificationId);

        if ($notification === null || $notification->status === MatchNotificationStatus::Sent) {
            return;
        }

        $notification->forceFill([
            'status' => MatchNotificationStatus::Failed,
            'failure_reason' => $exception->getMessage(),
        ])->save();
    }

    /**
     * @return array{
     *     embed: array<string, mixed>,
     *     nonce: string,
     *     payload: array<string, mixed>,
     *     roast: string
     * }
     */
    private function prepareDelivery(
        MatchNotification $notification,
        WatchedPlayer $watchedPlayer,
        RiotApiService $riot,
        RiotPollingService $polling,
        MatchSummaryService $summary,
        GroqService $groq,
    ): array {
        $payload = $notification->match_payload;
        $embed = $notification->discord_embed_payload;
        $roast = $notification->roast_text;
        $nonce = $notification->discord_delivery_nonce ?: Str::random(24);

        if (! is_array($payload) || ! is_array($embed) || ! is_string($roast) || $roast === '') {
            try {
                $match = $riot->match(
                    $watchedPlayer->game,
                    $watchedPlayer->routing_region,
                    $notification->riot_match_id,
                );
            } catch (RiotRateLimitException $exception) {
                $polling->recordRateLimitHit($watchedPlayer->game, $exception->retryAfterSeconds());

                throw $exception;
            }

            $payload = $summary->normalize($watchedPlayer, $match);
            $roast = $groq->generateRoast($payload);
            $embed = $summary->discordEmbed($payload);
        }

        $notification->forceFill([
            'match_payload' => $payload,
            'discord_embed_payload' => $embed,
            'roast_text' => $roast,
            'discord_delivery_nonce' => $nonce,
            'failure_reason' => null,
        ]);

        if ($notification->isDirty([
            'match_payload',
            'discord_embed_payload',
            'roast_text',
            'discord_delivery_nonce',
            'failure_reason',
        ])) {
            $notification->save();
        }

        return [
            'embed' => $embed,
            'nonce' => $nonce,
            'payload' => $payload,
            'roast' => $roast,
        ];
    }
}
