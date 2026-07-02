<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WatchedPlayer;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class AdminWatchedPlayerController extends Controller
{
    public function disable(WatchedPlayer $watchedPlayer): RedirectResponse
    {
        $watchedPlayer->forceFill([
            'is_active' => false,
            'next_poll_at' => null,
        ])->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "{$watchedPlayer->game_name}#{$watchedPlayer->tag_line} tracking disabled.",
        ]);

        return back();
    }
}
