<?php

namespace App\Game\Core\Controllers\Api;

use App\Game\Core\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Game\Core\Services\CharacterInventoryService;
use Illuminate\Http\Request;

class CharacterInventoryController extends Controller {

    private $characterInventoryService;

    public function __construct(CharacterInventoryService $characterInventoryService) {

        $this->characterInventoryService = $characterInventoryService;
    }

    public function inventory(Character $character) {
        $inventory = $this->characterInventoryService->setCharacter($character);

        return response()->json($inventory->getInventoryForApi(), 200);
    }

    public function destroy(Request $request, Character $character) {

        $slot = $character->inventory->slots->filter(function($slot) use ($request) {
            return $slot->id === (int) $request->slot_id;
        })->first();

        if (is_null($slot)) {
            return response()->json(['message' => 'You don\'t own that item.'], 422);
        }

        if ($slot->equipped) {
            return response()->json(['message' => 'Cannot destroy equipped item.'], 422);
        }

        $name = $slot ->item->affix_name;

        $slot->delete();

        event(new CharacterInventoryUpdateBroadCastEvent($character->user));

        return response()->json(['message' => 'Destroyed ' . $name . '.'], 200);
    }
}
