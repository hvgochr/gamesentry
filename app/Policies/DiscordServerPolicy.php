<?php

namespace App\Policies;

use App\Models\DiscordServer;
use App\Models\User;

class DiscordServerPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, DiscordServer $discordServer): bool
    {
        return $user->is($discordServer->user);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, DiscordServer $discordServer): bool
    {
        return $user->is($discordServer->user);
    }

    public function delete(User $user, DiscordServer $discordServer): bool
    {
        return $user->is($discordServer->user);
    }

    public function restore(User $user, DiscordServer $discordServer): bool
    {
        return $user->is($discordServer->user);
    }

    public function forceDelete(User $user, DiscordServer $discordServer): bool
    {
        return $user->is($discordServer->user);
    }
}
