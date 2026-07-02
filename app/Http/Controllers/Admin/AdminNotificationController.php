<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MatchNotificationStatus;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessMatchNotificationJob;
use App\Models\MatchNotification;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class AdminNotificationController extends Controller
{
    public function retry(MatchNotification $notification): RedirectResponse
    {
        $notification->forceFill([
            'status' => MatchNotificationStatus::Pending,
            'failure_reason' => null,
        ])->save();

        ProcessMatchNotificationJob::dispatch($notification->id);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Notification queued for retry.',
        ]);

        return back();
    }
}
