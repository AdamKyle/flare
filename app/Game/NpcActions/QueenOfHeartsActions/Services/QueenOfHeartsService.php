<?php

namespace App\Game\NpcActions\QueenOfHeartsActions\Services;

use App\Flare\Models\Character;
use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\NpcActions\QueenOfHeartsActions\Events\UpdateQueenOfHeartsPanel;

class QueenOfHeartsService {

    use ResponseBuilder;

    /**
     * @var RandomEnchantmentService $randomEnchantmentService
     */
    private RandomEnchantmentService $randomEnchantmentService;

    /**
     * @var ReRollEnchantmentService $reRollEnchantmentService
     */
    private ReRollEnchantmentService $reRollEnchantmentService;

    /**
     * @param RandomEnchantmentService $randomEnchantmentService
     * @param ReRollEnchantmentService $reRollEnchantmentService
     */
    public function __construct(RandomEnchantmentService $randomEnchantmentService, ReRollEnchantmentService $reRollEnchantmentService) {
        $this->randomEnchantmentService = $randomEnchantmentService;
        $this->reRollEnchantmentService = $reRollEnchantmentService;
    }

    /**
     * Purchase Unique.
     *
     * @param Character $character
     * @param string $type
     * @return array
     */
    public function purchaseUnique(Character $character, string $type): array {
        if (!$this->randomEnchantmentService->isPlayerInHell($character)) {
            event(new GlobalMessageEvent($character->name . ' has pissed off the Queen of Hearts with their cheating ways. They attempted to access her while not in Hell and/or with out the required item.'));

            return $this->errorResult('Invalid location to use that.');
        }

        if ($character->isInventoryFull()) {
            return $this->errorResult('Your inventory is full.');
        }

        if ($character->gold < $this->randomEnchantmentService->getCost($type)) {
            return $this->errorResult('Not enough gold.');
        }

        $character->update([
            'gold' => $character->gold - $this->randomEnchantmentService->getCost($type)
        ]);

        $item = $this->randomEnchantmentService->generateForType($character, $type);

        $slot = $character->inventory->slots()->create([
            'inventory_id' => $character->inventory->id,
            'item_id'      => $item->id,
        ]);

        $character = $character->refresh();

        event(new UpdateCharacterCurrenciesEvent($character));

        event(new UpdateQueenOfHeartsPanel($character->user, $this->randomEnchantmentService->fetchDataForApi($character)));

        event(new ServerMessageEvent($character->user, 'The Queen of Hearts blushes, smiles and bats her eye lashes at you as she hands you, from out of nowhere, a new shiny object: ' . $item->affix_name, $slot->id));

        return $this->successResult();
    }

    /**
     * Re roll Unique.
     *
     * @param Character $character
     * @param int $selectedSlotId
     * @param string $selectedReRollType
     * @param string $selectedAffix
     * @return array
     */
    public function reRollUnique(Character $character, int $selectedSlotId, string $selectedReRollType, string $selectedAffix): array {
        $slot = $character->inventory->slots->filter(function ($slot) use ($selectedSlotId) {
            return $slot->id === $selectedSlotId;
        })->first();

        if (is_null($slot)) {
            return $this->errorResult('Where did you put that item, child? Ooooh hooo hooo hooo! Are you playing hide and seek with it? (Unique does not exist.)');
        }

        if (!$this->randomEnchantmentService->isPlayerInHell($character)) {
            event(new GlobalMessageEvent($character->name . ' has pissed off the Queen of Hearts with their cheating ways. They attempted to access her while not in Hell and/or with out the required item.'));

            return $this->errorResult('Invalid location to use that.');
        }

        if (!$this->reRollEnchantmentService->canAfford($character, $selectedReRollType, $selectedAffix)) {
            return $this->errorResult('What! No! Child! I don\'t like poor people. I don\'t even date poor men! Oh this is so saddening, child! (You don\'t have enough currency, you made the Queen sad.)');
        }

        $this->reRollEnchantmentService->reRoll(
            $character,
            $slot,
            $selectedAffix,
            $selectedReRollType
        );

        $character = $character->refresh();

        event(new ServerMessageEvent($character->user, 'The Queen has re-rolled: ' . $slot->item->affix_name, $slot->id));

        return $this->successResult($this->randomEnchantmentService->fetchDataForApi($character));
    }

    /**
     * Move the affixes.
     *
     * @param Character $character
     * @param int $selectedSlotId
     * @param int $selectedSecondarySlotId
     * @param string $selectedAffix
     * @return array
     */
    public function moveAffixes(Character $character, int $selectedSlotId, int $selectedSecondarySlotId, string $selectedAffix): array {
        if (!$this->randomEnchantmentService->isPlayerInHell($character)) {
            event(new GlobalMessageEvent($character->name . ' has pissed off the Queen of Hearts with their cheating ways. They attempted to access her while not in Hell and/or with out the required item.'));

            return $this->errorResult('Invalid location to use that.');
        }

        $slot = $character->inventory->slots->filter(function ($slot) use ($selectedSlotId) {
            return $slot->id === $selectedSlotId;
        })->first();

        $secondSlot = $character->inventory->slots->filter(function ($slot) use ($selectedSecondarySlotId) {
            return $slot->id === $selectedSecondarySlotId;
        })->first();


        if (is_null($slot) || is_null($secondSlot)) {
            return $this->errorResult('Where did you put that item, child? Ooooh hooo hooo hooo! Are you playing hide and seek with it? (Unique does not exist.)');
        }

        if ($slot->item->type === 'trinket' || $slot->item->type === 'artifact') {
            return $this->errorResult('I don\'t know how to handle trinkets or artifacts child. Bring me something sexy! Oooooh hooo hooo!');
        }

        if ($secondSlot->item->type === 'trinket' || $secondSlot->item->type === 'artifact') {
            return $this->errorResult('I don\'t know how to handle trinkets or artifacts child. Bring me something sexy! Oooooh hooo hooo!');
        }

        if (!$this->reRollEnchantmentService->canAffordMovementCost($character, $slot->item->id, $selectedAffix)) {
            return $this->errorResult('Child, you are so poor (Not enough currency) ...');
        }

        $this->reRollEnchantmentService->moveAffixes(
            $character,
            $slot,
            $secondSlot,
            $selectedAffix,
        );

        return $this->successResult($this->randomEnchantmentService->fetchDataForApi($character));
    }
}
