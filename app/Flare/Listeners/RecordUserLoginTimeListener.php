<?php

namespace App\Flare\Listeners;

use App\Flare\Models\UserLoginDuration;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;

class RecordUserLoginTimeListener
{
    /**
     * Handle the event.
     */
    public function handle(Login|Registered $event)
    {

        $user = $event->user;

        if ($user->hasRole('Admin')) {
            return;
        }

        DB::transaction(function () use ($user): void {
            $openSession = UserLoginDuration::where('user_id', $user->id)
                ->whereNull('logged_out_at')
                ->whereNull('duration_in_seconds')
                ->latest('logged_in_at')
                ->lockForUpdate()
                ->first();

            if (! is_null($openSession)) {
                $loggedOutAt = $openSession->last_heart_beat ?? $openSession->logged_in_at;

                if ($loggedOutAt->lt($openSession->logged_in_at)) {
                    $loggedOutAt = $openSession->logged_in_at;
                }

                $openSession->update([
                    'logged_out_at' => $loggedOutAt,
                    'duration_in_seconds' => $openSession->logged_in_at->diffInSeconds($loggedOutAt),
                ]);
            }

            UserLoginDuration::create([
                'user_id' => $user->id,
                'logged_in_at' => now(),
                'last_heart_beat' => now(),
                'last_activity' => now(),
            ]);
        });
    }
}
