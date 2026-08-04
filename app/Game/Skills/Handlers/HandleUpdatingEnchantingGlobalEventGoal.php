<?php

namespace App\Game\Skills\Handlers;

use App\Flare\Models\Character;
use App\Flare\Models\GlobalEventCraftingInventorySlot;
use App\Flare\Models\InventorySlot;
use App\Game\Core\Items\Builders\RandomAffixGenerator;
use App\Game\Events\Concerns\UpdateCharacterEventGoalParticipation;
use App\Game\Events\Events\UpdateEventGoalCurrentProgressForCharacter;
use App\Game\Events\Events\UpdateEventGoalProgress;
use App\Game\Events\Handlers\BaseGlobalEventGoalParticipationHandler;
use App\Game\Events\Services\EventGoalsService;
use App\Game\Events\Services\GlobalEventGoalEligibilityService;
use App\Game\Events\Services\GlobalEventGoalProgressionService;
use App\Game\Events\Values\GlobalEventSteps;
use Exception;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;

class HandleUpdatingEnchantingGlobalEventGoal extends BaseGlobalEventGoalParticipationHandler
{
    use UpdateCharacterEventGoalParticipation;

    private bool $wasItemAccepted = false;

    public function __construct(
        RandomAffixGenerator $randomAffixGenerator,
        EventGoalsService $eventGoalsService,
        private readonly GlobalEventGoalProgressionService $globalEventGoalProgressionService,
        private readonly GlobalEventGoalEligibilityService $globalEventGoalEligibilityService,
    ) {
        parent::__construct($randomAffixGenerator, $eventGoalsService);
    }

    /**
     * Handle updating crafting global event goal.
     *
     * @throws Exception
     */
    public function handleUpdatingEnchantingGlobalEventGoal(Character $character, InventorySlot|GlobalEventCraftingInventorySlot $slot): void
    {

        $event = $this->globalEventGoalEligibilityService->eventForCharacterMap($character);

        if (is_null($event) || $event->current_event_goal_step !== GlobalEventSteps::ENCHANT) {
            return;
        }

        $globalEventGoal = $event->globalEventGoals()->latest('id')->first();

        if (is_null($globalEventGoal)) {
            return;
        }

        if ($slot instanceof GlobalEventCraftingInventorySlot && $slot->inventory->global_event_goal_id !== $globalEventGoal->id) {
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

        $amount = $character->globalEventEnchants()
            ->where('global_event_goal_id', $globalEventGoal->id)
            ->first()?->enchants ?? 0;

        event(new UpdateEventGoalCurrentProgressForCharacter($character->user->id, $amount));

        ServerMessageHandler::sendBasicMessage($character->user, '"Thank you child! This enchanted item will help in the fight against The Federation!" The Red Hawk Soldier takes the item from you. Onto the next child.');

        $this->wasItemAccepted = true;

        if (! is_null($event->event_goal_steps)) {
            $this->globalEventGoalProgressionService->advanceIfCurrentGoalComplete($globalEventGoal);
        }
    }

    /**
     * Did we hand over the item?
     */
    public function handedOverItem(): bool
    {
        return $this->wasItemAccepted;
    }
}
