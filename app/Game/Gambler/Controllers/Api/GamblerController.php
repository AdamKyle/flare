<?php

namespace App\Game\Gambler\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Gambler\Services\GamblerService;
use App\Game\Gambler\Values\CurrencyValue;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class GamblerController extends Controller
{
    private GamblerService $gamblerService;

    public function __construct(GamblerService $gamblerService)
    {
        $this->gamblerService = $gamblerService;
    }

    public function getSlots(): JsonResponse
    {
        return response()->json([
            'icons' => CurrencyValue::getIcons(),
        ]);
    }

    public function rollSlots(Character $character): JsonResponse
    {
        $response = $this->gamblerService->roll($character);

        $status = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
    }
}
