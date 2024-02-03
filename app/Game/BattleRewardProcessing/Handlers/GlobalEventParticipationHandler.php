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
     * @throws \Exception
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

            $character->globalEventKills()->create([
                'global_event_goal_id' => $globalEventGoal->id,
                'character_id'         => $character->id,
                'kills'                => 1,
            ]);

            $character = $character->refresh();

            event(new UpdateEventGoalProgress($this->eventGoalService->getEventGoalData($character)));

            return;
        }

        $character->globalEventParticipation()->update([
            'current_kills' => $character->globalEventParticipation->current_kills + 1,
        ]);

        $character->globalEventKills()->update([
            'kills' => $character->globalEventKills->kills + 1,
        ]);

        $character = $character->refresh();

        $globalEventGoal = $globalEventGoal->refresh();

        if ($globalEventGoal->total_kills >= $globalEventGoal->next_reward_at) {
            $newAmount = $globalEventGoal->next_reward_at + $globalEventGoal->reward_every_kills;

            $this->rewardCharactersParticipating($globalEventGoal->refresh());

            $globalEventGoal->update([
                'next_reward_at' => $newAmount >= $globalEventGoal->max_kills ? $globalEventGoal->max_kills : $newAmount,
            ]);
        }

        event(new UpdateEventGoalProgress($this->eventGoalService->getEventGoalData($character)));
    }

    /**
     * Reward only those who have met the required amount of kills or higher.
     *
     * @param GlobalEventGoal $globalEventGoal
     * @return void
     * @throws \Exception
     */
    protected function rewardCharactersParticipating(GlobalEventGoal $globalEventGoal) {

        Character::whereIn('id', $globalEventGoal->globalEventParticipation->pluck('character_id')->toArray())
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
    }

    /**
     * Generate reward for the character.
     *
     * @param Character $character
     * @param GlobalEventGoal $globalEventGoal
     * @return void
     * @throws \Exception
     */
    protected function rewardForCharacter(Character $character, GlobalEventGoal $globalEventGoal) {

        $item = Item::where('specialty_type', $globalEventGoal->item_specialty_type_reward)
            ->whereNull('item_prefix_id')
            ->whereNull('item_suffix_id')
            ->whereDoesntHave('appliedHolyStacks')
            ->inRandomOrder()
            ->first();

        if (is_null($item)) {
            return;
        }

        if ($globalEventGoal->should_be_unique) {

            $randomAffixGenerator = $this->randomAffixGenerator->setCharacter($character)->setPaidAmount(RandomAffixDetails::LEGENDARY);

            $newItem = $item->duplicate();

            $newItem->update([
                'item_prefix_id' => $randomAffixGenerator->generateAffix('prefix')->id,
                'item_suffix_id' => $randomAffixGenerator->generateAffix('suffix')->id,
            ]);

            $newItem = $newItem->refresh();

            $slot = $character->inventory->slots()->create([
                'inventory_id' => $character->inventory->id,
                'item_id'      => $newItem->id,
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
            ]);

            $newItem = $newItem->refresh();

            $slot = $character->inventory->slots()->create([
                'inventory_id' => $character->inventory->id,
                'item_id'      => $newItem->id,
            ]);

            event(new ServerMessageEvent($character->user, 'You were rewarded with an item of Mythical power for participating in the current events global goal.', $slot->id));
        }
    }
}
