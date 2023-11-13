<?php

namespace App\Game\BattleRewardProcessing\Handlers;

use App\Flare\Builders\RandomAffixGenerator;
use App\Flare\Models\Character;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Models\Item;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Events\Events\UpdateEventGoalProgress;
use App\Game\Events\Services\EventGoalsService;
use App\Game\Messages\Events\ServerMessageEvent;

class GlobalEventParticipationHandler {


    /**
     * @var RandomAffixGenerator $randomAffixGenerator
     */
    private RandomAffixGenerator $randomAffixGenerator;

    /**
     * @var EventGoalsService $eventGoalService
     */
    private EventGoalsService $eventGoalService;

    /**
     * @param RandomAffixGenerator $randomAffixGenerator
     * @param EventGoalsService $eventGoalService
     */
    public function __construct(RandomAffixGenerator $randomAffixGenerator, EventGoalsService $eventGoalService) {
        $this->randomAffixGenerator = $randomAffixGenerator;

        $this->eventGoalService = $eventGoalService;
    }

    /**
     * Handle updating the global; event participation
     *
     * @param Character $character
     * @param GlobalEventGoal $globalEventGoal
     * @return void
     */
    public function handleGlobalEventParticipation(Character $character, GlobalEventGoal $globalEventGoal) {
        if ($globalEventGoal->total_kills >= $globalEventGoal->max_kills) {
            return;
        }

        $globalEventParticipation = $character->globalEventParticipation;

        if (is_null($globalEventParticipation)) {
            $character->globalEventParticipation()->create([
                'global_event_goal_id' => $globalEventGoal->id,
                'character_id'         => $character->id,
                'current_kills'        => 1,
            ]);

            event(new UpdateEventGoalProgress($this->eventGoalService->getEventGoalData()));

            return;
        }

        $character->globalEventParticipation()->update([
            'current_kills' => $character->globalEventParticipation->current_kills + 1,
        ]);

        $globalEventGoal = $globalEventGoal->refresh();

        if ($globalEventGoal->total_kills >= $globalEventGoal->next_reward_at) {
            $newAmount = $globalEventGoal->next_reward_at + $globalEventGoal->reward_every_kills;

            $globalEventGoal->update([
                'next_reward_at' => $newAmount >= $globalEventGoal->max_kills ? $globalEventGoal->max_kills : $newAmount,
            ]);

            $this->rewardCharactersParticipating($globalEventGoal->refresh());
        }

        event(new UpdateEventGoalProgress($this->eventGoalService->getEventGoalData()));
    }

    /**
     * Reward only those who have met the required amount of kills or higher.
     *
     * @param GlobalEventGoal $globalEventGoal
     * @return void
     */
    protected function rewardCharactersParticipating(GlobalEventGoal $globalEventGoal) {
        Character::whereIn('id', $globalEventGoal->pluck('globalEventParticipation.character_id')->toArray())
            ->chunkById(100, function ($characters) use ($globalEventGoal) {
                foreach ($characters as $character) {

                    $amountOfKills = $globalEventGoal->globalEventParticipation
                        ->where('character_id', $character->id)
                        ->first()
                        ->current_kills;

                    if ($amountOfKills >= $this->eventGoalService->fetchKillAmountNeeded($globalEventGoal)) {
                        $this->rewardForCharacter($character, $globalEventGoal);
                    }
                }
            });

        $this->resetParticipationAtPhaseCompletion($globalEventGoal);
    }

    /**
     * Generate reward for the character.
     *
     * @param Character $character
     * @param GlobalEventGoal $globalEventGoal
     * @return void
     */
    protected function rewardForCharacter(Character $character, GlobalEventGoal $globalEventGoal) {
        $item = Item::where('specialty_type', $globalEventGoal->item_specialty_type_reward)
            ->whereIsNull('item_prefix_id')
            ->whereIsNull('item_suffix_id')
            ->whereDoesntHave('appliedHolyStacks')
            ->inRandomOrder()
            ->first();

        if (is_null($item)) {
            return;
        }

        if ($globalEventGoal->is_unique) {
            $randomAffixGenerator = $this->randomAffixGenerator->setPaidAmount(RandomAffixDetails::LEGENDARY);

            $newItem = $item->duplicate();

            $newItem->update([
                'item_prefix_id' => $randomAffixGenerator->generateAffix('prefix')->id,
                'item_suffix_id' => $randomAffixGenerator->generateAffix('suffix')->id,
            ]);

            $slot = $character->inventory->slots()->create([
                'inventory_id' => $character->inventory->id,
                'item_id'      => $newItem->id,
            ]);

            event(new ServerMessageEvent($character->user, 'You were rewarded with an item of Unique power for participating in the current events global goal.', $slot->id));
        }

        if ($globalEventGoal->is_mythic) {
            $randomAffixGenerator = $this->randomAffixGenerator->setPaidAmount(RandomAffixDetails::MYTHIC);

            $newItem = $item->duplicate();

            $newItem->update([
                'item_prefix_id' => $randomAffixGenerator->generateAffix('prefix')->id,
                'item_suffix_id' => $randomAffixGenerator->generateAffix('suffix')->id,
            ]);

            $slot = $character->inventory->slots()->create([
                'inventory_id' => $character->inventory->id,
                'item_id'      => $newItem->id,
            ]);

            event(new ServerMessageEvent($character->user, 'You were rewarded with an item of Mythical power for participating in the current events global goal.', $slot->id));
        }
    }

    /**
     * Reset the participation.
     *
     * @param GlobalEventGoal $globalEventGoal
     * @return void
     */
    private function resetParticipationAtPhaseCompletion(GlobalEventGoal $globalEventGoal) {
        $globalEventGoal->globalEventParticipation()->truncate();
    }
}
