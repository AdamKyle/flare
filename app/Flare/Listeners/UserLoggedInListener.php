<?php

namespace App\Flare\Listeners;

use Carbon\Carbon;
use Illuminate\Auth\Events\Login;
use App\Flare\Events\UpdateSiteStatisticsChart;
use App\Flare\Models\User;
use App\Flare\Models\UserSiteAccessStatistics;

class UserLoggedInListener {


    /**
     * Handle the event.
     *
     * @param Login $event
     */
    public function handle(Login $event) {

        $event->user->last_logged_in  = now();
        $event->user->will_be_deleted = false;
        $event->user->save();

        if (is_null(UserSiteAccessStatistics::first())) {

            UserSiteAccessStatistics::create([
                'amount_signed_in'  => 1,
                'amount_registered' => 0,
                'invalid_ips'       => [$event->user->ip_address]
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
                'amount_registered' => 0,
                'invalid_ips'       => [$event->user->ip_address]
            ]);
        } else {

            $invalidIps = $lastRecord->invalid_ips;

            if (!in_array($event->user->ip_address, $invalidIps)) {
                $invalidIps[] = $event->user->ip_address;

                UserSiteAccessStatistics::create([
                    'amount_signed_in'  => $lastRecord->amount_signed_in + 1,
                    'amount_registered' => $lastRecord->amount_registered,
                    'invalid_ips'       => $invalidIps,
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
