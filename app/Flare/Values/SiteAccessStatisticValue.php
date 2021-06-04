<?php

namespace App\Flare\Values;

use App\Flare\Models\UserSiteAccessStatistics;

class SiteAccessStatisticValue {

    /**
     * Gets registered users for the data.
     *
     * @return array
     */
    public static  function getRegistered(): array {
        return [
            'labels' => UserSiteAccessStatistics::whereNotNull('amount_registered')->get()->map(function($statistic) {
                return $statistic->created_at->format('y-m-d g:i A');
            }),
            'data' => UserSiteAccessStatistics::whereNotNull('amount_registered')->get()->pluck('amount_registered'),
        ];
    }

    /**
     * Gets signed in users for the data.
     *
     * @return array
     */
    public static  function getSignedIn(): array {
        return [
            'labels' => UserSiteAccessStatistics::whereNotNull('amount_signed_in')->get()->map(function($statistic) {
                return $statistic->created_at->format('y-m-d g:i A');
            }),
            'data' => UserSiteAccessStatistics::whereNotNull('amount_signed_in')->get()->pluck('amount_signed_in'),
        ];
    }
}
