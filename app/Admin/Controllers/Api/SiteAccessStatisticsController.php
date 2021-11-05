<?php

namespace App\Admin\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Values\SiteAccessStatisticValue;
use App\Http\Controllers\Controller;

class SiteAccessStatisticsController extends Controller {

    public function index() {
        return response()->json([
            'registered' => SiteAccessStatisticValue::getRegistered(),
            'signed_in'  => SiteAccessStatisticValue::getSignedIn(),
        ], 200);
    }

    public function fetchLoggedInAllTime() {
        return response()->json(['stats' => SiteAccessStatisticValue::getAllTimeSignedIn()], 200);
    }

    public function fetchRegisteredAllTime() {
        return response()->json(['stats' => SiteAccessStatisticValue::getAllTimeRegistered()], 200);
    }

    public function fetchCharactersGold() {
        $charactersWithHighGold = Character::where('gold', '>=', 1000000000)->get();

        return response()->json([
            'stats' => [
                'labels' => $charactersWithHighGold->pluck('name')->toArray(),
                'data'   => $charactersWithHighGold->pluck('gold')->toArray(),
            ]
        ]);
    }
}
