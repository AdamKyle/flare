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
            'labels' => UserSiteAccessStatistics::whereNotNull('amount_registered')->whereDate('created_at', Carbon::today())->get()->map(function($statistic) {
                return $statistic->created_at->format('y-m-d g:i A');
            }),
            'data' => UserSiteAccessStatistics::whereNotNull('amount_registered')->whereDate('created_at', Carbon::today())->get()->pluck('amount_registered'),
        ];
    }

    /**
     * Gets signed in users for the data.
     *
     * @return array
     */
    public static  function getSignedIn(): array {
        return [
            'labels' => UserSiteAccessStatistics::whereNotNull('amount_signed_in')->whereDate('created_at', Carbon::today())->get()->map(function($statistic) {
                return $statistic->created_at->format('y-m-d g:i A');
            }),
            'data' => UserSiteAccessStatistics::whereNotNull('amount_signed_in')->whereDate('created_at', Carbon::today())->get()->pluck('amount_signed_in'),
        ];
    }

    /**
     * Fetches all time logged in statistics
     *
     * @return array
     */
    public static function getAllTimeSignedIn(): array {
        $result = self::fetchData();

        $labels = [];
        $data   = [];

        foreach ($result as $dataSet) {
            $labels[] = Carbon::parse($dataSet->created_at)->format('Y-m-d');
            $data[]   = $dataSet->amount_signed_in;
        }

        return [
            'labels' => $labels,
            'data'   => $data
        ];
    }

    public static function getAllTimeRegistered(): array {
        $result = self::fetchData();

        $labels = [];
        $data   = [];

        foreach ($result as $dataSet) {
            $labels[] = Carbon::parse($dataSet->created_at)->format('Y-m-d');
            $data[]   = $dataSet->amount_registered;
        }

        return [
            'labels' => $labels,
            'data'   => $data
        ];
    }

    /**
     * Fetches the "All time" data
     *
     * @return array
     */
    protected static function fetchData(): array {
        return DB::select(DB::raw('
            SELECT amount_signed_in, amount_registered, created_at, updated_at, rnum
            FROM
            (SELECT *,
                   ROW_NUMBER() OVER (PARTITION BY DATE(created_at) ORDER BY created_at DESC) AS rnum
            FROM user_site_access_statistics) A
            WHERE rnum=1;
        '));
    }
}
