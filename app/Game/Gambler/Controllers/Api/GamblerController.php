<?php

namespace App\Game\Gambler\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Gambler\Controllers\Charcater;
use App\Game\Gambler\Services\GamblerService;
use App\Game\Gambler\Values\CurrencyValue;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class GamblerController extends Controller {

    /**
     * @var GamblerService $gamblerService
     */
    private GamblerService $gamblerService;

    /**
     * @param GamblerService $gamblerService
     */
    public function __construct(GamblerService $gamblerService) {
        $this->gamblerService = $gamblerService;
    }

    /**
     * @return JsonResponse
     */
    public function getSlots(): JsonResponse {
        return response()->json([
            'icons' => CurrencyValue::getIcons()
        ]);
    }

    /**
     * @param Character $character
     * @return JsonResponse
     */
    public function rollSlots(Character $character): JsonResponse {
        $response = $this->gamblerService->roll($character);

        dump($response);

        return response()->json($response);
    }
}
