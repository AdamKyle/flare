<?php

namespace App\Game\Core\Controllers\Api;

use App\Flare\Events\ServerMessageEvent;
use App\Flare\Events\UpdateTopBarEvent;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Events\UpdateSkillEvent;
use App\Game\Core\Events\CraftedItemTimeOutEvent;
use App\Game\Core\Services\CraftingSkillService;
use Illuminate\Http\Request;

class CharacterSkillController extends Controller {


    public function __construct() {
        $this->middleware('auth:api');
        $this->middleware('is.character.dead');
        $this->middleware('is.character.adventuring');
    }

    public function fetchItemsToCraft(Request $request, Character $character) {
        $foundSkill = $character->skills->where('name', $request->crafting_type . ' Crafting')->first();
        $items      = item::where('can_craft', true)
                            ->where('crafting_type', strtolower($request->crafting_type))
                            ->where('skill_level_required', '<=', $foundSkill->level)
                            ->where('item_prefix_id', null)
                            ->where('item_suffix_id', null)
                            ->get();

        return response()->json([
            'items' => $items,
        ], 200);
    }

    public function trainCrafting(CraftingSkillService $craftingSkill, Request $request, Character $character) {
        $item = Item::find($request->item_to_craft);

        if (is_null($item)) {
            return response()->json([
                'message' => 'Invalid input.',
            ], 422);
        }

        if ($item->cost > $character->gold) {
            event(new ServerMessageEvent($character->user, 'not_enough_gold'));

            return response()->json([], 200);
        }

        $craftingSkill = $craftingSkill->setCharacter($character);
        $currentSkill  = $craftingSkill->getCurrentSkill($request->type);

        if (is_null($currentSkill)) {
            return response()->json([
                'message' => 'Invalid input.',
            ], 422);
        }

        $craftingSkill->updateCharacterGold($character, $item);

        $craftingSkill->sendOffServerMessage($currentSkill, $item, $character);

        $items = Item::where('can_craft', true)
                    ->where('crafting_type', strtolower($request->type))
                    ->where('item_suffix_id', null)
                    ->where('item_prefix_id', null)
                    ->where('skill_level_required', '<=', $currentSkill->refresh()->level)
                    ->get();

        event(new CraftedItemTimeOutEvent($character->refresh()));
        
        return response()->json([
            'items' => $items,
        ], 200);
    }
}
