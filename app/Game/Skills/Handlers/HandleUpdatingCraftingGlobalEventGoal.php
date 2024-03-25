<?php

namespace App\Game\Skills\Handlers;

use App\Flare\Builders\RandomAffixGenerator;
use App\Flare\Models\Character;
use App\Flare\Models\Event;
use App\Flare\Models\GlobalEventCraftingInventory;
use App\Flare\Models\GlobalEventCraftingInventorySlot;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Models\Item;
use App\Game\Events\Concerns\UpdateCharacterEventGoalParticipation;
use App\Game\Events\Events\UpdateEventGoalProgress;
use App\Game\Events\Handlers\BaseGlobalEventGoalParticipationHandler;
use App\Game\Events\Services\EventGoalsService;
use App\Game\Events\Values\GlobalEventSteps;
use Exception;

class HandleUpdatingCraftingGlobalEventGoal extends BaseGlobalEventGoalParticipationHandler {

    use UpdateCharacterEventGoalParticipation;

    private bool $wasItemAccepted = false;

    /**
     * @param RandomAffixGenerator $randomAffixGenerator
     * @param EventGoalsService $eventGoalsService
     */
    public function __construct(RandomAffixGenerator $randomAffixGenerator, EventGoalsService $eventGoalsService) {
        parent::__construct($randomAffixGenerator, $eventGoalsService);
    }


    /**
     * Handle updating crafting global event goal.
     *
     * @param Character $character
     * @param Item $item
     * @return void
     * @throws Exception
     */
    public function handleUpdatingCraftingGlobalEventGoal(Character $character, Item $item): void {

        $event = Event::where('current_event_goal_step', GlobalEventSteps::CRAFT)->first();
        $globalEventGoal = GlobalEventGoal::where('event_type', $event->type)->first();

        if (is_null($event || $globalEventGoal)) {
            return;
        }

        $this->updateOrCreateEventInventory($character, $event, $item);

        $this->handleUpdatingCraftingGlobalEventGoal($character, $globalEventGoal, 'crafts');

        $globalEventGoal = $globalEventGoal->refrsh();
        $character       = $character->refresh();

        if ($globalEventGoal->total_crafts >= $globalEventGoal->next_reward_at) {
            $newAmount = $globalEventGoal->next_reward_at + $globalEventGoal->reward_every;

            $this->rewardCharactersParticipating($globalEventGoal->refresh());

            $globalEventGoal->update([
                'next_reward_at' => $newAmount >= $globalEventGoal->max_crafts ? $globalEventGoal->max_crafts : $newAmount,
            ]);
        }

        event(new UpdateEventGoalProgress($this->eventGoalsService->getEventGoalData($character)));

        ServerMessageHandler::sendBasicMessage($character->user, '"Thank you child! This weapon will help in the fight against The Federation!" The Red Hawk Soldier takes the item from you. Onto the next child.');
    }

    /**
     * Did we hand over the item?
     *
     * @return bool
     */
    public function handedOverItem(): bool {
        return $this->wasItemAccepted;
    }

    /**
     * Set the item into the characters global event crafting inventory.
     *
     * @param Character $character
     * @param Event $event
     * @param Item $item
     * @return void
     */
    private function updateOrCreateEventInventory(Character $character, Event $event, Item $item): void {
        $inventory = GlobalEventCraftingInventory::firstOrCreate([
            'event_id' => $event->id,
            'character_id' => $character->id
        ]);

        GlobalEventCraftingInventorySlot::create([
            'global_event_crafting_inventory_id' => $inventory->id,
            'item_id' => $item->id,
        ]);
    }
}
