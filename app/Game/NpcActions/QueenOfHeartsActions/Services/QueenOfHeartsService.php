<?php

namespace App\Game\NpcActions\QueenOfHeartsActions\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Values\ItemEffectsValue;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;

class QueenOfHeartsService
{
    use ResponseBuilder;

    private RandomEnchantmentService $randomEnchantmentService;

    private ReRollEnchantmentService $reRollEnchantmentService;

    public function __construct(RandomEnchantmentService $randomEnchantmentService, ReRollEnchantmentService $reRollEnchantmentService)
    {
        $this->randomEnchantmentService = $randomEnchantmentService;
        $this->reRollEnchantmentService = $reRollEnchantmentService;
    }

    /**
     * Re roll Unique.
     */
    public function reRollUnique(Character $character, int $selectedSlotId, string $selectedReRollType, string $selectedAffix): array
    {

        if (! $character->map->gameMap->mapType()->isHell()) {
            event(new GlobalMessageEvent('The Queen of Hell is not happy that '.$character->name.' tried to talk to her while not in hell. "Hmmmp child, I do not like you right now!" As she pouts'));

            $item = Item::where('type', 'quest')->where('effect', ItemEffectsValue::QUEEN_OF_HEARTS)->first();

            return $this->errorResult('You need to be in Hell to access The Queen of Hearts and have the quest item: '.$item->affix_name.'.');
        }

        $slot = $character->inventory->slots->filter(function ($slot) use ($selectedSlotId) {
            return $slot->id === $selectedSlotId;
        })->first();

        if (is_null($slot)) {
            return $this->errorResult('Where did you put that item, child? Ooooh hooo hooo hooo! Are you playing hide and seek with it? (Unique does not exist.)');
        }

        if (! $this->reRollEnchantmentService->canAfford($character, $selectedReRollType, $selectedAffix)) {
            return $this->errorResult('What! No! Child! I don\'t like poor people. I don\'t even date poor men! Oh this is so saddening, child! (You don\'t have enough currency, you made the Queen sad.)');
        }

        $this->reRollEnchantmentService->reRoll(
            $character,
            $slot,
            $selectedAffix,
            $selectedReRollType
        );

        $character = $character->refresh();

        event(new ServerMessageEvent($character->user, 'The Queen has re-rolled: '.$slot->item->affix_name, $slot->id));

        return $this->successResult($this->randomEnchantmentService->fetchDataForApi($character));
    }

    /**
     * Move the affixes.
     */
    public function moveAffixes(Character $character, int $selectedSlotId, int $selectedSecondarySlotId, string $selectedAffix): array
    {

        if (! $this->randomEnchantmentService->isPlayerInHell($character)) {
            event(new GlobalMessageEvent('The Queen of Hell is not happy that '.$character->name.' tried to talk to her while not in hell. "Hmmmp child, I do not like you right now!" As she pouts'));

            $item = Item::where('type', 'quest')->where('effect', ItemEffectsValue::QUEEN_OF_HEARTS)->first();

            return $this->errorResult('You need to be in Hell to access The Queen of Hearts and have the quest item: '.$item->affix_name.'.');
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

        if (! $this->reRollEnchantmentService->canAffordMovementCost($character, $slot->item->id, $selectedAffix)) {
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
