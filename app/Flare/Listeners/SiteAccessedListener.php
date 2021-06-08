<?php

namespace App\Flare\Listeners;

use App\Flare\Events\SiteAccessedEvent;
use App\Flare\Events\UpdateSiteStatisticsChart;
use App\Flare\Models\Session;
use App\Flare\Models\User;
use App\Flare\Models\UserSiteAccessStatistics;

class SiteAccessedListener {


    /**
     * Handle the event.
     *
     * @param SiteAccessedEvent $event
     */
    public function handle(SiteAccessedEvent $event) {

        if (is_null(UserSiteAccessStatistics::first()) && !$event->loggedOut && ($event->signIn || $event->register)) {

            UserSiteAccessStatistics::create([
                'amount_signed_in' => $event->signIn ? Session::count() : 0,
                'amount_registered' => $event->register ? 1 : 0,
            ]);

            $adminUser = User::with('roles')->whereHas('roles', function($q) { $q->where('name', 'Admin'); })->first();

            if (is_null($adminUser)) {
                return;
            }

            return broadcast(new UpdateSiteStatisticsChart($adminUser));
        }

        $lastRecord = UserSiteAccessStatistics::orderBy('created_at', 'desc')->first();

        if ($event->signIn && $event->register) {
            UserSiteAccessStatistics::create([
                'amount_signed_in' => Session::count(),
                'amount_registered' => is_null($lastRecord->amount_registered) ? 1 : $lastRecord->amount_registered + 1,
            ]);

            $adminUser = User::with('roles')->whereHas('roles', function ($q) {
                $q->where('name', 'Admin');
            })->first();

            if (is_null($adminUser)) {
                return;
            }

            return broadcast(new UpdateSiteStatisticsChart($adminUser));
        }

        if ($event->signIn) {
            UserSiteAccessStatistics::create([
                'amount_signed_in' => is_null($lastRecord->amount_signed_in) ? 0 : Session::count(),
            ]);

            $adminUser = User::with('roles')->whereHas('roles', function ($q) {
                $q->where('name', 'Admin');
            })->first();

            if (is_null($adminUser)) {
                return;
            }

            return broadcast(new UpdateSiteStatisticsChart($adminUser));
        }

        if ($event->loggedOut && !is_null($lastRecord->amount_signed_in)) {
            UserSiteAccessStatistics::create([
                'amount_signed_in' => Session::count()
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
}
