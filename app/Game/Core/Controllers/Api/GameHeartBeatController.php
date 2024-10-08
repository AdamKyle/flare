<?php

namespace App\Game\Core\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class GameHeartBeatController extends Controller {

    /**
     * @return JsonResponse
     */
    public function heartBeat(): JsonResponse {
        return response()->json(['status' => 'ok']);
    }
}
