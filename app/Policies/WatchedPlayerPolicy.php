<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WatchedPlayer;

class WatchedPlayerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->discordServers()->exists();
    }

    public function view(User $user, WatchedPlayer $watchedPlayer): bool
    {
        return $user->is($watchedPlayer->discordServer->user);
    }

    public function create(User $user): bool
    {
        return $user->discordServers()->exists();
    }

    public function update(User $user, WatchedPlayer $watchedPlayer): bool
    {
        return $user->is($watchedPlayer->discordServer->user);
    }

    public function delete(User $user, WatchedPlayer $watchedPlayer): bool
    {
        return $user->is($watchedPlayer->discordServer->user);
    }

    public function restore(User $user, WatchedPlayer $watchedPlayer): bool
    {
        return $user->is($watchedPlayer->discordServer->user);
    }

    public function forceDelete(User $user, WatchedPlayer $watchedPlayer): bool
    {
        return $user->is($watchedPlayer->discordServer->user);
    }
}
