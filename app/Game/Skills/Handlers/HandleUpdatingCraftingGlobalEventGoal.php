<?php

namespace App\Game\Skills\Handlers;

use App\Game\Events\Events\UpdateEventGoalCurrentProgressForCharacter;
use Exception;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;
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

        if (is_null($event)) {
            return;
        }

        $globalEventGoal = GlobalEventGoal::where('event_type', $event->type)->first();

        if (is_null($globalEventGoal)) {
            return;
        }

        if ($globalEventGoal->total_crafts >= $globalEventGoal->max_crafts) {

            ServerMessageHandler::sendBasicMessage($character->user, '"Child, We need no more of these." The Red Hawk Soldier states, looking at the item. The event has been finished. The next stage will start soon. Use Craft to craft your own items.');

            return;
        }

        $this->updateOrCreateEventInventory($character, $globalEventGoal, $item);

        $this->handleUpdatingParticipation($character, $globalEventGoal, 'crafts');

        $globalEventGoal = $globalEventGoal->refresh();
        $character       = $character->refresh();

        if ($globalEventGoal->total_crafts >= $globalEventGoal->next_reward_at) {
            $newAmount = $globalEventGoal->next_reward_at + $globalEventGoal->reward_every;

            $this->rewardCharactersParticipating($globalEventGoal->refresh());

            $globalEventGoal->update([
                'next_reward_at' => $newAmount >= $globalEventGoal->max_crafts ? $globalEventGoal->max_crafts : $newAmount,
            ]);
        }

        event(new UpdateEventGoalProgress($this->eventGoalsService->getEventGoalData($character)));

        $amount = $character->globalEventKills->crafts;

        event(new UpdateEventGoalCurrentProgressForCharacter($character->user->id, $amount));

        ServerMessageHandler::sendBasicMessage($character->user, '"Thank you child! This item will help in the fight against The Federation! we shall save it for when we are ready for to enchant them!" The Red Hawk Soldier takes the item from you. Onto the next child.');

        $this->wasItemAccepted = true;
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
     * @param GlobalEventGoal $event
     * @param Item $item
     * @return void
     */
    private function updateOrCreateEventInventory(Character $character, GlobalEventGoal $event, Item $item): void {
        $inventory = GlobalEventCraftingInventory::firstOrCreate([
            'global_event_id' => $event->id,
            'character_id' => $character->id
        ]);

        GlobalEventCraftingInventorySlot::create([
            'global_event_crafting_inventory_id' => $inventory->id,
            'item_id' => $item->id,
        ]);
    }
}
