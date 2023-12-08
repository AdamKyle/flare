<?php

namespace App\Flare\Listeners;

use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use App\Flare\Events\UpdateSiteStatisticsChart;
use App\Flare\Models\User;
use App\Flare\Models\UserSiteAccessStatistics;

class UserRegisteredListener {


    /**
     * Handle the event.
     *
     * @param Registered $event
     */
    public function handle(Registered $event) {

        if (is_null(UserSiteAccessStatistics::first())) {

            UserSiteAccessStatistics::create([
                'amount_signed_in'  => 1,
                'amount_registered' => 1,
                'invalid_ips'       => [$event->user->ip_address],
                'invalid_user_ids'  => [$event->user->id],
            ]);

            $adminUser = User::with('roles')->whereHas('roles', function($q) { $q->where('name', 'Admin'); })->first();

            if (is_null($adminUser)) {
                return;
            }

            return broadcast(new UpdateSiteStatisticsChart($adminUser));
        }

        $lastRecord = UserSiteAccessStatistics::orderBy('created_at', 'desc')->first();

        if ($lastRecord->created_at->lt(Carbon::today(config('app.timezone')))) {
            UserSiteAccessStatistics::create([
                'amount_signed_in'  => 1,
                'amount_registered' => 1,
                'invalid_ips'       => [$event->user->ip_address],
                'invalid_user_ids'  => [$event->user->id]
            ]);
        } else {

            $invalidUsers = $lastRecord->invalid_user_ids;
            $invalidIps   = $lastRecord->invalid_ips;

            if (is_null($invalidUsers)) {

                UserSiteAccessStatistics::create([
                    'amount_signed_in'  => $lastRecord->amount_signed_in + 1,
                    'amount_registered' => $lastRecord->amount_registered + 1,
                    'invalid_ips'       => [$event->user->ip_address],
                    'invalid_user_ids'  => [$event->user->id]
                ]);
            } else if (!in_array($event->user->id, $invalidUsers)) {
                $invalidIps[] = $event->user->ip_address;
                $userId = $event->user->id;

                if (is_null($invalidUsers)) {
                    $invalidUsers = [$userId];
                } else {
                    $invalidUsers[] = $userId;
                }

                UserSiteAccessStatistics::create([
                    'amount_signed_in'  => $lastRecord->amount_signed_in + 1,
                    'amount_registered' => $lastRecord->amount_registered + 1,
                    'invalid_ips'       => $invalidIps,
                    'invalid_user_ids'  => $invalidUsers,
                ]);
            }
        }

        $adminUser = User::with('roles')->whereHas('roles', function ($q) {
            $q->where('name', 'Admin');
        })->first();

        if (is_null($adminUser)) {
            return;
        }

        return broadcast(new UpdateSiteStatisticsChart($adminUser));
    }
}
