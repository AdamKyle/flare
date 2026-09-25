<?php

namespace App\Game\Gambler\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Gambler\Services\GamblerService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GamblerController extends Controller
{
    /**
     * @param GamblerService $gamblerService
     */
    public function __construct(private readonly GamblerService $gamblerService) {}

    /**
     * Return the slot machine symbols and the authenticated character's spin availability.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getSlots(Request $request): JsonResponse
    {
        return response()->json($this->gamblerService->getSlotStatus($request->user()->character));
    }

    /**
     * Spin the slot machine for the character.
     *
     * @param Character $character
     * @return JsonResponse
     */
    public function rollSlots(Character $character): JsonResponse
    {
        $response = $this->gamblerService->roll($character);

        $status = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
    }
}
