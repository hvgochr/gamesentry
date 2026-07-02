<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiscordServer;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class AdminDiscordServerController extends Controller
{
    public function unlink(DiscordServer $discordServer): RedirectResponse
    {
        $name = $discordServer->name;

        $discordServer->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "{$name} has been unlinked.",
        ]);

        return back();
    }
}
