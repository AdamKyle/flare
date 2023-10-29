<?php

namespace App\Flare\Values;

use Carbon\Carbon;
use App\Flare\Models\UserSiteAccessStatistics;
use Illuminate\Database\Eloquent\Collection;

class SiteAccessStatisticValue {

    /**
     * Gets registered users for the data.
     *
     * @return array
     */
    public static function getRegistered(int $daysPast = 0): array {
        $statistics = self::getQuery('amount_registered', $daysPast);

        $labels = $statistics->pluck('created_at')->map(function ($date) {
            return $date->format('y-m-d g:i A');
        });

        $data = $statistics->pluck('amount_registered');

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }


    /**
     * Gets signed-in users for the data.
     *
     * @return array
     */
    public static  function getSignedIn(int $daysPast = 0): array {
        $statistics = self::getQuery('amount_signed_in', $daysPast);

        $labels = $statistics->pluck('created_at')->map(function ($date) {
            return $date->format('y-m-d g:i A');
        });

        $data = $statistics->pluck('amount_signed_in');

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    protected static function getQuery(string $attribute, int $daysPast = 0): Collection {

        $start = Carbon::today();
        $end   = Carbon::today()->endOfDay();

        if ($daysPast > 0) {
            $start = Carbon::today()->subDays($daysPast)->startOfDay();
        }

        if ($daysPast >= 30) {
            $start = Carbon::now()->startOfMonth();
            $end   = Carbon::now()->endOfMonth();
        }

        return UserSiteAccessStatistics::whereNotNull($attribute)
            ->whereBetween('created_at', [
                $start,
                $end
            ])
            ->select($attribute, 'created_at')
            ->get();
    }
}
