<?php

namespace App\Game\Core\Controllers\Api;

use App\Flare\Events\ServerMessageEvent;
use App\Flare\Events\UpdateTopBarEvent;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Game\Battle\Events\UpdateSkillEvent;
use App\Game\Core\Events\CraftedItemTimeOutEvent;
use App\Game\Core\Services\CraftingSkillService;
use Illuminate\Http\Request;

class CharacterSkillController extends Controller {


    public function __construct() {
        $this->middleware('auth:api');
        $this->middleware('is.character.dead');
    }

    public function fetchItemsToCraft(Request $request, Character $character) {
        $foundSkill = $character->skills->where('name', $request->crafting_type . ' Crafting')->first();
        $items      = item::where('can_craft', true)
                            ->where('crafting_type', strtolower($request->crafting_type))
                            ->where('skill_level_required', '<=', $foundSkill->level)
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

        $character->update([
            'gold' => $character->gold - $item->cost,
        ]);

        event(new UpdateTopBarEvent($character->refresh()));

        if ($currentSkill->level < $item->skill_level_required) {
            event(new ServerMessageEvent($character->user, 'to_hard_to_craft'));
        } else {
            $dcCheck       = $craftingSkill->fetchDCCheck($currentSkill);
            $characterRoll = $craftingSkill->fetchCharacterRoll($currentSkill);

            if ($characterRoll > $dcCheck) {
                $this->attemptToPickUpItem($character->refresh(), $item);
                event(new UpdateSkillEvent($currentSkill));
            } else {
                event(new ServerMessageEvent($character->user, 'failed_to_craft'));
            }
        }

        $items = Item::where('can_craft', true)
                    ->where('crafting_type', strtolower($request->type))
                    ->where('skill_level_required', '<=', $currentSkill->refresh()->level)
                    ->get();

        event(new CraftedItemTimeOutEvent($character->refresh()));
        
        return response()->json([
            'items' => $items,
        ], 200);
    }

    protected function attemptToPickUpItem(Character $character, Item $item) {
        if ($character->inventory->slots->count() !== $character->inventory_max) {

            $character->inventory->slots()->create([
                'item_id'      => $item->id,
                'inventory_id' => $character->inventory->id,
            ]);

            event(new ServerMessageEvent($character->user, 'crafted', $item->name));
        } else {
            event(new ServerMessageEvent($character->user, 'inventory_full'));
        }
    }
}
