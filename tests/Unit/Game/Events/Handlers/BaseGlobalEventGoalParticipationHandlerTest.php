<?php

namespace Tests\Unit\Game\Events\Handlers;

use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Events\Handlers\BaseGlobalEventGoalParticipationHandler;
use App\Game\Events\Values\EventType;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGlobalEventGoal;
use Tests\Traits\CreateItem;

class BaseGlobalEventGoalParticipationHandlerTest extends TestCase
{
    use CreateGlobalEventGoal, CreateItem, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?BaseGlobalEventGoalParticipationHandler $baseGlobalEventGoalParticipationHandler;

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter();
        $this->baseGlobalEventGoalParticipationHandler = resolve(BaseGlobalEventGoalParticipationHandler::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->baseGlobalEventGoalParticipationHandler = null;
    }

    public function testDoesNotGivenItemToPlayerWhenItemDoesNotExist()
    {
        $character = $this->character->getCharacter();

        $globalEventGoal = $this->createGlobalEventGoal([
            'max_kills' => 100,
            'reward_every' => 10,
            'next_reward_at' => 10,
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
        ]);

        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $globalEventGoal->id,
            'character_id' => $character->id,
            'current_kills' => 500,
            'current_crafts' => 0,
        ]);

        $this->baseGlobalEventGoalParticipationHandler->rewardCharactersParticipating($globalEventGoal);

        $character = $character->refresh();

        $this->assertCount(0, $character->inventory->slots);
    }

    public function testPlayersInventoryIsFullWhenItComesToObtainingAReward()
    {

        Event::fake();

        $item = $this->createItem([
            'specialty_type' => ItemSpecialtyType::DELUSIONAL_SILVER,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $character->update([
            'inventory_max' => 1
        ]);

        $character = $character->refresh();

        $globalEventGoal = $this->createGlobalEventGoal([
            'max_kills' => 100,
            'reward_every' => 10,
            'next_reward_at' => 10,
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
        ]);

        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $globalEventGoal->id,
            'character_id' => $character->id,
            'current_kills' => 500,
            'current_crafts' => 0,
        ]);

        $this->baseGlobalEventGoalParticipationHandler->rewardCharactersParticipating($globalEventGoal);

        Event::assertDispatched(ServerMessageEvent::class, function ($event) {
            return $event->message === 'Child, your inventory is too full for you to be rewarded with any items for this event goal! You need to make some room.';
        });
    }

    public function testPlayerIsRewardedWithUniqueItem()
    {
        $this->createItem([
            'specialty_type' => ItemSpecialtyType::DELUSIONAL_SILVER,
        ]);

        $character = $this->character->getCharacter();

        $globalEventGoal = $this->createGlobalEventGoal([
            'max_kills' => 100,
            'reward_every' => 10,
            'next_reward_at' => 10,
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
        ]);

        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $globalEventGoal->id,
            'character_id' => $character->id,
            'current_kills' => 500,
            'current_crafts' => 0,
        ]);

        $this->baseGlobalEventGoalParticipationHandler->rewardCharactersParticipating($globalEventGoal);

        $character = $character->refresh();

        $this->assertCount(1, $character->inventory->slots);

        $foundSlot = $character->inventory->slots->filter(function ($slot) {
            return $slot->item->is_unique;
        })->first();

        $this->assertNotNull($foundSlot);
    }

    public function testPlayerIsRewardedWithMythicItem()
    {
        $this->createItem([
            'specialty_type' => ItemSpecialtyType::DELUSIONAL_SILVER,
        ]);

        $character = $this->character->getCharacter();

        $globalEventGoal = $this->createGlobalEventGoal([
            'max_kills' => 100,
            'reward_every' => 10,
            'next_reward_at' => 10,
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique' => false,
            'should_be_mythic' => true,
        ]);

        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $globalEventGoal->id,
            'character_id' => $character->id,
            'current_kills' => 500,
            'current_crafts' => 0,
        ]);

        $this->baseGlobalEventGoalParticipationHandler->rewardCharactersParticipating($globalEventGoal);

        $character = $character->refresh();

        $this->assertCount(1, $character->inventory->slots);

        $foundSlot = $character->inventory->slots->filter(function ($slot) {
            return $slot->item->is_mythic;
        })->first();

        $this->assertNotNull($foundSlot);
    }
}
