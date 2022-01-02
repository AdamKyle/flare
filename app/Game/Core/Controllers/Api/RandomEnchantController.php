<?php

namespace App\Game\Core\Controllers\Api;

use App\Flare\Builders\RandomAffixGenerator;
use App\Flare\Events\UpdateTopBarEvent;
use App\Game\Core\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Game\Core\Requests\PurchaseRandomEnchantment;
use App\Game\Core\Requests\ReRollRandomEnchantment;
use App\Game\Core\Services\RandomEnchantmentService;
use App\Game\Core\Services\ReRollEnchantmentService;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;

class RandomEnchantController extends Controller {

    private $randomAffixGenerator;

    private $randomEnchantmentService;

    private $reRollEnchantmentService;

    public function __construct(RandomAffixGenerator $randomAffixGenerator,
                                RandomEnchantmentService $randomEnchantmentService,
                                ReRollEnchantmentService $reRollEnchantmentService
    ) {
        $this->randomAffixGenerator     = $randomAffixGenerator;
        $this->randomEnchantmentService = $randomEnchantmentService;
        $this->reRollEnchantmentService = $reRollEnchantmentService;
    }

    public function purchase(PurchaseRandomEnchantment $request, Character $character) {

        if ($character->isInventoryFull()) {
            return response()->json([
                'message' => 'Nope, your inventory is full.'
            ], 422);
        }

        if ($character->gold < $this->randomEnchantmentService->getCost($request->type)) {
            return response()->json([
                'message' => 'Nope, not enough gold.'
            ], 422);
        }

        $item = $this->randomEnchantmentService->generateForType($character, $request->type);

        $character->inventory->slots()->create([
            'inventory_id' => $character->inventory->id,
            'item_id'      => $item->id,
        ]);

        $character = $character->refresh();

        event(new CharacterInventoryUpdateBroadCastEvent($character->user));

        event(new UpdateTopBarEvent($character));

        broadcast(new ServerMessageEvent($character->user, 'The Queen of Hearts blushes, smiles and bats her eye lashes at you as she hands you, from out of no where, a new shiny object: ' . $item->affix_name, true));

        return response()->json([
            'item' => $item
        ], 200);
    }

    public function reRoll(ReRollRandomEnchantment $request, Character $character) {
        $slot = $character->inventory->slots->filter(function($slot) use ($request) {
            return $slot->id === $request->selected_slot_id;
        })->first();

        if (is_null($slot)) {
            return response()->json(['message' => 'Where did you put that item child? Ooooh hooo hooo hooo! Are you playing hide and seek with it? (Item does not exist)'], 422);
        }

        if ($character->gold_dust < $request->gold_dust_cost || $character->shards < $request->shard_cost) {
            return response()->json(['message' => 'What! No! Child! I don\'t like poor people. I don\'t  even date poor men! Oh this is so saddening child! (You dont have enough currency, you made the Queen sad)'], 422);
        }

        $this->reRollEnchantmentService->reRoll(
            $character,
            $slot->item,
            $request->selected_affix,
            $request->selected_reroll_type,
            $request->gold_dust_cost,
            $request->shard_cost
        );

        $character = $character->refresh();

        return response()->json([
            'gold_dust' => $character->gold_dust,
            'shards'    => $character->shards,
            'message'   => 'The queen has re rolled: ' . $slot->item->affix_name . ' Check your inventory to see the new stats.',
        ], 200);
    }


}
