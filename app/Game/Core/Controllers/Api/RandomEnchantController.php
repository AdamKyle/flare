<?php

namespace App\Game\Core\Controllers\Api;

use App\Flare\Builders\RandomAffixGenerator;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Core\Events\CharacterInventoryDetailsUpdate;
use App\Game\Core\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Game\Core\Events\UpdateQueenOfHeartsPanel;
use App\Game\Core\Requests\MoveRandomEnchantment;
use App\Game\Core\Requests\PurchaseRandomEnchantment;
use App\Game\Core\Requests\ReRollRandomEnchantment;
use App\Game\Core\Services\RandomEnchantmentService;
use App\Game\Core\Services\ReRollEnchantmentService;
use App\Game\Messages\Events\GlobalMessageEvent;
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

    public function uniquesOnly(Character $character) {
        return response()->json($this->randomEnchantmentService->fetchDataForApi($character));
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

        $character->update([
            'gold' => $character->gold - $this->randomEnchantmentService->getCost($request->type)
        ]);

        $item = $this->randomEnchantmentService->generateForType($character, $request->type);

        $slot = $character->inventory->slots()->create([
            'inventory_id' => $character->inventory->id,
            'item_id'      => $item->id,
        ]);

        $character = $character->refresh();

        event(new UpdateTopBarEvent($character));

        event(new UpdateQueenOfHeartsPanel($character->user, $this->randomEnchantmentService->fetchDataForApi($character)));

        broadcast(new ServerMessageEvent($character->user, 'The Queen of Hearts blushes, smiles and bats her eye lashes at you as she hands you, from out of nowhere, a new shiny object: ' . $item->affix_name, $slot->id));

        return response()->json();
    }

    public function reRoll(ReRollRandomEnchantment $request, Character $character) {
        $slot = $character->inventory->slots->filter(function($slot) use ($request) {
            return $slot->id === $request->selected_slot_id;
        })->first();

        if (is_null($slot)) {
            return response()->json(['message' => 'Where did you put that item, child? Ooooh hooo hooo hooo! Are you playing hide and seek with it? (Unique does not exist.)'], 422);
        }

        if (!$this->randomEnchantmentService->isPlayerInHell($character)) {
            event (new GlobalMessageEvent($character->name . ' has pissed off the Queen of Hearts with their cheating ways. They attempted to access her while not in Hell and/or with out the required item.'));

            return response()->json(['message' => 'Invalid location to use that.'], 422);
        }

        if (!$this->reRollEnchantmentService->canAfford($character, $request->selected_reroll_type, $request->selected_affix)) {
            return response()->json(['message' => 'What! No! Child! I don\'t like poor people. I don\'t even date poor men! Oh this is so saddening, child! (You don\'t have enough currency, you made the Queen sad.)'], 422);
        }

        $this->reRollEnchantmentService->reRoll(
            $character,
            $slot,
            $request->selected_affix,
            $request->selected_reroll_type
        );

        $character = $character->refresh();

        event(new ServerMessageEvent($character->user, 'The Queen has re-rolled: ' . $slot->item->affix_name, $slot->id));

        return response()->json($this->randomEnchantmentService->fetchDataForApi($character));
    }

    public function moveAffixes(MoveRandomEnchantment $request, Character $character) {
        if (!$this->randomEnchantmentService->isPlayerInHell($character)) {
            event (new GlobalMessageEvent($character->name . ' has pissed off the Queen of Hearts with their cheating ways. They attempted to access her while not in Hell and/or with out the required item.'));

            return response()->json(['message' => 'Invalid location to use that.'], 422);
        }

        $slot = $character->inventory->slots->filter(function($slot) use ($request) {
            return $slot->id === $request->selected_slot_id;
        })->first();

        $secondSlot = $character->inventory->slots->filter(function($slot) use ($request) {
            return $slot->id === $request->selected_secondary_slot_id;
        })->first();


        if (is_null($slot) || is_null($secondSlot)) {
            return response()->json(['message' => 'Where did you put those items, child? Ooooh hooo hooo hooo! Are you playing hide and seek with it? (Either the unique or the requested item does not exist.)'], 422);
        }

        if (!$this->reRollEnchantmentService->canAffordMovementCost($character, $slot->item->id, $request->selected_affix)) {
            return response()->json(['message' => 'Child, you are so poor (Not enough currency) ...'], 422);
        }

        if ($character->gold_dust < $request->gold_cost || $character->shards < $request->shard_cost) {
            return response()->json(['message' => 'What! No! Child! I don\'t like poor people. I don\'t even date poor men! Oh this is so saddening, child! (You don\'t have enough currency, you made the Queen sad.)'], 422);
        }

        $this->reRollEnchantmentService->moveAffixes(
            $character,
            $slot,
            $secondSlot,
            $request->selected_affix,
        );

        return response()->json($this->randomEnchantmentService->fetchDataForApi($character));
    }
}
