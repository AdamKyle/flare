<?php

namespace App\Game\Skills\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Game\Core\Events\CraftedItemTimeOutEvent;
use App\Game\Skills\Services\AlchemyService;
use App\Game\Skills\Requests\AlchemyValidation;

class AlchemyController extends Controller {

    private $alchemyService;

    public function __construct(AlchemyService $alchemyService) {
        $this->alchemyService = $alchemyService;
    }

    public function alchemyItems(Character $character) {
        return response()->json([
            'items' => $this->alchemyService->fetchAlchemistItems($character),
        ]);
    }

    public function transmute(AlchemyValidation $request, Character $character) {
        if (!$character->can_craft) {
            return response()->json(['message' => 'You must wait to craft again.'], 422);
        }

        event(new CraftedItemTimeOutEvent($character));

        $this->alchemyService->transmute($character, $request->item_to_craft);

        return response()->json([
            'items' => $this->alchemyService->fetchAlchemistItems($character),
        ]);
    }
}
