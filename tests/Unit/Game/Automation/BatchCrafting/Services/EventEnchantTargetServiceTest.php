<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\GlobalEventCraftingInventory;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingEndReason;
use App\Game\Automation\BatchCrafting\Services\EventEnchantTargetService;
use App\Game\Core\Items\Values\ItemSpecialtyType;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\GlobalEventSteps;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateGlobalCraftingInventory;
use Tests\Traits\CreateGlobalCraftingInventorySlot;
use Tests\Traits\CreateGlobalEventGoal;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateRunningEnchantEvent;
use Tests\Traits\CreateScheduledEvent;

class EventEnchantTargetServiceTest extends TestCase
{
    use CreateEvent,
        CreateGameMap,
        CreateGameSkill,
        CreateGlobalCraftingInventory,
        CreateGlobalCraftingInventorySlot,
        CreateGlobalEventGoal,
        CreateItem,
        CreateRunningEnchantEvent,
        CreateScheduledEvent,
        RefreshDatabase;

    private ?EventEnchantTargetService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = resolve(EventEnchantTargetService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->service = null;
    }

    public function test_current_goal_is_null_when_no_event_is_running(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->assertNull($this->service->currentGoal($character));
    }

    public function test_resolve_next_inventory_target_is_null_when_no_inventory_exists(): void
    {
        [$event, $goal, $gameMap] = $this->createRunningEnchantEvent();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->getCharacter();

        $this->assertNull($this->service->resolveNextInventoryTarget($character, $goal));
    }

    public function test_resolve_next_inventory_target_returns_the_eligible_slot_and_item(): void
    {
        [$event, $goal, $gameMap] = $this->createRunningEnchantEvent();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->getCharacter();

        $item = $this->createItem(['name' => 'Target Item', 'type' => 'body']);
        $inventory = $this->createGlobalCraftingInventory(['global_event_goal_id' => $goal->id, 'character_id' => $character->id]);
        $this->createGlobalCraftingInventorySlot(['global_event_crafting_inventory_id' => $inventory->id, 'item_id' => $item->id]);

        $result = $this->service->resolveNextInventoryTarget($character, $goal);

        $this->assertNotNull($result);
        $this->assertSame($item->id, $result->item->id);
    }

    public function test_resolve_next_inventory_target_excludes_ineligible_item_types(): void
    {
        [$event, $goal, $gameMap] = $this->createRunningEnchantEvent();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->getCharacter();

        $questItem = $this->createItem(['name' => 'Quest Item', 'type' => 'quest']);
        $inventory = $this->createGlobalCraftingInventory(['global_event_goal_id' => $goal->id, 'character_id' => $character->id]);
        $this->createGlobalCraftingInventorySlot(['global_event_crafting_inventory_id' => $inventory->id, 'item_id' => $questItem->id]);

        $this->assertNull($this->service->resolveNextInventoryTarget($character, $goal));
    }

    public function test_fallback_cycle_size_returns_the_craft_event_target_cycle_size(): void
    {
        $this->assertGreaterThan(0, $this->service->fallbackCycleSize());
    }

    public function test_resolve_fallback_craft_target_returns_a_real_craftable_item(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();

        $this->createItem(['name' => 'Fallback Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);

        $result = $this->service->resolveFallbackCraftTarget($character, 0);

        $this->assertNotNull($result);
        $this->assertSame('Fallback Dagger', $result['item']->name);
    }

    public function test_resolve_fallback_craft_target_returns_null_when_no_skill_exists(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->assertNull($this->service->resolveFallbackCraftTarget($character, 0));
    }

    public function test_add_fallback_item_to_inventory_creates_the_inventory_and_slot(): void
    {
        [$event, $goal, $gameMap] = $this->createRunningEnchantEvent();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->getCharacter();

        $item = $this->createItem(['name' => 'Fallback Item']);

        $this->service->addFallbackItemToInventory($character, $goal, $item);

        $inventory = GlobalEventCraftingInventory::where('global_event_goal_id', $goal->id)->where('character_id', $character->id)->first();

        $this->assertNotNull($inventory);
        $this->assertSame(1, $inventory->craftingSlots()->where('item_id', $item->id)->count());
    }

    public function test_resolve_unavailable_reason_is_event_not_running(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->assertSame(BatchCraftingEndReason::EVENT_NOT_RUNNING, $this->service->resolveUnavailableReason($character));
    }

    public function test_resolve_unavailable_reason_is_event_goal_complete_when_saturated(): void
    {
        [$event, $goal, $gameMap] = $this->createRunningEnchantEvent(1);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->getCharacter();

        $this->createGlobalEventParticipation(['global_event_goal_id' => $goal->id, 'character_id' => $character->id, 'current_enchants' => 1]);

        $this->assertSame(BatchCraftingEndReason::EVENT_GOAL_COMPLETE, $this->service->resolveUnavailableReason($character));
    }

    public function test_resolve_unavailable_reason_is_event_no_craftable_items_when_goal_has_capacity(): void
    {
        [$event, $goal, $gameMap] = $this->createRunningEnchantEvent();
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->getCharacter();

        $this->assertSame(BatchCraftingEndReason::EVENT_NO_CRAFTABLE_ITEMS, $this->service->resolveUnavailableReason($character));
    }

    public function test_resolve_unavailable_reason_is_event_wrong_map_when_on_a_secondary_event_map(): void
    {
        [$event, $goal, $primaryGameMap] = $this->createRunningEnchantEvent();
        $secondaryGameMap = $this->createGameMap(['name' => 'Secondary Event Map', 'default' => false, 'only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $secondaryGameMap)->getCharacter();

        $this->assertSame(BatchCraftingEndReason::EVENT_WRONG_MAP, $this->service->resolveUnavailableReason($character));
    }

    public function test_resolve_unavailable_reason_is_event_step_changed_when_the_step_is_not_enchant(): void
    {
        $schedule = $this->createScheduledEvent(['event_type' => EventType::DELUSIONAL_MEMORIES_EVENT, 'status' => 'running']);

        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'scheduled_event_id' => $schedule->id,
            'current_event_goal_step' => GlobalEventSteps::CRAFT,
            'ends_at' => now()->addHour(),
        ]);

        $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_id' => $event->id,
            'max_enchants' => 100,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER->value,
        ]);

        $gameMap = $this->createGameMap(['only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->getCharacter();

        $this->assertSame(BatchCraftingEndReason::EVENT_STEP_CHANGED, $this->service->resolveUnavailableReason($character));
    }
}
