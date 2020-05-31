<?php

namespace App\Game\Core\Controllers\Api;

use App\Flare\Events\ServerMessageEvent;
use App\Flare\Events\UpdateTopBarEvent;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Game\Battle\Events\UpdateSkillEvent;
use App\Game\Core\Events\CraftedItemTimeOutEvent;
use Illuminate\Http\Request;

class CharacterSkillController extends Controller {


    public function __construct() {
        $this->middleware('auth:api');
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

    public function trainCrafting(Request $request, Character $character) {
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

        $currentSkill = $character->skills->filter(function($skill) use($request) {
            return $skill->name === $request->type . ' Crafting';
        })->first();

        if (is_null($currentSkill)) {
            return response()->json([
                'message' => 'Invalid input.',
            ], 422);
        }

        $dcCheck = rand(0, $currentSkill->max_level);
        $dcCheck = $dcCheck !== 0 ? $dcCheck - $currentSkill->level : $dcCheck / 2;

        $characterRoll = rand(0, $currentSkill->max_level) * (1 + ($currentSkill->skill_bonus));
        
        $character->update([
            'gold' => $character->gold - $item->cost,
        ]);

        event(new UpdateTopBarEvent($character->refresh()));

        if ($characterRoll > $dcCheck) {
            $this->attemptToPickUpItem($character->refresh(), $item);
        } else {
            event(new ServerMessageEvent($character->user, 'failed_to_craft'));
        }

        event(new UpdateSkillEvent($currentSkill));

        $items      = item::where('can_craft', true)
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
