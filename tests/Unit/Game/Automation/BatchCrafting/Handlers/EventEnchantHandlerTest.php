<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Handlers;

use App\Flare\Models\GameSkill;
use App\Flare\Models\GlobalEventCraftingInventory;
use App\Flare\Models\GlobalEventGoal;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingActionStatus;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingEndReason;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Handlers\EventEnchantHandler;
use App\Game\Automation\BatchCrafting\Services\EventEnchantTargetService;
use App\Game\Skills\Services\SkillCheckService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateGlobalCraftingInventory;
use Tests\Traits\CreateGlobalCraftingInventorySlot;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Traits\CreateRunningEnchantEvent;

class EventEnchantHandlerTest extends TestCase
{
    use CreateBatchCrafting,
        CreateGameSkill,
        CreateGlobalCraftingInventory,
        CreateGlobalCraftingInventorySlot,
        CreateItem,
        CreateItemAffix,
        CreateRunningEnchantEvent,
        RefreshDatabase;

    private ?EventEnchantHandler $handler;

    private array $progress;

    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = resolve(EventEnchantHandler::class);

        $this->progress = [
            'enchant_mode' => 'event',
            'event_goal_id' => null,
            'event_enchant_phase' => 'enchant_event_inventory',
            'current_item_id' => null,
            'current_item_name' => null,
            'current_prefix_name' => null,
            'current_suffix_name' => null,
            'enchanting_xp_gained' => 0,
            'crafting_xp_gained' => 0,
            'fallback_cycle_position' => 0,
        ];
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->handler = null;
    }

    public function test_handle_ends_event_not_running_when_no_eligible_goal_exists(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => $this->progress,
        ]);

        $result = $this->handler->handle($batchCrafting, $character);

        $this->assertSame(BatchCraftingEndReason::EVENT_NOT_RUNNING, $result->endReason());
    }

    public function test_handle_enchants_real_event_inventory_item_and_contributes(): void
    {
        [$event, $goal, $gameMap] = $this->createRunningEnchantEvent();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->getCharacter();

        $enchantingGameSkill = GameSkill::where('type', SkillTypeValue::ENCHANTING->value)->first();
        $character->skills()->where('game_skill_id', $enchantingGameSkill->id)->update(['level' => 10]);
        $character->update(['gold' => 10000]);
        $character = $character->refresh();

        $item = $this->createItem(['name' => 'Event Enchant Item', 'type' => 'body']);
        $inventory = $this->createGlobalCraftingInventory(['global_event_goal_id' => $goal->id, 'character_id' => $character->id]);
        $this->createGlobalCraftingInventorySlot(['global_event_crafting_inventory_id' => $inventory->id, 'item_id' => $item->id]);

        $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => false, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => -10, 'cost' => 100]);

        $progress = $this->progress;
        $progress['event_goal_id'] = $goal->id;

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => $progress,
        ]);

        $result = $this->handler->handle($batchCrafting, $character);

        $this->assertSame(BatchCraftingActionStatus::APPLIED, $result->actionStatus());
        $this->assertNull($result->endReason());

        $contribution = $character->globalEventEnchants()->where('global_event_goal_id', $goal->id)->first();
        $this->assertNotNull($contribution);
        $this->assertSame(1, $contribution->enchants);
    }

    public function test_handle_ends_no_enchanting_affix_when_none_exist(): void
    {
        [$event, $goal, $gameMap] = $this->createRunningEnchantEvent();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->getCharacter();
        $character->update(['gold' => 10000]);
        $character = $character->refresh();

        $item = $this->createItem(['name' => 'Event Enchant Item', 'type' => 'body']);
        $inventory = $this->createGlobalCraftingInventory(['global_event_goal_id' => $goal->id, 'character_id' => $character->id]);
        $this->createGlobalCraftingInventorySlot(['global_event_crafting_inventory_id' => $inventory->id, 'item_id' => $item->id]);

        $progress = $this->progress;
        $progress['event_goal_id'] = $goal->id;

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => $progress,
        ]);

        $result = $this->handler->handle($batchCrafting, $character);

        $this->assertSame(BatchCraftingEndReason::NO_ENCHANTING_AFFIX, $result->endReason());
    }

    public function test_handle_ends_int_too_low_without_choosing_a_weaker_affix(): void
    {
        [$event, $goal, $gameMap] = $this->createRunningEnchantEvent();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->getCharacter();

        $enchantingGameSkill = GameSkill::where('type', SkillTypeValue::ENCHANTING->value)->first();
        $character->skills()->where('game_skill_id', $enchantingGameSkill->id)->update(['level' => 10]);
        $character->update(['gold' => 10000, 'int' => 1]);
        $character = $character->refresh();

        $item = $this->createItem(['name' => 'Event Enchant Item', 'type' => 'body']);
        $inventory = $this->createGlobalCraftingInventory(['global_event_goal_id' => $goal->id, 'character_id' => $character->id]);
        $this->createGlobalCraftingInventorySlot(['global_event_crafting_inventory_id' => $inventory->id, 'item_id' => $item->id]);

        $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => false, 'int_required' => 100000, 'skill_level_required' => 1, 'skill_level_trivial' => -10, 'cost' => 100]);

        $progress = $this->progress;
        $progress['event_goal_id'] = $goal->id;

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => $progress,
        ]);

        $result = $this->handler->handle($batchCrafting, $character);

        $this->assertSame(BatchCraftingEndReason::INT_TOO_LOW, $result->endReason());
    }

    public function test_handle_moves_to_craft_fallback_phase_when_inventory_is_empty(): void
    {
        [$event, $goal, $gameMap] = $this->createRunningEnchantEvent();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->getCharacter();

        $progress = $this->progress;
        $progress['event_goal_id'] = $goal->id;

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => $progress,
        ]);

        $result = $this->handler->handle($batchCrafting, $character);

        $this->assertNull($result->endReason());
        $this->assertNull($result->actionStatus());
        $this->assertSame('craft_fallback_set', $batchCrafting->refresh()->progress['event_enchant_phase']);
    }

    public function test_handle_craft_fallback_phase_crafts_item_and_moves_to_enchant_fallback_phase(): void
    {
        [$event, $goal, $gameMap] = $this->createRunningEnchantEvent();

        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['gold' => 10000]);
        $character = $character->refresh();

        $this->createItem(['name' => 'Fallback Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);

        $progress = $this->progress;
        $progress['event_goal_id'] = $goal->id;
        $progress['event_enchant_phase'] = 'craft_fallback_set';

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => $progress,
        ]);

        $result = $this->handler->handle($batchCrafting, $character);

        $this->assertNull($result->endReason());
        $refreshed = $batchCrafting->refresh()->progress;
        $this->assertSame('enchant_fallback_set', $refreshed['event_enchant_phase']);
        $this->assertSame(1, $refreshed['fallback_cycle_position']);

        $inventory = GlobalEventCraftingInventory::where('global_event_goal_id', $goal->id)->where('character_id', $character->id)->first();
        $this->assertNotNull($inventory);
        $this->assertSame(1, $inventory->craftingSlots()->count());
    }

    public function test_handle_ends_event_goal_complete_when_contribution_reaches_max(): void
    {
        [, $goal, $gameMap] = $this->createRunningEnchantEvent(1);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->getCharacter();

        $this->createGlobalEventParticipation(['global_event_goal_id' => $goal->id, 'character_id' => $character->id, 'current_enchants' => 1]);

        $progress = $this->progress;
        $progress['event_goal_id'] = $goal->id;

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => $progress,
        ]);

        $result = $this->handler->handle($batchCrafting, $character);

        $this->assertSame(BatchCraftingEndReason::EVENT_GOAL_COMPLETE, $result->endReason());
    }

    public function test_handle_ends_event_goal_complete_when_goal_reaches_cap_between_lookup_and_recheck(): void
    {
        [$event, $goal, $gameMap] = $this->createRunningEnchantEvent();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->getCharacter();

        $staleGoal = GlobalEventGoal::find($goal->id);
        $staleGoal->load('globalEventParticipation');

        $this->createGlobalEventParticipation(['global_event_goal_id' => $goal->id, 'character_id' => $character->id, 'current_enchants' => $goal->max_enchants]);

        $this->instance(
            EventEnchantTargetService::class,
            Mockery::mock(EventEnchantTargetService::class, function (MockInterface $mock) use ($staleGoal): void {
                $mock->shouldReceive('currentGoal')->andReturn($staleGoal);
                $mock->shouldReceive('resolveNextInventoryTarget')->andReturn(null);
            })
        );

        $progress = $this->progress;
        $progress['event_goal_id'] = $goal->id;

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => $progress,
        ]);

        $handler = resolve(EventEnchantHandler::class);
        $result = $handler->handle($batchCrafting, $character);

        $this->assertSame(BatchCraftingEndReason::EVENT_GOAL_COMPLETE, $result->endReason());
    }

    public function test_handle_returns_a_failed_result_when_the_enchant_roll_fails(): void
    {
        [$event, $goal, $gameMap] = $this->createRunningEnchantEvent();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->getCharacter();

        $enchantingGameSkill = GameSkill::where('type', SkillTypeValue::ENCHANTING->value)->first();
        $character->skills()->where('game_skill_id', $enchantingGameSkill->id)->update(['level' => 10]);
        $character->update(['gold' => 10000]);
        $character = $character->refresh();

        $item = $this->createItem(['name' => 'Event Enchant Item', 'type' => 'body']);
        $inventory = $this->createGlobalCraftingInventory(['global_event_goal_id' => $goal->id, 'character_id' => $character->id]);
        $this->createGlobalCraftingInventorySlot(['global_event_crafting_inventory_id' => $inventory->id, 'item_id' => $item->id]);

        $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => false, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 50, 'cost' => 100]);

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock): void {
                $mock->shouldReceive('getDCCheck')->andReturn(1000);
                $mock->shouldReceive('characterRoll')->andReturn(1);
            })
        );

        $progress = $this->progress;
        $progress['event_goal_id'] = $goal->id;

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => $progress,
        ]);

        $handler = resolve(EventEnchantHandler::class);
        $result = $handler->handle($batchCrafting, $character);

        $this->assertSame(BatchCraftingActionStatus::FAILED, $result->actionStatus());
        $this->assertFalse($result->didCraft());
        $this->assertNull($result->endReason());
    }

    public function test_handle_craft_fallback_phase_ends_no_craftable_items_when_none_are_available(): void
    {
        [$event, $goal, $gameMap] = $this->createRunningEnchantEvent();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->getCharacter();

        $progress = $this->progress;
        $progress['event_goal_id'] = $goal->id;
        $progress['event_enchant_phase'] = 'craft_fallback_set';

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => $progress,
        ]);

        $result = $this->handler->handle($batchCrafting, $character);

        $this->assertSame(BatchCraftingEndReason::EVENT_NO_CRAFTABLE_ITEMS, $result->endReason());
    }

    public function test_handle_craft_fallback_phase_translates_a_failed_craft_roll(): void
    {
        [$event, $goal, $gameMap] = $this->createRunningEnchantEvent();

        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['gold' => 10000]);
        $character = $character->refresh();

        $this->createItem(['name' => 'Fallback Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 1, 'skill_level_trivial' => 50]);

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock): void {
                $mock->shouldReceive('getDCCheck')->andReturn(1000);
                $mock->shouldReceive('characterRoll')->andReturn(1);
            })
        );

        $progress = $this->progress;
        $progress['event_goal_id'] = $goal->id;
        $progress['event_enchant_phase'] = 'craft_fallback_set';

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => $progress,
        ]);

        $handler = resolve(EventEnchantHandler::class);
        $result = $handler->handle($batchCrafting, $character);

        $this->assertSame(BatchCraftingActionStatus::FAILED, $result->actionStatus());
        $this->assertFalse($result->didCraft());
        $this->assertSame(1, $batchCrafting->refresh()->progress['fallback_cycle_position']);
    }
}
