<?php

namespace Tests\Unit\Game\Skills\Handlers;

use App\Flare\Models\GlobalEventCraftingInventory;
use App\Flare\Models\GlobalEventCraftingInventorySlot;
use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Values\WeaponTypes;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\GlobalEventSteps;
use App\Game\Skills\Handlers\HandleUpdatingCraftingGlobalEventGoal;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateGlobalEventGoal;
use Tests\Traits\CreateItem;

class HandleUpdatingCraftingGlobalEventGoalTest extends TestCase {

    use RefreshDatabase, CreateGlobalEventGoal, CreateEvent, CreateItem, CreateGameSkill;

    private ?HandleUpdatingCraftingGlobalEventGoal $handleUpdatingCraftingGlobalEventGoal;

    private ?CharacterFactory $characterFactory;

    public function setUp(): void {
        parent::setUp();

        $this->handleUpdatingCraftingGlobalEventGoal = resolve(HandleUpdatingCraftingGlobalEventGoal::class);

        $this->character = (new CharacterFactory())->createBaseCharacter()->assignSkill(
            $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING]),
            400
        );
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->handleUpdatingCraftingGlobalEventGoal = null;
        $this->character = null;
    }

    public function testDoNotParticipateInCraftingGlobalEventWhenEventDoesNotExist() {
        $item = $this->createItem(['type' => WeaponTypes::WEAPON]);
        $character = $this->character->getCharacter();

        $this->handleUpdatingCraftingGlobalEventGoal->handleUpdatingCraftingGlobalEventGoal($character, $item);

        $character = $character->refresh();

        $this->assertNull($character->globalEventCrafts);

        $this->assertNull($character->globalEventParticipation);

        $this->assertEmpty(GlobalEventCraftingInventory::all());
    }

    public function testDoNotParticipateInCraftingGlobalEventWhenGlobalEventDoesNotExist() {
        $this->createEvent([
            'type'                    => EventType::DELUSIONAL_MEMORIES_EVENT,
            'current_event_goal_step' => GlobalEventSteps::CRAFT,
        ]);

        $item = $this->createItem(['type' => WeaponTypes::WEAPON]);
        $character = $this->character->getCharacter();

        $this->handleUpdatingCraftingGlobalEventGoal->handleUpdatingCraftingGlobalEventGoal($character, $item);

        $character = $character->refresh();

        $this->assertNull($character->globalEventCrafts);

        $this->assertNull($character->globalEventParticipation);

        $this->assertEmpty(GlobalEventCraftingInventory::all());
    }

    public function testParticipateInGlobalCraftingEvent() {
        $this->createEvent([
            'type'                    => EventType::DELUSIONAL_MEMORIES_EVENT,
            'current_event_goal_step' => GlobalEventSteps::CRAFT,
        ]);

        $this->createGlobalEventGoal([
            'event_type'                  => EventType::DELUSIONAL_MEMORIES_EVENT,
            'max_crafts'                  => 100,
            'reward_every'                => 10,
            'next_reward_at'              => 10,
            'item_specialty_type_reward'  => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique'            => false,
            'should_be_mythic'            => true,
        ]);

        $item = $this->createItem(['type' => WeaponTypes::WEAPON]);
        $character = $this->character->getCharacter();

        $this->handleUpdatingCraftingGlobalEventGoal->handleUpdatingCraftingGlobalEventGoal($character, $item);

        $character = $character->refresh();

        $this->assertNotNull($character->globalEventCrafts);
        $this->assertNotNull($character->globalEventParticipation);
        $this->assertNotEmpty(GlobalEventCraftingInventory::all());

        $this->assertEquals(1, $character->globalEventCrafts->crafts);
        $this->assertEquals(1, $character->globalEventParticipation->current_crafts);

        $this->assertCount(1, GlobalEventCraftingInventorySlot::where('item_id', $item->id)->get());
    }

    public function testParticipateInGlobalCraftingEventWhenWeShouldBeRewarded() {
        $this->createEvent([
            'type'                    => EventType::DELUSIONAL_MEMORIES_EVENT,
            'current_event_goal_step' => GlobalEventSteps::CRAFT,
        ]);

        $eventGoal = $this->createGlobalEventGoal([
            'event_type'                  => EventType::DELUSIONAL_MEMORIES_EVENT,
            'max_crafts'                  => 100,
            'reward_every'                => 10,
            'next_reward_at'              => 10,
            'item_specialty_type_reward'  => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique'            => false,
            'should_be_mythic'            => true,
        ]);

        $character = $this->character->getCharacter();

        $this->createGlobalEventParticipation([
            'global_event_goal_id'  => $eventGoal->id,
            'character_id'          => $character->id,
            'current_crafts'        => 99,
        ]);

        $character->globalEventCrafts()->create([
            'global_event_goal_id' => $eventGoal->id,
            'character_id'         => $character->id,
            'crafts'               => 99,
        ]);

        $character = $character->refresh();

        $item = $this->createItem(['type' => WeaponTypes::WEAPON]);

        $this->handleUpdatingCraftingGlobalEventGoal->handleUpdatingCraftingGlobalEventGoal($character, $item);

        $character = $character->refresh();

        $this->assertNotNull($character->globalEventCrafts);
        $this->assertNotNull($character->globalEventParticipation);
        $this->assertNotEmpty(GlobalEventCraftingInventory::all());

        $this->assertEquals(100, $character->globalEventCrafts->crafts);
        $this->assertEquals(100, $character->globalEventParticipation->current_crafts);

        $this->assertCount(1, GlobalEventCraftingInventorySlot::where('item_id', $item->id)->get());

        $foundMythic = $character->inventory->slots->filter(function($slot) {
            return $slot->item->is_mythic;
        });

        $this->assertNotNull($foundMythic);
    }
}
