<?php

namespace App\Flare\Values;

use Carbon\Carbon;
use App\Flare\Models\UserSiteAccessStatistics;
use Illuminate\Support\Facades\DB;

class SiteAccessStatisticValue {

    /**
     * Gets registered users for the data.
     *
     * @return array
     */
    public static  function getRegistered(): array {
        return [
            'labels' => UserSiteAccessStatistics::whereNotNull('amount_registered')->whereBetween('created_at', [Carbon::today()->subDays(7), Carbon::today()])->get()->map(function($statistic) {
                return $statistic->created_at->format('y-m-d g:i A');
            }),
            'data' => UserSiteAccessStatistics::whereNotNull('amount_registered')->whereBetween('created_at', [Carbon::today()->subDays(7), Carbon::today()])->get()->pluck('amount_registered'),
        ];
    }

    /**
     * Gets signed-in users for the data.
     *
     * @return array
     */
    public static  function getSignedIn(): array {
        return [
            'labels' => UserSiteAccessStatistics::whereNotNull('amount_signed_in')->whereBetween('created_at', [Carbon::today()->subDays(7), Carbon::today()])->get()->map(function($statistic) {
                return $statistic->created_at->format('y-m-d g:i A');
            }),
            'data' => UserSiteAccessStatistics::whereNotNull('amount_signed_in')->whereBetween('created_at', [Carbon::today()->subDays(7), Carbon::today()])->get()->pluck('amount_signed_in'),
        ];
    }
}
