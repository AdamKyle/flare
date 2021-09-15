<?php

namespace App\Game\Skills\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Game\Skills\Requests\CraftingValidation;
use App\Game\Skills\Services\CraftingService;

class CraftingController extends Controller {

    /**
     * @var CraftingService $craftingService
     */
    private $craftingService;

    /**
     * Constructor
     * 
     * @param CraftingService $craftingService
     * @return void
     */
    public function __construct(CraftingService $craftingService) {
        $this->craftingService = $craftingService;
    }

    public function fetchItemsToCraft(Request $request, Character $character) {
        return response()->json([
            'items' => $this->craftingService->fetchCraftableItems($character, $request->all())
        ], 200);
    }

    public function craft(CraftingValidation $request, Character $character) {
        if (!$character->can_craft) {
            return response()->json(['message' => 'invalid input.'], 429);
        }

        $response = $this->craftingService->craft($character, $request->all());

        $status = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
    }
}