<?php

namespace App\Game\Core\Controllers\Api;

use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Events\ServerMessageEvent;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
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
        $items      = Item::where('can_craft', true)
                            ->where('crafting_type', strtolower($request->crafting_type))
                            ->where('skill_level_required', '<=', $foundSkill->level)
                            ->where('item_prefix_id', null)
                            ->where('item_suffix_id', null)
                            ->get();

        return response()->json([
            'items' => $items,
        ], 200);
    }

    public function fetchAffixes(Character $character, CharacterInformationBuilder $builder) {

        $builder        = $builder->setCharacter($character);
        $enchatingSkill = $character->skills->where('game_skill_id', GameSkill::where('name', 'Enchanting')->first()->id)->first();

        $inventory      = $character->inventory->slots->filter(function($slot) {
            if ($slot->item->type !== 'quest' && !$slot->equipped) {
                return $slot->item->load('itemSuffix', 'itemPrefix')->toArray();
            }

        })->all();

        return response()->json([
            'affixes' => ItemAffix::where('int_required', '<=', $builder->statMod('int'))
                                  ->where('skill_level_required', '<=', $enchatingSkill->level)
                                  ->get(),
            'character_inventory' => array_values($inventory),
        ]);
    }

    public function trainEnchanting(Request $request, Character $character, CharacterInformationBuilder $builder, CraftingSkillService $craftingService) {
        $request->validate([
            'item_id'   => 'required',
            'affix_ids' => 'required',
            'cost'      => 'required',
            'extraTime' => 'nullable|in:double,tripple'
        ]);

        $builder        = $builder->setCharacter($character);
        $enchatingSkill = $character->skills->where('game_skill_id', GameSkill::where('name', 'Enchanting')->first()->id)->first();

        $affixes  = ItemAffix::findMany($request->affix_ids);
        $itemSlot = $character->inventory->slots->where('item_id', $request->item_id)->where('equipped', false)->first();
        
        if ($affixes->isEmpty() || is_null($itemSlot)) {
            return response()->json([
                'message' => 'Invalid input.'
            ], 422);
        }

        $item = $itemSlot->item;

        if ($request->cost > $character->gold) {
            event(new ServerMessageEvent($character->user, 'not_enough_gold'));

            return response()->json([], 200);
        }

        $craftingService->updateCharacterGoldForEnchanting($character, $request->cost);

        $craftingService->sendOffEnchantingServerMessage($enchatingSkill, $item, $affixes, $character);

        event(new CraftedItemTimeOutEvent($character->refresh()), $request->extraTime);

        return response()->json([
            'affixes' => ItemAffix::where('int_required', '<=', $builder->statMod('int'))
                                  ->where('skill_level_required', '<=', $enchatingSkill->level)
        ]);
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
