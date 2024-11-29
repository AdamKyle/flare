<?php

namespace App\Game\Events\Handlers;

use App\Flare\Builders\RandomAffixGenerator;
use App\Flare\Models\Character;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Models\Item;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Events\Concerns\UpdateCharacterEventGoalParticipation;
use App\Game\Events\Services\EventGoalsService;
use App\Game\Messages\Events\ServerMessageEvent;
use Exception;

class BaseGlobalEventGoalParticipationHandler
{
    use UpdateCharacterEventGoalParticipation;

    private RandomAffixGenerator $randomAffixGenerator;

    protected EventGoalsService $eventGoalsService;

    public function __construct(RandomAffixGenerator $randomAffixGenerator, EventGoalsService $eventGoalsService)
    {
        $this->randomAffixGenerator = $randomAffixGenerator;
        $this->eventGoalsService = $eventGoalsService;
    }

    /**
     * Reward only those who have met the required amount of kills or higher.
     *
     * @throws Exception
     */
    public function rewardCharactersParticipating(GlobalEventGoal $globalEventGoal): void
    {
        Character::whereIn('id', $globalEventGoal->globalEventParticipation->pluck('character_id')->toArray())
            ->chunkById(100, function ($characters) use ($globalEventGoal) {
                foreach ($characters as $character) {

                    $amountOfKills = $globalEventGoal->globalEventParticipation
                        ->where('character_id', $character->id)
                        ->first()
                        ->current_kills;

                    $amountOfCrafts = $globalEventGoal->globalEventParticipation
                        ->where('character_id', $character->id)
                        ->first()
                        ->current_crafts;

                    $amountOfEnchants = $globalEventGoal->globalEventParticipation
                        ->where('character_id', $character->id)
                        ->first()
                        ->current_enchants;

                    if (($amountOfKills ?? 0) >= $this->eventGoalsService->fetchAmountNeeded($globalEventGoal)) {
                        $this->rewardForCharacter($character, $globalEventGoal);
                    }

                    if (($amountOfCrafts ?? 0) >= $this->eventGoalsService->fetchAmountNeeded($globalEventGoal)) {
                        $this->rewardForCharacter($character, $globalEventGoal);
                    }

                    if (($amountOfEnchants ?? 0) >= $this->eventGoalsService->fetchAmountNeeded($globalEventGoal)) {
                        $this->rewardForCharacter($character, $globalEventGoal);
                    }
                }
            });
    }

    /**
     * Generate reward for the character.
     *
     * @return void
     *
     * @throws Exception
     */
    private function rewardForCharacter(Character $character, GlobalEventGoal $globalEventGoal)
    {

        $item = Item::where('specialty_type', $globalEventGoal->item_specialty_type_reward)
            ->whereNull('item_prefix_id')
            ->whereNull('item_suffix_id')
            ->whereDoesntHave('appliedHolyStacks')
            ->whereNotIn('type', [
                'artifact',
                'trinket',
                'quest',
            ])
            ->inRandomOrder()
            ->first();

        if (is_null($item)) {
            return;
        }

        if ($character->isInventoryFull()) {
            event(new ServerMessageEvent($character->user, 'Child, your inventory is too full for you to be rewarded with any items for this event goal! You need to make some room.'));

            return;
        }

        if ($globalEventGoal->should_be_unique) {

            $randomAffixGenerator = $this->randomAffixGenerator->setCharacter($character)->setPaidAmount(RandomAffixDetails::LEGENDARY);

            $newItem = $item->duplicate();

            $newItem->update([
                'item_prefix_id' => $randomAffixGenerator->generateAffix('prefix')->id,
                'item_suffix_id' => $randomAffixGenerator->generateAffix('suffix')->id,
                'holy_stacks' => 20,
            ]);

            $newItem = $newItem->refresh();

            $slot = $character->inventory->slots()->create([
                'inventory_id' => $character->inventory->id,
                'item_id' => $newItem->id,
            ]);

            event(new ServerMessageEvent($character->user, 'You were rewarded with an item of Unique power for participating in the current events global goal.', $slot->id));

            return;
        }

        if ($globalEventGoal->should_be_mythic) {
            $randomAffixGenerator = $this->randomAffixGenerator->setCharacter($character)->setPaidAmount(RandomAffixDetails::MYTHIC);

            $newItem = $item->duplicate();

            $newItem->update([
                'item_prefix_id' => $randomAffixGenerator->generateAffix('prefix')->id,
                'item_suffix_id' => $randomAffixGenerator->generateAffix('suffix')->id,
                'is_mythic' => true,
            ]);

            $newItem = $newItem->refresh();

            $slot = $character->inventory->slots()->create([
                'inventory_id' => $character->inventory->id,
                'item_id' => $newItem->id,
            ]);

            event(new ServerMessageEvent($character->user, 'You were rewarded with an item of Mythical power for participating in the current events global goal.', $slot->id));
        }
    }
}
