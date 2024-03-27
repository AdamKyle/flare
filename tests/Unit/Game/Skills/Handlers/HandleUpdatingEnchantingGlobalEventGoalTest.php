<?php

namespace Tests\Unit\Game\Skills\Handlers;

use App\Flare\Models\GlobalEventCraftingInventory;
use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Values\WeaponTypes;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\GlobalEventSteps;
use App\Game\Skills\Handlers\HandleUpdatingEnchantingGlobalEventGoal;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateGlobalEventGoal;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class HandleUpdatingEnchantingGlobalEventGoalTest extends TestCase {

    use RefreshDatabase, CreateGlobalEventGoal, CreateEvent, CreateItem, CreateGameSkill, CreateItemAffix;

    private ?HandleUpdatingEnchantingGlobalEventGoal $handleUpdateEnchantingGlobalEventGoal;

    private ?CharacterFactory $characterFactory;

    public function setUp(): void {
        parent::setUp();

        $this->handleUpdateEnchantingGlobalEventGoal = resolve(HandleUpdatingEnchantingGlobalEventGoal::class);

        $this->character = (new CharacterFactory())->createBaseCharacter()->assignSkill(
            $this->createGameSkill(['name' => 'Enchanting', 'type' => SkillTypeValue::ENCHANTING]),
            400
        );
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->handleUpdateEnchantingGlobalEventGoal = null;
        $this->character = null;
    }

    public function testDoNotParticipateInEnchantingGlobalEventWhenEventDoesNotExist() {
        $item = $this->createItem(['type' => WeaponTypes::WEAPON, 'item_prefix_id' => $this->createItemAffix(['type' => 'prefix'])->id]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $slot = $character->inventory->slots->first();

        $this->handleUpdateEnchantingGlobalEventGoal->handleUpdatingEnchantingGlobalEventGoal($character, $slot);

        $character = $character->refresh();

        $this->assertNull($character->globalEventEnchants);

        $this->assertNull($character->globalEventParticipation);

        $this->assertEmpty(GlobalEventCraftingInventory::all());

        $this->assertNotEmpty($character->inventory->slots);
    }

    public function testDoNotParticipateInEnchantingGlobalEventWhenGlobalEventDoesNotExist() {
        $this->createEvent([
            'type'                    => EventType::DELUSIONAL_MEMORIES_EVENT,
            'current_event_goal_step' => GlobalEventSteps::ENCHANT,
        ]);

        $item = $this->createItem(['type' => WeaponTypes::WEAPON, 'item_prefix_id' => $this->createItemAffix(['type' => 'prefix'])->id]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $slot = $character->inventory->slots->first();

        $this->handleUpdateEnchantingGlobalEventGoal->handleUpdatingEnchantingGlobalEventGoal($character, $slot);

        $character = $character->refresh();

        $this->assertNull($character->globalEventEnchants);

        $this->assertNull($character->globalEventParticipation);

        $this->assertNotEmpty($character->inventory->slots);
    }

    public function testParticipateInGlobalEnchantingEvent() {
        $this->createEvent([
            'type'                    => EventType::DELUSIONAL_MEMORIES_EVENT,
            'current_event_goal_step' => GlobalEventSteps::ENCHANT,
        ]);

        $this->createGlobalEventGoal([
            'event_type'                  => EventType::DELUSIONAL_MEMORIES_EVENT,
            'max_enchants'                => 100,
            'reward_every'                => 10,
            'next_reward_at'              => 10,
            'item_specialty_type_reward'  => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique'            => false,
            'should_be_mythic'            => true,
        ]);

        $item = $this->createItem(['type' => WeaponTypes::WEAPON, 'item_prefix_id' => $this->createItemAffix(['type' => 'prefix'])->id]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $slot = $character->inventory->slots->first();

        $this->handleUpdateEnchantingGlobalEventGoal->handleUpdatingEnchantingGlobalEventGoal($character, $slot);

        $character = $character->refresh();

        $this->assertNotNull($character->globalEventEnchants);
        $this->assertNotNull($character->globalEventParticipation);

        $this->assertEquals(1, $character->globalEventEnchants->enchants);
        $this->assertEquals(1, $character->globalEventParticipation->current_enchants);

        $this->assertempty($character->inventory->slots);
    }

    public function testParticipateInGlobalEnchantingEventWhenWeShouldBeRewarded() {
        $this->createItem(['name' => 'Delusional silver', 'specialty_type' => ItemSpecialtyType::DELUSIONAL_SILVER]);

        $this->createEvent([
            'type'                    => EventType::DELUSIONAL_MEMORIES_EVENT,
            'current_event_goal_step' => GlobalEventSteps::ENCHANT,
        ]);

        $eventGoal = $this->createGlobalEventGoal([
            'event_type'                  => EventType::DELUSIONAL_MEMORIES_EVENT,
            'max_enchants'                => 100,
            'reward_every'                => 10,
            'next_reward_at'              => 10,
            'item_specialty_type_reward'  => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique'            => false,
            'should_be_mythic'            => true,
        ]);


        $item = $this->createItem(['name' => 'Item To Enchant', 'type' => WeaponTypes::WEAPON, 'item_prefix_id' => $this->createItemAffix(['type' => 'prefix'])->id]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $this->createGlobalEventParticipation([
            'global_event_goal_id'  => $eventGoal->id,
            'character_id'          => $character->id,
            'current_enchants'      => 99,
        ]);

        $character->globalEventEnchants()->create([
            'global_event_goal_id' => $eventGoal->id,
            'character_id'         => $character->id,
            'enchants'             => 99,
        ]);

        $character = $character->refresh();

        $slot = $character->inventory->slots->first();

        $this->handleUpdateEnchantingGlobalEventGoal->handleUpdatingEnchantingGlobalEventGoal($character, $slot);

        $character = $character->refresh();

        $this->assertNotNull($character->globalEventEnchants);
        $this->assertNotNull($character->globalEventParticipation);

        $this->assertEquals(100, $character->globalEventEnchants->enchants);
        $this->assertEquals(100, $character->globalEventParticipation->current_enchants);

        $foundMythic = $character->inventory->slots->filter(function($slot) {
            return $slot->item->is_mythic;
        })->first();

        $this->assertNotNull($foundMythic);

        $this->assertCount(1, $character->inventory->slots);
    }
}
