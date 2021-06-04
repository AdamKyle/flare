<?php

namespace App\Admin\Controllers\Api;

use App\Flare\Values\SiteAccessStatisticValue;
use App\Http\Controllers\Controller;

class SiteAccessStatisticsController extends Controller {

    public function index() {
        return response()->json([
            'registered' => SiteAccessStatisticValue::getRegistered(),
            'signed_in'  => SiteAccessStatisticValue::getSignedIn(),
        ], 200);
    }
}
