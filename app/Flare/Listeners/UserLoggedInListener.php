<?php

namespace App\Flare\Listeners;

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
        if (is_null(UserSiteAccessStatistics::first())) {

            UserSiteAccessStatistics::create([
                'amount_signed_in'  => 1,
                'amount_registered' => 0,
            ]);

            $adminUser = User::with('roles')->whereHas('roles', function($q) { $q->where('name', 'Admin'); })->first();

            if (is_null($adminUser)) {
                return;
            }

            return broadcast(new UpdateSiteStatisticsChart($adminUser));
        }

        $lastRecord = UserSiteAccessStatistics::orderBy('created_at', 'desc')->first();

        UserSiteAccessStatistics::create([
            'amount_signed_in'  => $lastRecord->amount_signed_in + 1,
            'amount_registered' => $lastRecord->amount_registered,
        ]);

        $adminUser = User::with('roles')->whereHas('roles', function ($q) {
            $q->where('name', 'Admin');
        })->first();

        if (is_null($adminUser)) {
            return;
        }

        return broadcast(new UpdateSiteStatisticsChart($adminUser));
    }
}
