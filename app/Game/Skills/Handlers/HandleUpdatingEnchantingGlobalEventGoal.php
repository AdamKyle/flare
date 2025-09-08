<?php

namespace App\Game\Skills\Handlers;

use App\Flare\Items\Builders\RandomAffixGenerator;
use App\Flare\Models\Character;
use App\Flare\Models\Event;
use App\Flare\Models\GlobalEventCraftingInventorySlot;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Models\InventorySlot;
use App\Game\Events\Concerns\UpdateCharacterEventGoalParticipation;
use App\Game\Events\Events\UpdateEventGoalCurrentProgressForCharacter;
use App\Game\Events\Events\UpdateEventGoalProgress;
use App\Game\Events\Handlers\BaseGlobalEventGoalParticipationHandler;
use App\Game\Events\Services\EventGoalsService;
use App\Game\Events\Values\GlobalEventSteps;
use Exception;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;

class HandleUpdatingEnchantingGlobalEventGoal extends BaseGlobalEventGoalParticipationHandler
{
    use UpdateCharacterEventGoalParticipation;

    private bool $wasItemAccepted = false;

    public function __construct(RandomAffixGenerator $randomAffixGenerator, EventGoalsService $eventGoalsService)
    {
        parent::__construct($randomAffixGenerator, $eventGoalsService);
    }

    /**
     * Handle updating crafting global event goal.
     *
     * @throws Exception
     */
    public function handleUpdatingEnchantingGlobalEventGoal(Character $character, InventorySlot|GlobalEventCraftingInventorySlot $slot): void
    {

        $event = Event::where('current_event_goal_step', GlobalEventSteps::ENCHANT)->first();

        if (is_null($event)) {
            return;
        }

        $globalEventGoal = GlobalEventGoal::where('event_type', $event->type)->first();

        if (is_null($globalEventGoal)) {
            return;
        }

        $slot->delete();

        $this->handleUpdatingParticipation($character, $globalEventGoal, 'enchants');

        $globalEventGoal = $globalEventGoal->refresh();
        $character = $character->refresh();

        if ($globalEventGoal->total_enchants >= $globalEventGoal->next_reward_at) {
            $newAmount = $globalEventGoal->next_reward_at + $globalEventGoal->reward_every;

            $this->rewardCharactersParticipating($globalEventGoal->refresh());

            $globalEventGoal->update([
                'next_reward_at' => $newAmount >= $globalEventGoal->max_enchants ? $globalEventGoal->max_enchants : $newAmount,
            ]);
        }

        event(new UpdateEventGoalProgress($this->eventGoalsService->getEventGoalData($character)));

        $amount = $character->globalEventEnchants->enchants;

        event(new UpdateEventGoalCurrentProgressForCharacter($character->user->id, $amount));

        ServerMessageHandler::sendBasicMessage($character->user, '"Thank you child! This enchanted item will help in the fight against The Federation!" The Red Hawk Soldier takes the item from you. Onto the next child.');

        $this->wasItemAccepted = true;
    }

    /**
     * Did we hand over the item?
     */
    public function handedOverItem(): bool
    {
        return $this->wasItemAccepted;
    }
}
