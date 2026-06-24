<?php

namespace App\Services\Plans;

use App\Enums\Plan;
use App\Models\DailyUsageCounter;
use App\Models\User;
use App\Services\Plans\Exceptions\PlanLimitExceededException;
use Illuminate\Support\Facades\DB;

class PlanLimitService
{
    private const int FreeDiscordServers = 1;

    private const int FreeWatchedPlayers = 3;

    private const int FreeGroqCallsPerDay = 50;

    private const int ProDiscordServers = 3;

    private const int ProWatchedPlayers = 15;

    private const int ProGroqCallsPerDay = 250;

    public function ensureCanCreateDiscordServer(User $user): void
    {
        $limit = $this->discordServerLimit($user);

        if ($limit !== null && $user->discordServers()->count() >= $limit) {
            throw new PlanLimitExceededException(
                "Your {$user->plan->value} plan allows up to {$limit} linked Discord server"
                .($limit === 1 ? '' : 's').'.',
            );
        }
    }

    public function ensureCanCreateWatchedPlayer(User $user): void
    {
        $limit = $this->watchedPlayerLimit($user);

        if ($limit !== null && $user->watchedPlayers()->count() >= $limit) {
            throw new PlanLimitExceededException(
                "Your {$user->plan->value} plan allows up to {$limit} tracked player"
                .($limit === 1 ? '' : 's').'.',
            );
        }
    }

    public function consumeGroqCall(User $user): void
    {
        if ($user->isAdmin()) {
            return;
        }

        DB::transaction(function () use ($user): void {
            $lockedUser = User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $limit = $this->groqDailyLimit($lockedUser);
            $usageDate = today();

            $counter = DailyUsageCounter::query()
                ->whereBelongsTo($lockedUser)
                ->whereDate('usage_date', $usageDate)
                ->lockForUpdate()
                ->first();

            if ($counter === null) {
                $counter = DailyUsageCounter::query()->create([
                    'user_id' => $lockedUser->id,
                    'usage_date' => $usageDate,
                    'groq_calls' => 0,
                ]);
            }

            if ($counter->groq_calls >= $limit) {
                throw new PlanLimitExceededException(
                    "Your {$lockedUser->plan->value} plan allows up to {$limit} Groq calls per day.",
                );
            }

            $counter->increment('groq_calls');
        }, attempts: 3);
    }

    public function discordServerLimit(User $user): ?int
    {
        if ($user->isAdmin()) {
            return null;
        }

        return match ($user->plan) {
            Plan::Free => self::FreeDiscordServers,
            Plan::Pro => self::ProDiscordServers,
        };
    }

    public function watchedPlayerLimit(User $user): ?int
    {
        if ($user->isAdmin()) {
            return null;
        }

        return match ($user->plan) {
            Plan::Free => self::FreeWatchedPlayers,
            Plan::Pro => self::ProWatchedPlayers,
        };
    }

    public function groqDailyLimit(User $user): ?int
    {
        if ($user->isAdmin()) {
            return null;
        }

        return match ($user->plan) {
            Plan::Free => self::FreeGroqCallsPerDay,
            Plan::Pro => self::ProGroqCallsPerDay,
        };
    }

    public function groqCallsUsedToday(User $user): int
    {
        return (int) $user->dailyUsageCounters()
            ->whereDate('usage_date', today())
            ->value('groq_calls');
    }
}
