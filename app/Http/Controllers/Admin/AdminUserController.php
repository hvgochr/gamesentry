<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminUserController extends Controller
{
    public function pause(Request $request, User $user): RedirectResponse
    {
        abort_if($request->user()->is($user), 422, 'Admins cannot pause their own account.');

        $user->forceFill([
            'paused_at' => $user->paused_at ?? now(),
        ])->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "{$user->name} has been paused.",
        ]);

        return back();
    }

    public function resume(User $user): RedirectResponse
    {
        $user->forceFill([
            'paused_at' => null,
        ])->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "{$user->name} has been resumed.",
        ]);

        return back();
    }
}
