<?php

namespace App\Flare\Listeners;

use Illuminate\Auth\Events\Logout;
use App\Flare\Events\UpdateSiteStatisticsChart;
use App\Flare\Models\User;
use App\Flare\Models\UserSiteAccessStatistics;

class UserLoggedOutListener {


    /**
     * Handle the event.
     *
     * @param Logout $event
     */
    public function handle(Logout $event) {

        $lastRecord = UserSiteAccessStatistics::orderBy('created_at', 'desc')->first();

        UserSiteAccessStatistics::create([
            'amount_signed_in'  => $lastRecord->amount_signed_in - 1,
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
