<?php

namespace App\Game\Skills\Controllers\Api;

use App\Game\Core\Events\CraftedItemTimeOutEvent;
use App\Game\Skills\Jobs\ProcessAlchemy;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Game\Skills\Requests\AlchemyValidation;
use App\Game\Skills\Services\AlchemyService;

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
            return response()->json(['message' => 'invalid input.'], 429);
        }

        event(new CraftedItemTimeOutEvent($character));

        ProcessAlchemy::dispatch($character, $request->item_to_craft);

        return response()->json([], 200);
    }
}
