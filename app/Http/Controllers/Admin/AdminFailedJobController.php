<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;

class AdminFailedJobController extends Controller
{
    public function retry(string $uuid): RedirectResponse
    {
        Artisan::call('queue:retry', [
            'id' => [$uuid],
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Failed job queued for retry.',
        ]);

        return back();
    }
}
