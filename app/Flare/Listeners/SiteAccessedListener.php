<?php

namespace App\Flare\Listeners;

use App\Flare\Events\SiteAccessedEvent;
use App\Flare\Events\UpdateSiteStatisticsChart;
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
                'amount_signed_in' => $event->signIn ? 1 : 0,
                'amount_registered' => $event->register ? 1 : 0,
            ]);

            $adminUser = User::with('roles')->whereHas('roles', function($q) { $q->where('name', 'Admin'); })->first();

            if (is_null($adminUser)) {
                return;
            }

            return broadcast(new UpdateSiteStatisticsChart($adminUser));
        }

        $lastRecord = UserSiteAccessStatistics::orderBy('created_at', 'desc')->first();

        if (is_null($lastRecord)) {
            $adminUser = User::with('roles')->whereHas('roles', function ($q) {
                $q->where('name', 'Admin');
            })->first();

            if (is_null($adminUser)) {
                return;
            }

            return broadcast(new UpdateSiteStatisticsChart($adminUser));
        }

        if ($event->signIn && $event->register) {
            UserSiteAccessStatistics::create([
                'amount_signed_in' => is_null($lastRecord->amount_signed_in) ? 1 : $lastRecord->amount_signed_in + 1,
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
                'amount_signed_in' => is_null($lastRecord->amount_signed_in) ? 1 : $lastRecord->amount_signed_in + 1,
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
            $newAmount = $lastRecord->amount_signed_in - 1;

            UserSiteAccessStatistics::create([
                'amount_signed_in' => $newAmount < 0 ? 0 : $newAmount
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
