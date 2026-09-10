<?php

namespace App\Game\Maps\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Maps\Services\GemWorldService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class GemWorldController extends Controller
{
    public function __construct(
        private readonly GemWorldService $gemWorldService,
    ) {
        $this->middleware('is.character.dead')->except(['context']);
    }

    /**
     * Return the current Gem World Map card state for the Character.
     */
    public function context(Character $character): JsonResponse
    {
        return response()->json($this->gemWorldService->context($character), 200);
    }

    /**
     * Enter the Character's single contextually valid generated Gem World.
     */
    public function enter(Character $character): JsonResponse
    {
        if (! $character->can_move) {
            return response()->json(['invalid input'], 422);
        }

        $response = $this->gemWorldService->enter($character);

        $status = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
    }

    /**
     * Exit the Character's current generated Gem World.
     */
    public function exit(Character $character): JsonResponse
    {
        if (! $character->can_move) {
            return response()->json(['invalid input'], 422);
        }

        $response = $this->gemWorldService->exit($character);

        $status = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
    }
}
