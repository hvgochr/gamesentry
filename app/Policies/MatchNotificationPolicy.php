<?php

namespace App\Policies;

use App\Models\MatchNotification;
use App\Models\User;

class MatchNotificationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->discordServers()->exists();
    }

    public function view(User $user, MatchNotification $matchNotification): bool
    {
        return $user->is($matchNotification->discordServer->user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, MatchNotification $matchNotification): bool
    {
        return false;
    }

    public function delete(User $user, MatchNotification $matchNotification): bool
    {
        return false;
    }

    public function restore(User $user, MatchNotification $matchNotification): bool
    {
        return false;
    }

    public function forceDelete(User $user, MatchNotification $matchNotification): bool
    {
        return false;
    }
}
