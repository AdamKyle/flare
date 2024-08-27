<?php

namespace App\Flare\Values;

use App\Flare\Models\UserSiteAccessStatistics;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Collection;

class SiteAccessStatisticValue
{
    /**
     * Gets registered users for the data.
     */
    public static function getRegistered(int $daysPast = 0): array
    {
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
     */
    public static function getSignedIn(int $daysPast = 0): array
    {
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

    protected static function getQuery(string $attribute, int $daysPast = 0): Collection
    {

        $end = Carbon::today()->endOfDay();

        $start = match ($daysPast) {
            0 => Carbon::today()->subDays($daysPast)->startOfDay(),
            7, 14 => Carbon::now()->subDays($daysPast)->startOfDay(),
            31 => Carbon::today()->subMonth(),
            default => Carbon::today(),
        };

        return UserSiteAccessStatistics::whereNotNull($attribute)
            ->whereBetween('created_at', [
                $start,
                $end,
            ])
            ->select($attribute, 'created_at')
            ->get();
    }
}
