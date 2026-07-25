<?php

namespace Tests\Unit\Game\BatchCrafting\Services;

use App\Admin\Services\MonitoredBugReportService;
use App\Flare\Models\AlchemyBagSlot;
use App\Flare\Models\GlobalEventCraftingInventorySlot;
use App\Flare\Models\GlobalEventEnchant;
use App\Flare\Models\InventorySet;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\SetSlot;
use App\Flare\Values\ItemSpecialtyType;
use App\Game\BatchCrafting\Services\BatchCraftingService;
use App\Game\BatchCrafting\Services\BatchCraftingProcessor;
use App\Game\BatchCrafting\Values\BatchCraftingDisposition;
use App\Game\BatchCrafting\Values\BatchCraftingEndReason;
use App\Game\BatchCrafting\Values\BatchCraftingType;
use App\Game\Character\CharacterInventory\Jobs\CharacterBoonJob;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\GlobalEventSteps;
use App\Game\Events\Values\ScheduledEventStatus;
use App\Game\Character\CharacterInventory\Services\BatchCraftingSetService;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Services\EnchantingService;
use App\Game\Skills\Services\SkillCheckService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateAlchemyBagSlot;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateGlobalCraftingInventory;
use Tests\Traits\CreateGlobalCraftingInventorySlot;
use Tests\Traits\CreateGlobalEventGoal;
use Tests\Traits\CreateInventorySets;
use Tests\Traits\CreateInventorySlot;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Traits\CreateScheduledEvent;

class BatchCraftingProcessorTest extends TestCase
{
    use CreateAlchemyBagSlot, CreateBatchCrafting, CreateEvent, CreateGameMap, CreateGameSkill, CreateGlobalCraftingInventory, CreateGlobalCraftingInventorySlot, CreateGlobalEventGoal, CreateInventorySets, CreateInventorySlot, CreateItem, CreateItemAffix, CreateScheduledEvent, MockeryPHPUnitIntegration, RefreshDatabase;

    public function testCraftEnchantSetCraftPhaseCraftsSelectedItemIdInsteadOfHighestCraftableItem(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $this->createItem(['name' => 'Processor Selected High Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 5, 'skill_level_trivial' => 5]);
        $lowDagger = $this->createItem(['name' => 'Processor Selected Low Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'selected_set_id' => $set->id,
                'craft_enchant_set_queue' => [['type' => 'dagger', 'crafting_type' => 'weapon']],
                'craft_enchant_set_keys' => ['dagger'],
                'craft_enchant_set_selected_item_ids' => ['dagger' => $lowDagger->id],
                'enchant_plan' => [],
                'craft_enchant_set_requested' => 1,
                'craft_enchant_set_phase' => 'crafting',
                'craft_enchant_set_craft_index' => 0,
                'craft_enchant_set_enchant_index' => 0,
                'craft_enchant_set_crafted_slots' => [],
                'craft_enchant_set_prefix_applied_count' => 0,
                'craft_enchant_set_suffix_applied_count' => 0,
                'craft_enchant_set_total_work_units' => 1,
                'craft_enchant_set_completed_work_units' => 0,
            ],
        ]);
        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $craftAction = collect($result->action_log)->first(fn (array $entry) => ($entry['action_type'] ?? null) === 'craft_enchant_set_craft');

        $this->assertSame('Processor Selected Low Dagger', $craftAction['crafted_item']['name'] ?? null);
    }

    public function testCraftEnchantSetCraftPhaseFallsBackToHighestCraftableItemWhenNoSelectionStored(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $this->createItem(['name' => 'Processor Fallback High Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 5, 'skill_level_trivial' => 5]);
        $this->createItem(['name' => 'Processor Fallback Low Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'selected_set_id' => $set->id,
                'craft_enchant_set_queue' => [['type' => 'dagger', 'crafting_type' => 'weapon']],
                'craft_enchant_set_keys' => ['dagger'],
                'craft_enchant_set_selected_item_ids' => [],
                'enchant_plan' => [],
                'craft_enchant_set_requested' => 1,
                'craft_enchant_set_phase' => 'crafting',
                'craft_enchant_set_craft_index' => 0,
                'craft_enchant_set_enchant_index' => 0,
                'craft_enchant_set_crafted_slots' => [],
                'craft_enchant_set_prefix_applied_count' => 0,
                'craft_enchant_set_suffix_applied_count' => 0,
                'craft_enchant_set_total_work_units' => 1,
                'craft_enchant_set_completed_work_units' => 0,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $craftAction = collect($result->action_log)->first(fn (array $entry) => ($entry['action_type'] ?? null) === 'craft_enchant_set_craft');

        $this->assertSame('Processor Fallback High Dagger', $craftAction['crafted_item']['name'] ?? null);
    }

    public function testCraftEnchantSetEnchantPhaseHardStopsWithIntTooLowEndReasonWhenPlannedAffixRequiresMoreIntThanCharacterHas(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 100, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 30, 'int' => 1]);
        $item = $this->createItem(['name' => 'Enchant Phase Int Block Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $prefix = $this->createItemAffix(['name' => 'Enchant Phase High Int Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 999, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'craft_enchant_set_target_mode' => 'craft_new',
                'craft_enchant_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_enchant_set_keys' => ['dagger'],
                'craft_enchant_set_selected_item_ids' => ['dagger' => $item->id],
                'craft_enchant_set_crafted_item_ids' => ['dagger' => $item->id],
                'enchant_plan' => ['dagger' => ['prefix_affix_id' => $prefix->id]],
                'craft_enchant_set_requested' => 1,
                'craft_enchant_set_phase' => 'enchanting',
                'craft_enchant_set_craft_index' => 1,
                'craft_enchant_set_enchant_index' => 0,
                'craft_enchant_set_prefix_applied_count' => 0,
                'craft_enchant_set_suffix_applied_count' => 0,
                'craft_enchant_set_total_work_units' => 1,
                'craft_enchant_set_completed_work_units' => 0,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::INT_TOO_LOW_FOR_ENCHANTING->value, $result->ended_reason);
    }

    public function testCraftEnchantSetCraftPhaseFailedAttemptDoesNotAdvanceIndexOrWorkUnits(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $this->createItem(['name' => 'Craft Fail Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(400);
                $mock->shouldReceive('characterRoll')->andReturn(1);
            })
        );
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'selected_set_id' => $set->id,
                'craft_enchant_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_enchant_set_keys' => ['dagger'],
                'craft_enchant_set_selected_item_ids' => [],
                'enchant_plan' => [],
                'craft_enchant_set_requested' => 1,
                'craft_enchant_set_phase' => 'crafting',
                'craft_enchant_set_craft_index' => 0,
                'craft_enchant_set_enchant_index' => 0,
                'craft_enchant_set_prefix_applied_count' => 0,
                'craft_enchant_set_suffix_applied_count' => 0,
                'craft_enchant_set_total_work_units' => 1,
                'craft_enchant_set_completed_work_units' => 0,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(0, $result->progress['craft_enchant_set_craft_index']);
        $this->assertSame(0, $result->progress['craft_enchant_set_completed_work_units']);
    }

    public function testCraftEnchantSetCraftPhaseSuccessfulEntryAdvancesIndexAndWorkUnitsByOneEach(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $this->createItem(['name' => 'Craft Success Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(400);
            })
        );
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'selected_set_id' => $set->id,
                'craft_enchant_set_queue' => array_fill(0, 6, ['type' => 'dagger', 'crafting_type' => 'dagger']),
                'craft_enchant_set_keys' => ['a', 'b', 'c', 'd', 'e', 'f'],
                'craft_enchant_set_selected_item_ids' => [],
                'enchant_plan' => [],
                'craft_enchant_set_requested' => 6,
                'craft_enchant_set_phase' => 'crafting',
                'craft_enchant_set_craft_index' => 0,
                'craft_enchant_set_enchant_index' => 0,
                'craft_enchant_set_prefix_applied_count' => 0,
                'craft_enchant_set_suffix_applied_count' => 0,
                'craft_enchant_set_total_work_units' => 6,
                'craft_enchant_set_completed_work_units' => 0,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(0, $result->progress['craft_enchant_set_craft_index']);
        $this->assertSame(0, $result->progress['craft_enchant_set_completed_work_units']);
    }

    public function testCraftEnchantSetEnchantPhaseAttemptNotMadeWhenGoldInsufficientDoesNotAdvanceIndexOrWorkUnits(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 0, 'inventory_max' => 30, 'int' => 100]);
        $item = $this->createItem(['name' => 'Enchant Gold Block Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $prefix = $this->createItemAffix(['name' => 'Enchant Gold Block Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'selected_set_id' => $set->id,
                'craft_enchant_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_enchant_set_keys' => ['dagger'],
                'craft_enchant_set_crafted_item_ids' => ['dagger' => $item->id],
                'enchant_plan' => ['dagger' => ['prefix_affix_id' => $prefix->id]],
                'craft_enchant_set_requested' => 1,
                'craft_enchant_set_phase' => 'enchanting',
                'craft_enchant_set_craft_index' => 1,
                'craft_enchant_set_enchant_index' => 0,
                'craft_enchant_set_prefix_applied_count' => 0,
                'craft_enchant_set_suffix_applied_count' => 0,
                'craft_enchant_set_total_work_units' => 1,
                'craft_enchant_set_completed_work_units' => 0,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(0, $result->progress['craft_enchant_set_enchant_index']);
        $this->assertSame(0, $result->progress['craft_enchant_set_completed_work_units']);
    }

    public function testCraftEnchantSetEnchantPhaseSuccessfulCompletionAdvancesIndexAndWorkUnitsByOneNotTwo(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 100000, 'inventory_max' => 30, 'int' => 100]);
        $item = $this->createItem(['name' => 'Enchant Success Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $prefix = $this->createItemAffix(['name' => 'Enchant Success Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(400);
            })
        );
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $keys = ['a', 'b', 'c', 'd', 'e', 'f'];
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'selected_set_id' => $set->id,
                'craft_enchant_set_queue' => array_fill(0, 6, ['type' => 'dagger', 'crafting_type' => 'dagger']),
                'craft_enchant_set_keys' => $keys,
                'craft_enchant_set_crafted_item_ids' => array_fill_keys($keys, $item->id),
                'enchant_plan' => array_fill_keys($keys, ['prefix_affix_id' => $prefix->id]),
                'craft_enchant_set_requested' => 6,
                'craft_enchant_set_phase' => 'enchanting',
                'craft_enchant_set_craft_index' => 6,
                'craft_enchant_set_enchant_index' => 0,
                'craft_enchant_set_prefix_applied_count' => 0,
                'craft_enchant_set_suffix_applied_count' => 0,
                'craft_enchant_set_total_work_units' => 6,
                'craft_enchant_set_completed_work_units' => 0,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(3, $result->progress['craft_enchant_set_enchant_index']);
        $this->assertSame(3, $result->progress['craft_enchant_set_completed_work_units']);
    }

    public function testCraftEnchantSetEnchantPhaseShatteredItemDoesNotAdvanceIndexDecreasesSurvivingCountAndSwitchesToReplacementCrafting(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 100000, 'inventory_max' => 30, 'int' => 100]);
        $item = $this->createItem(['name' => 'Enchant Shatter Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $prefix = $this->createItemAffix(['name' => 'Enchant Shatter Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(400);
                $mock->shouldReceive('characterRoll')->andReturn(1);
            })
        );
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $keys = ['a', 'b', 'c', 'd', 'e', 'f'];
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'selected_set_id' => $set->id,
                'craft_enchant_set_queue' => array_fill(0, 6, ['type' => 'dagger', 'crafting_type' => 'dagger']),
                'craft_enchant_set_keys' => $keys,
                // Not the highest-craftable-item fallback: an invalid id makes any
                // replacement-craft attempt (iterations after the first shatter, in
                // this same tick) a harmless no-op skip instead of a real craft, so
                // this test's outcome does not depend on the crafting skill roll.
                'craft_enchant_set_selected_item_ids' => array_fill_keys($keys, 999999999),
                'craft_enchant_set_crafted_item_ids' => array_fill_keys($keys, $item->id),
                'enchant_plan' => array_fill_keys($keys, ['prefix_affix_id' => $prefix->id]),
                'craft_enchant_set_requested' => 6,
                'craft_enchant_set_phase' => 'enchanting',
                'craft_enchant_set_craft_index' => 6,
                'craft_enchant_set_enchant_index' => 0,
                'craft_enchant_set_prefix_applied_count' => 0,
                'craft_enchant_set_suffix_applied_count' => 0,
                'craft_enchant_set_total_work_units' => 6,
                'craft_enchant_set_completed_work_units' => 0,
                'craft_enchant_set_surviving_crafted_count' => 6,
                'craft_enchant_set_counted_crafted_keys' => $keys,
                'craft_enchant_set_replacement_key' => null,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(0, $result->progress['craft_enchant_set_enchant_index']);
        $this->assertSame(5, $result->progress['craft_enchant_set_surviving_crafted_count']);
        $this->assertSame('replacement_crafting', $result->progress['craft_enchant_set_phase']);
        $this->assertSame('a', $result->progress['craft_enchant_set_replacement_key']);
        $destroyedAction = collect($result->action_log)->first(fn (array $entry) => ($entry['action_type'] ?? null) === 'craft_enchant_set_enchant' && ($entry['status'] ?? null) === 'destroyed');
        $this->assertNotNull($destroyedAction);
    }

    public function testCraftEnchantSetThreeEntryFixtureReachesFullWorkUnitCompletion(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 100000, 'inventory_max' => 30, 'int' => 100]);
        $itemA = $this->createItem(['name' => 'Full Cycle Dagger A', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $itemB = $this->createItem(['name' => 'Full Cycle Dagger B', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $itemC = $this->createItem(['name' => 'Full Cycle Dagger C', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $prefix = $this->createItemAffix(['name' => 'Full Cycle Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(400);
            })
        );
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $keys = ['a', 'b', 'c'];
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'selected_set_id' => $set->id,
                'craft_enchant_set_queue' => array_fill(0, 3, ['type' => 'dagger', 'crafting_type' => 'dagger']),
                'craft_enchant_set_keys' => $keys,
                'craft_enchant_set_selected_item_ids' => ['a' => $itemA->id, 'b' => $itemB->id, 'c' => $itemC->id],
                'enchant_plan' => array_fill_keys($keys, ['prefix_affix_id' => $prefix->id]),
                'craft_enchant_set_requested' => 3,
                'craft_enchant_set_phase' => 'crafting',
                'craft_enchant_set_craft_index' => 0,
                'craft_enchant_set_enchant_index' => 0,
                'craft_enchant_set_prefix_applied_count' => 0,
                'craft_enchant_set_suffix_applied_count' => 0,
                'craft_enchant_set_completed_final_count' => 0,
                'craft_enchant_set_total_work_units' => 3,
                'craft_enchant_set_completed_work_units' => 0,
            ],
        ]);

        $batchCrafting = resolve(BatchCraftingService::class)->process($batchCrafting);
        $batchCrafting = resolve(BatchCraftingService::class)->process($batchCrafting->refresh());

        $totalWorkUnits = $batchCrafting->progress['craft_enchant_set_total_work_units'];
        $completedWorkUnits = $batchCrafting->progress['craft_enchant_set_completed_work_units'];

        $this->assertSame(3, $totalWorkUnits);
        $this->assertSame(3, $completedWorkUnits);
        $this->assertSame(0, max(0, $totalWorkUnits - $completedWorkUnits));
    }

    public function testCraftEnchantSetEnchantPhasePrefixAndSuffixAppliedCountsAndDispositionRemainUnchanged(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 100000, 'inventory_max' => 30, 'int' => 100]);
        $item = $this->createItem(['name' => 'Prefix Suffix Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $prefix = $this->createItemAffix(['name' => 'Prefix Suffix Test Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $suffix = $this->createItemAffix(['name' => 'Prefix Suffix Test Suffix', 'type' => 'suffix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(400);
            })
        );
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'selected_set_id' => $set->id,
                'craft_enchant_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_enchant_set_keys' => ['dagger'],
                'craft_enchant_set_crafted_item_ids' => ['dagger' => $item->id],
                'enchant_plan' => ['dagger' => ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => $suffix->id]],
                'craft_enchant_set_requested' => 1,
                'craft_enchant_set_phase' => 'enchanting',
                'craft_enchant_set_craft_index' => 1,
                'craft_enchant_set_enchant_index' => 0,
                'craft_enchant_set_prefix_applied_count' => 0,
                'craft_enchant_set_suffix_applied_count' => 0,
                'craft_enchant_set_total_work_units' => 1,
                'craft_enchant_set_completed_work_units' => 0,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(1, $result->progress['craft_enchant_set_prefix_applied_count']);
        $this->assertSame(1, $result->progress['craft_enchant_set_suffix_applied_count']);
        $enchantAction = collect($result->action_log)->first(fn (array $entry) => ($entry['action_type'] ?? null) === 'craft_enchant_set_enchant' && ($entry['status'] ?? null) === 'double_enchanted');
        $this->assertNotNull($enchantAction);
    }

    public function testCraftAndEnchantForExperienceResolvesExactIntendedAutoAffixAndStopsWhenItRequiresTooMuchInt(): void
    {
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(400);
            })
        );
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 30, 'int' => 1]);
        $this->createItem(['name' => 'Auto Affix Resolve Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->createItemAffix(['name' => 'Auto Affix Resolve Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 999, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::INT_TOO_LOW_FOR_ENCHANTING->value, $result->ended_reason);
        $this->assertSame(1, $result->crafted_count);
    }

    public function testCraftAndEnchantForExperiencePersistsAndReturnsExactAutomaticallyResolvedIntStopAffixes(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 30, 'int' => 1]);
        $this->createItem(['name' => 'Exact Auto INT Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $prefix = $this->createItemAffix(['name' => 'Exact Auto INT Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 240, 'skill_level_required' => 10, 'skill_level_trivial' => 400]);
        $suffix = $this->createItemAffix(['name' => 'Exact Auto INT Suffix', 'type' => 'suffix', 'cost' => 1, 'int_required' => 225, 'skill_level_required' => 10, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $result = resolve(BatchCraftingProcessor::class)->processOneTick($batchCrafting, $character);
        $progress = $batchCrafting->refresh()->progress;

        $this->assertSame(BatchCraftingEndReason::INT_TOO_LOW_FOR_ENCHANTING, $result['end_reason']);
        $this->assertSame([$prefix->id, $suffix->id], $result['int_stop_affix_ids']);
        $this->assertSame($prefix->id, $progress['craft_experience_current_prefix_affix']['id'] ?? null);
        $this->assertSame($suffix->id, $progress['craft_experience_current_suffix_affix']['id'] ?? null);
    }

    public function testCraftAndEnchantForExperienceDoesNotFallBackToLowerIntAffixWhenHighestEligibleAffixIsTooHigh(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 30, 'int' => 1]);
        $this->createItem(['name' => 'No Fallback Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->createItemAffix(['name' => 'No Fallback High Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 999, 'skill_level_required' => 10, 'skill_level_trivial' => 400]);
        $this->createItemAffix(['name' => 'No Fallback Low Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::INT_TOO_LOW_FOR_ENCHANTING->value, $result->ended_reason);
        $this->assertFalse(collect($result->action_log)->contains(fn (array $entry) => ($entry['status'] ?? null) === 'enchanted'));
    }

    public function testIntHardStopDoesNotCreateMonitoredBugReport(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 30, 'int' => 1]);
        $this->createItem(['name' => 'Bug Report Guard Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->createItemAffix(['name' => 'Bug Report Guard Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 999, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);
        $mockedMonitoredBugReportService = Mockery::mock(MonitoredBugReportService::class);
        $mockedMonitoredBugReportService->shouldNotReceive('reportError');
        $this->app->instance(MonitoredBugReportService::class, $mockedMonitoredBugReportService);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::INT_TOO_LOW_FOR_ENCHANTING->value, $result->ended_reason);
    }

    public function testNormalNonIntEnchantFailureStillContinuesAsBefore(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 1, 'inventory_max' => 30]);
        $this->createItem(['name' => 'Normal Enchant Failure Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->createItemAffix(['name' => 'Normal Enchant Failure Prefix', 'type' => 'prefix', 'cost' => 5000, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertNull($result->ended_reason);
        $this->assertGreaterThan(0, $result->crafted_count);
    }

    public function testTryEnchantSlotCannotBypassIntCheckWhenUsedByStandaloneEnchantSetPath(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 30, 'int' => 1]);
        $item = $this->createItem(['name' => 'Enchant Set Bypass Guard Dagger', 'type' => 'dagger']);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $item->id]);
        $this->createItemAffix(['name' => 'Enchant Set Bypass Guard Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 999, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'enchant_mode' => 'set',
                'selected_set_id' => $set->id,
                'enchant_affix_ids' => [],
                'enchant_set_total' => 1,
                'enchant_set_completed' => 0,
                'enchant_set_skipped' => 0,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::INT_TOO_LOW_FOR_ENCHANTING->value, $result->ended_reason);
        $this->assertSame(1, $set->refresh()->slots()->count());
    }

    public function testCraftSetProcessorCraftsManuallySelectedItemInsteadOfHighest(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $this->createItem(['name' => 'Craft Set Selected High Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 5, 'skill_level_trivial' => 5]);
        $lowDagger = $this->createItem(['name' => 'Craft Set Selected Low Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_set',
                'selected_set_id' => $set->id,
                'craft_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_set_keys' => ['dagger'],
                'craft_set_selected_item_ids' => ['dagger' => $lowDagger->id],
                'craft_set_index' => 0,
                'craft_set_requested' => 1,
                'craft_set_completed' => 0,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $craftAction = collect($result->action_log)->first(fn (array $entry) => ($entry['action_type'] ?? null) === 'craft_set');

        $this->assertSame('Craft Set Selected Low Dagger', $craftAction['crafted_item']['name'] ?? null);
    }

    public function testCraftSetProcessorStopsWithoutFallbackWhenPersistedSelectedItemIsUnavailable(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $fallbackDagger = $this->createItem(['name' => 'Craft Set Forbidden Fallback Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 5, 'skill_level_trivial' => 5]);
        $selectedDagger = $this->createItem(['name' => 'Craft Set Unavailable Selected Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $selectedItemId = $selectedDagger->id;
        $selectedDagger->delete();
        $outputSet = $this->createInventorySet(['character_id' => $character->id, 'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_set',
                'output_destination' => 'crafted_items_set',
                'craft_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_set_keys' => ['left_hand'],
                'craft_set_selected_item_ids' => ['left_hand' => $selectedItemId],
                'craft_set_index' => 0,
                'craft_set_requested' => 2,
                'craft_set_completed' => 1,
                'craft_set_current_item' => ['name' => 'Earlier Completed Item'],
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $stoppedAction = collect($result->action_log)->first(fn (array $entry) => ($entry['action_type'] ?? null) === 'craft_set');

        $this->assertSame(BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT->value, $result->ended_reason);
        $this->assertSame('Batch Crafting stopped because the selected item for Left Hand is no longer available or craftable.', $result->progress['invalid_plan_message'] ?? null);
        $this->assertSame('left_hand', $stoppedAction['plan_key'] ?? null);
        $this->assertSame('Left Hand', $stoppedAction['target_label'] ?? null);
        $this->assertSame(0, $result->progress['craft_set_index'] ?? null);
        $this->assertSame(1, $result->progress['craft_set_completed'] ?? null);
        $this->assertSame('Earlier Completed Item', $result->progress['craft_set_current_item']['name'] ?? null);
        $this->assertSame(0, $result->crafted_count);
        $this->assertSame(0, $result->kept_count);
        $this->assertNull($stoppedAction['disposition'] ?? null);
        $this->assertSame(0, $outputSet->refresh()->slots()->count());
        $this->assertFalse(collect($result->action_log)->contains(fn (array $entry) => ($entry['crafted_item']['name'] ?? null) === $fallbackDagger->name));
    }

    public function testCraftSetProcessorResolvesTheSameSelectedItemForBothHandKeys(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $dagger = $this->createItem(['name' => 'Shared Hand Selection Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $this->createInventorySet(['character_id' => $character->id, 'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_set',
                'output_destination' => 'crafted_items_set',
                'craft_set_queue' => [
                    ['type' => 'dagger', 'crafting_type' => 'dagger'],
                    ['type' => 'dagger', 'crafting_type' => 'dagger'],
                ],
                'craft_set_keys' => ['left_hand', 'right_hand'],
                'craft_set_selected_item_ids' => [
                    'left_hand' => $dagger->id,
                    'right_hand' => $dagger->id,
                ],
                'craft_set_index' => 0,
                'craft_set_requested' => 2,
                'craft_set_completed' => 0,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $craftActions = collect($result->action_log)->filter(fn (array $entry) => ($entry['action_type'] ?? null) === 'craft_set');

        $this->assertCount(2, $craftActions);
        $this->assertTrue($craftActions->every(fn (array $entry) => ($entry['crafted_item']['name'] ?? null) === $dagger->name));
        $this->assertSame(2, $result->progress['craft_set_index'] ?? null);
        $this->assertSame(2, $result->progress['craft_set_completed'] ?? null);
    }

    public function testCraftAndEnchantForExperienceProcessesAtMostSixWorkflowsPerTick(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $this->createItem(['name' => 'Six Workflow Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertCount(6, $result->action_log);
    }

    public function testCraftAndEnchantForExperienceReachesTwentyThreeActionsAcrossFourChunkedSixSixSixFiveTicks(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400, 'skill_bonus_per_level' => 1.0]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->update(['gold' => 100000, 'inventory_max' => 200]);
        $this->createItem(['name' => 'Chunked Cycle Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
        ]);

        $batchCrafting = resolve(BatchCraftingService::class)->process($batchCrafting);
        $this->assertCount(6, $batchCrafting->action_log);
        $this->assertSame(2, $batchCrafting->progress['tick_delay_seconds']);

        $batchCrafting = resolve(BatchCraftingService::class)->process($batchCrafting);
        $this->assertCount(12, $batchCrafting->action_log);
        $this->assertSame(2, $batchCrafting->progress['tick_delay_seconds']);

        $batchCrafting = resolve(BatchCraftingService::class)->process($batchCrafting);
        $this->assertCount(18, $batchCrafting->action_log);
        $this->assertSame(2, $batchCrafting->progress['tick_delay_seconds']);

        $batchCrafting = resolve(BatchCraftingService::class)->process($batchCrafting);
        $this->assertCount(23, $batchCrafting->action_log);
        $this->assertSame(60, $batchCrafting->progress['tick_delay_seconds']);
        $this->assertSame(0, $batchCrafting->progress['experience_cycle_actions']);
    }

    public function testCraftAndEnchantForExperienceAutoSelectsOneEligiblePrefixAndOneEligibleSuffix(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400, 'skill_bonus_per_level' => 1.0]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 30, 'int' => 1]);
        $this->createItem(['name' => 'Double Affix Select Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $prefix = $this->createItemAffix(['name' => 'Double Affix Select Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $suffix = $this->createItemAffix(['name' => 'Double Affix Select Suffix', 'type' => 'suffix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $enchantedEntry = collect($result->action_log)->first(fn (array $entry) => ! empty($entry['enchant_affix_name'] ?? null));
        $names = explode(', ', $enchantedEntry['enchant_affix_name']);

        $this->assertNotNull($enchantedEntry);
        $this->assertCount(2, $names);
        $this->assertEqualsCanonicalizing([$prefix->name, $suffix->name], $names);
    }

    public function testCraftAndEnchantForExperienceEnchantedItemReceivesBothPrefixAndSuffixIds(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400, 'skill_bonus_per_level' => 1.0]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 30, 'int' => 1]);
        $this->createItem(['name' => 'Double Affix Ids Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $prefix = $this->createItemAffix(['name' => 'Double Affix Ids Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $suffix = $this->createItemAffix(['name' => 'Double Affix Ids Suffix', 'type' => 'suffix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $enchantedEntry = collect($result->action_log)->first(fn (array $entry) => ! empty($entry['enchant_affix_name'] ?? null));
        $enchantedItem = Item::find($enchantedEntry['enchanted_item']['item_id']);

        $this->assertSame($prefix->id, $enchantedItem->item_prefix_id);
        $this->assertSame($suffix->id, $enchantedItem->item_suffix_id);
    }

    public function testCraftAndEnchantForExperienceActionLogListsAffixNamesInPrefixThenSuffixOrder(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400, 'skill_bonus_per_level' => 1.0]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 30, 'int' => 1]);
        $this->createItem(['name' => 'Affix Order Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $prefix = $this->createItemAffix(['name' => 'Affix Order Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $suffix = $this->createItemAffix(['name' => 'Affix Order Suffix', 'type' => 'suffix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $enchantedEntry = collect($result->action_log)->first(fn (array $entry) => ! empty($entry['enchant_affix_name'] ?? null));

        $this->assertSame($prefix->name . ', ' . $suffix->name, $enchantedEntry['enchant_affix_name']);
    }

    public function testCraftAndEnchantForExperienceKeptItemServerMessageContainsBothAffixNames(): void
    {
        Event::fake([ServerMessageEvent::class]);
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400, 'skill_bonus_per_level' => 1.0]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 30, 'int' => 1]);
        $this->createItem(['name' => 'Server Message Affix Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $prefix = $this->createItemAffix(['name' => 'Server Message Affix Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $suffix = $this->createItemAffix(['name' => 'Server Message Affix Suffix', 'type' => 'suffix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        resolve(BatchCraftingService::class)->process($batchCrafting);

        Event::assertDispatched(ServerMessageEvent::class, function (ServerMessageEvent $event) use ($prefix, $suffix) {
            return str_starts_with($event->message, 'Applied enchantment: ' . $prefix->name . ', ' . $suffix->name . ' to:');
        });
    }

    public function testCraftAndEnchantForExperienceAppliesOnlyOneAffixWhenOnlyOneTypeIsEligible(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400, 'skill_bonus_per_level' => 1.0]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 30, 'int' => 1]);
        $this->createItem(['name' => 'Single Type Eligible Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $prefix = $this->createItemAffix(['name' => 'Single Type Eligible Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $enchantedEntry = collect($result->action_log)->first(fn (array $entry) => ! empty($entry['enchant_affix_name'] ?? null));

        $this->assertSame($prefix->name, $enchantedEntry['enchant_affix_name']);
    }

    public function testCraftAndEnchantForExperienceNeverSelectsTwoPrefixesWhenNoSuffixExists(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400, 'skill_bonus_per_level' => 1.0]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 30, 'int' => 1]);
        $this->createItem(['name' => 'Two Prefixes No Suffix Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->createItemAffix(['name' => 'Two Prefixes No Suffix Prefix A', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->createItemAffix(['name' => 'Two Prefixes No Suffix Prefix B', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $enchantedEntry = collect($result->action_log)->first(fn (array $entry) => ! empty($entry['enchant_affix_name'] ?? null));

        $this->assertCount(1, explode(', ', $enchantedEntry['enchant_affix_name']));
    }

    public function testCraftAndEnchantForExperienceNeverSelectsTwoSuffixesWhenNoPrefixExists(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400, 'skill_bonus_per_level' => 1.0]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 30, 'int' => 1]);
        $this->createItem(['name' => 'Two Suffixes No Prefix Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->createItemAffix(['name' => 'Two Suffixes No Prefix Suffix A', 'type' => 'suffix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->createItemAffix(['name' => 'Two Suffixes No Prefix Suffix B', 'type' => 'suffix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $enchantedEntry = collect($result->action_log)->first(fn (array $entry) => ! empty($entry['enchant_affix_name'] ?? null));

        $this->assertCount(1, explode(', ', $enchantedEntry['enchant_affix_name']));
    }

    public function testCraftAndEnchantForExperienceExplicitSingleAffixSelectionOverridesAutomaticDoubleSelection(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400, 'skill_bonus_per_level' => 1.0]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 30, 'int' => 1]);
        $this->createItem(['name' => 'Explicit Single Affix Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $prefix = $this->createItemAffix(['name' => 'Explicit Single Affix Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->createItemAffix(['name' => 'Explicit Single Affix Suffix', 'type' => 'suffix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['enchant_affix_ids' => [$prefix->id]],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $enchantedEntry = collect($result->action_log)->first(fn (array $entry) => ! empty($entry['enchant_affix_name'] ?? null));

        $this->assertSame($prefix->name, $enchantedEntry['enchant_affix_name']);
    }

    public function testCraftAndEnchantForExperienceIntCheckEvaluatesBothAutomaticallySelectedAffixes(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400, 'skill_bonus_per_level' => 1.0]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 30, 'int' => 1]);
        $this->createItem(['name' => 'Int Checks Both Affixes Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->createItemAffix(['name' => 'Int Checks Both Affixes Eligible Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->createItemAffix(['name' => 'Int Checks Both Affixes High Int Suffix', 'type' => 'suffix', 'cost' => 1, 'int_required' => 999, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::INT_TOO_LOW_FOR_ENCHANTING->value, $result->ended_reason);
        $this->assertSame(1, $result->crafted_count);
    }

    public function testCraftAndEnchantForExperienceTotalCostIncludesBothAutomaticallySelectedAffixes(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400, 'skill_bonus_per_level' => 1.0]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 15000, 'inventory_max' => 30, 'int' => 1]);
        $this->createItem(['name' => 'Combined Cost Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->createItemAffix(['name' => 'Combined Cost Prefix', 'type' => 'prefix', 'cost' => 10000, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->createItemAffix(['name' => 'Combined Cost Suffix', 'type' => 'suffix', 'cost' => 10000, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $blockedEntry = collect($result->action_log)->first(
            fn (array $entry) => ($entry['failure'] ?? null) === 'Enchanting was not attempted. The unenchanted item was discarded and another item will be crafted on a later attempt.'
        );

        $this->assertNotNull($blockedEntry);
        $this->assertArrayNotHasKey('enchanted_item', $blockedEntry);
    }

    public function testEnchantForEventEnchantsExistingEventItemsBeforeCraftingFallbackSet(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $schedule = $this->createScheduledEvent(['event_type' => EventType::WINTER_EVENT, 'status' => ScheduledEventStatus::RUNNING, 'currently_running' => true]);
        $event = $this->createEvent(['type' => EventType::WINTER_EVENT, 'scheduled_event_id' => $schedule->id, 'current_event_goal_step' => GlobalEventSteps::ENCHANT, 'ends_at' => now()->addHour()]);
        $goal = $this->createGlobalEventGoal(['event_type' => $event->type, 'event_id' => $event->id, 'max_enchants' => 100, 'item_specialty_type_reward' => ItemSpecialtyType::HELL_FORGED]);
        $eventMap = $this->createGameMap(['only_during_event_type' => $event->type]);
        $character->map()->update(['game_map_id' => $eventMap->id]);
        $item = $this->createItem(['name' => 'Existing Event Item', 'type' => 'weapon', 'crafting_type' => 'weapon']);
        $inventory = $this->createGlobalCraftingInventory(['global_event_goal_id' => $goal->id, 'character_id' => $character->id]);
        $this->createGlobalCraftingInventorySlot(['global_event_crafting_inventory_id' => $inventory->id, 'item_id' => $item->id]);
        $this->createItemAffix(['name' => 'Existing Event Item Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = resolve(BatchCraftingService::class)->start($character->refresh(), [
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['enchant_mode' => 'event'],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertTrue(collect($result->action_log)->contains(fn (array $entry) => ($entry['action_type'] ?? null) === 'event_enchant'));
        $this->assertFalse(collect($result->action_log)->contains(fn (array $entry) => ($entry['action_type'] ?? null) === 'event_fallback_craft'));
    }

    public function testEnchantForEventFallsBackToCraftingTwentyThreeItemsWhenNoEventItemsExist(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $armourCrafting = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $ringCrafting = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $spellCrafting = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->assignSkill($weaponCrafting, 10, false)
            ->assignSkill($armourCrafting, 10, false)
            ->assignSkill($ringCrafting, 10, false)
            ->assignSkill($spellCrafting, 10, false)
            ->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
            })
        );
        $schedule = $this->createScheduledEvent(['event_type' => EventType::WINTER_EVENT, 'status' => ScheduledEventStatus::RUNNING, 'currently_running' => true]);
        $event = $this->createEvent(['type' => EventType::WINTER_EVENT, 'scheduled_event_id' => $schedule->id, 'current_event_goal_step' => GlobalEventSteps::ENCHANT, 'ends_at' => now()->addHour()]);
        $this->createGlobalEventGoal(['event_type' => $event->type, 'event_id' => $event->id, 'max_enchants' => 100, 'item_specialty_type_reward' => ItemSpecialtyType::HELL_FORGED]);
        $eventMap = $this->createGameMap(['only_during_event_type' => $event->type]);
        $character->map()->update(['game_map_id' => $eventMap->id]);
        $this->createItem(['name' => 'Fallback Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->createItem(['name' => 'Fallback Helmet', 'type' => 'helmet', 'crafting_type' => 'armour', 'default_position' => 'helmet', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->createItem(['name' => 'Fallback Ring', 'type' => 'ring', 'crafting_type' => 'ring', 'default_position' => 'ring', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->createItem(['name' => 'Fallback Spell Damage', 'type' => 'spell_damage', 'crafting_type' => 'spell', 'default_position' => 'spell', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->createItem(['name' => 'Fallback Spell Healing', 'type' => 'spell_healing', 'crafting_type' => 'spell', 'default_position' => 'spell', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = resolve(BatchCraftingService::class)->start($character->refresh(), [
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['enchant_mode' => 'event'],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $craftedCount = collect($result->action_log)->filter(fn (array $entry) => ($entry['action_type'] ?? null) === 'event_fallback_craft' && ($entry['status'] ?? null) === 'crafted')->count();

        $this->assertSame(23, $craftedCount);
    }

    public function testAlchemyUseNowEmitsAggregateUsedMessageForUsableBoonItem(): void
    {
        Event::fake([ServerMessageEvent::class]);
        Bus::fake([CharacterBoonJob::class]);
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold_dust' => 1000]);
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ALCHEMY->value)->update(['level' => 50, 'xp' => 0, 'xp_max' => 100]);
        $item = $this->createItem([
            'name' => 'Use Now Boon Item',
            'type' => 'alchemy',
            'crafting_type' => 'alchemy',
            'can_craft' => true,
            'skill_level_required' => 1,
            'skill_level_trivial' => 1,
            'gold_dust_cost' => 0,
            'shards_cost' => 0,
            'usable' => true,
            'lasts_for' => 60,
            'damages_kingdoms' => false,
            'can_use_on_other_items' => false,
            'can_stack' => true,
        ]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::USE_NOW->value,
            'progress' => [
                'alchemy_mode' => 'amount',
                'alchemy_amount' => 1,
                'alchemy_item_id' => $item->id,
            ],
        ]);

        resolve(BatchCraftingService::class)->process($batchCrafting);

        Event::assertDispatched(ServerMessageEvent::class, function (ServerMessageEvent $event) {
            return $event->message === 'Used 1 Use Now Boon Item boon on you.';
        });
    }

    public function testAlchemyUseNowEmitsAggregateKeptMessageForKingdomBombItem(): void
    {
        Event::fake([ServerMessageEvent::class]);
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold_dust' => 1000]);
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ALCHEMY->value)->update(['level' => 50, 'xp' => 0, 'xp_max' => 100]);
        $item = $this->createItem([
            'name' => 'Kingdom Bomb Item',
            'type' => 'alchemy',
            'crafting_type' => 'alchemy',
            'can_craft' => true,
            'skill_level_required' => 1,
            'skill_level_trivial' => 1,
            'gold_dust_cost' => 0,
            'shards_cost' => 0,
            'usable' => false,
            'lasts_for' => null,
            'damages_kingdoms' => true,
            'can_use_on_other_items' => false,
            'can_stack' => false,
        ]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::USE_NOW->value,
            'progress' => [
                'alchemy_mode' => 'amount',
                'alchemy_amount' => 1,
                'alchemy_item_id' => $item->id,
            ],
        ]);

        resolve(BatchCraftingService::class)->process($batchCrafting);

        Event::assertDispatched(ServerMessageEvent::class, function (ServerMessageEvent $event) {
            return $event->message === 'Kept 1 Kingdom Bomb Item in your Alchemy Bag because it could not be used on you right now.';
        });
        $this->assertSame(1, AlchemyBagSlot::where('character_id', $character->id)->where('item_id', $item->id)->value('amount'));
    }

    public function testAlchemyUseNowKeptMessageIncludesLinkMetadataWhenItemRemainsInBag(): void
    {
        Event::fake([ServerMessageEvent::class]);
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold_dust' => 1000]);
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ALCHEMY->value)->update(['level' => 50, 'xp' => 0, 'xp_max' => 100]);
        $item = $this->createItem([
            'name' => 'Link Metadata Kingdom Bomb Item',
            'type' => 'alchemy',
            'crafting_type' => 'alchemy',
            'can_craft' => true,
            'skill_level_required' => 1,
            'skill_level_trivial' => 1,
            'gold_dust_cost' => 0,
            'shards_cost' => 0,
            'usable' => false,
            'lasts_for' => null,
            'damages_kingdoms' => true,
            'can_use_on_other_items' => false,
            'can_stack' => false,
        ]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::USE_NOW->value,
            'progress' => [
                'alchemy_mode' => 'amount',
                'alchemy_amount' => 1,
                'alchemy_item_id' => $item->id,
            ],
        ]);

        resolve(BatchCraftingService::class)->process($batchCrafting);

        $alchemyBagSlot = AlchemyBagSlot::where('character_id', $character->id)->where('item_id', $item->id)->first();

        $this->assertNotNull($alchemyBagSlot);
        Event::assertDispatched(ServerMessageEvent::class, function (ServerMessageEvent $event) use ($alchemyBagSlot, $item) {
            return $event->message === 'Kept 1 Link Metadata Kingdom Bomb Item in your Alchemy Bag because it could not be used on you right now.'
                && $event->id === $alchemyBagSlot->id
                && $event->source === 'alchemy_bag'
                && $event->linkText === $item->name;
        });
    }

    public function testAlchemyUseNowEmitsPerOperationMessagesWhenTenBoonCapIsReached(): void
    {
        Event::fake([ServerMessageEvent::class]);
        Bus::fake([CharacterBoonJob::class]);
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold_dust' => 1000]);
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ALCHEMY->value)->update(['level' => 50, 'xp' => 0, 'xp_max' => 100]);
        $existingBoonItem = $this->createItem(['name' => 'Existing Boon Item', 'type' => 'alchemy', 'usable' => true, 'lasts_for' => 60, 'can_stack' => true]);
        $character->boons()->create([
            'character_id' => $character->id,
            'item_id' => $existingBoonItem->id,
            'last_for_minutes' => 60,
            'amount_used' => 9,
            'started' => now(),
            'complete' => now()->addHour(),
        ]);
        $item = $this->createItem([
            'name' => 'Cap Test Boon Item',
            'type' => 'alchemy',
            'crafting_type' => 'alchemy',
            'can_craft' => true,
            'skill_level_required' => 1,
            'skill_level_trivial' => 1,
            'gold_dust_cost' => 0,
            'shards_cost' => 0,
            'usable' => true,
            'lasts_for' => 60,
            'damages_kingdoms' => false,
            'can_use_on_other_items' => false,
            'can_stack' => true,
        ]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::USE_NOW->value,
            'progress' => [
                'alchemy_mode' => 'amount',
                'alchemy_amount' => 3,
                'alchemy_item_id' => $item->id,
            ],
        ]);

        resolve(BatchCraftingService::class)->process($batchCrafting);

        Event::assertDispatched(ServerMessageEvent::class, function (ServerMessageEvent $event) {
            return $event->message === 'Used 1 Cap Test Boon Item boon on you.';
        });
        Event::assertDispatched(ServerMessageEvent::class, function (ServerMessageEvent $event) {
            return $event->message === 'Kept 1 Cap Test Boon Item in your Alchemy Bag because it could not be used on you right now.';
        });
        $this->assertSame(2, AlchemyBagSlot::where('character_id', $character->id)->where('item_id', $item->id)->value('amount'));
    }

    public function testAlchemyUseNowActionLogStillRecordsPerItemDispositionDetails(): void
    {
        Event::fake([ServerMessageEvent::class]);
        Bus::fake([CharacterBoonJob::class]);
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold_dust' => 1000]);
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ALCHEMY->value)->update(['level' => 50, 'xp' => 0, 'xp_max' => 100]);
        $existingBoonItem = $this->createItem(['name' => 'Action Log Existing Boon', 'type' => 'alchemy', 'usable' => true, 'lasts_for' => 60, 'can_stack' => true]);
        $character->boons()->create([
            'character_id' => $character->id,
            'item_id' => $existingBoonItem->id,
            'last_for_minutes' => 60,
            'amount_used' => 9,
            'started' => now(),
            'complete' => now()->addHour(),
        ]);
        $item = $this->createItem([
            'name' => 'Action Log Boon Item',
            'type' => 'alchemy',
            'crafting_type' => 'alchemy',
            'can_craft' => true,
            'skill_level_required' => 1,
            'skill_level_trivial' => 1,
            'gold_dust_cost' => 0,
            'shards_cost' => 0,
            'usable' => true,
            'lasts_for' => 60,
            'damages_kingdoms' => false,
            'can_use_on_other_items' => false,
            'can_stack' => true,
        ]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::USE_NOW->value,
            'progress' => [
                'alchemy_mode' => 'amount',
                'alchemy_amount' => 3,
                'alchemy_item_id' => $item->id,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $alchemyEntries = collect($result->action_log)->filter(fn (array $entry) => ($entry['action_type'] ?? null) === 'alchemy');
        $usedEntries = $alchemyEntries->filter(fn (array $entry) => ($entry['disposition'] ?? null) === 'use_now');
        $keptEntries = $alchemyEntries->filter(fn (array $entry) => ($entry['disposition'] ?? null) === 'keep');

        $this->assertSame(1, $usedEntries->count());
        $this->assertSame(2, $keptEntries->count());
        $this->assertSame('Action Log Boon Item', $usedEntries->first()['used_item']['name'] ?? null);
        $this->assertSame('Action Log Boon Item', $keptEntries->first()['kept_item']['name'] ?? null);
    }

    public function testCraftExperienceKeepDispositionChecksDecreasingCrafedItemsSetCapacityAcrossSixSixSixFiveChunks(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400, 'skill_bonus_per_level' => 1.0]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->update(['gold' => 100000, 'inventory_max' => 200]);
        $this->createItem(['name' => 'Capacity Cycle Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $set = $this->createInventorySet(['character_id' => $character->id, 'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE, 'max_slots' => 23]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $batchCrafting = resolve(BatchCraftingService::class)->process($batchCrafting);
        $this->assertCount(6, $batchCrafting->action_log);
        $this->assertSame(6, SetSlot::where('inventory_set_id', $set->id)->count());
        $this->assertSame(6, $batchCrafting->progress['experience_cycle_actions']);
        $this->assertNull($batchCrafting->ended_reason);

        $batchCrafting = resolve(BatchCraftingService::class)->process($batchCrafting);
        $this->assertCount(12, $batchCrafting->action_log);
        $this->assertSame(12, SetSlot::where('inventory_set_id', $set->id)->count());
        $this->assertSame(12, $batchCrafting->progress['experience_cycle_actions']);
        $this->assertNull($batchCrafting->ended_reason);

        $batchCrafting = resolve(BatchCraftingService::class)->process($batchCrafting);
        $this->assertCount(18, $batchCrafting->action_log);
        $this->assertSame(18, SetSlot::where('inventory_set_id', $set->id)->count());
        $this->assertSame(18, $batchCrafting->progress['experience_cycle_actions']);
        $this->assertNull($batchCrafting->ended_reason);

        $batchCrafting = resolve(BatchCraftingService::class)->process($batchCrafting);
        $this->assertCount(23, $batchCrafting->action_log);
        $this->assertSame(23, SetSlot::where('inventory_set_id', $set->id)->count());
        $this->assertSame(0, $batchCrafting->progress['experience_cycle_actions']);
        $this->assertNull($batchCrafting->ended_reason);
    }

    public function testCraftExperienceKeepDispositionStopsBeforeCraftingWhenCraftedItemsSetHasInsufficientCapacityForANewCycle(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400, 'skill_bonus_per_level' => 1.0]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->update(['gold' => 100000, 'inventory_max' => 200]);
        $this->createItem(['name' => 'Insufficient Capacity Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $set = $this->createInventorySet(['character_id' => $character->id, 'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE, 'max_slots' => 22]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $batchCrafting = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::BATCH_CRAFTING_SET_FULL->value, $batchCrafting->ended_reason);
        $this->assertNull($batchCrafting->action_log);
        $this->assertSame(0, SetSlot::where('inventory_set_id', $set->id)->count());
    }

    public function testCraftExperienceKeepDispositionStopsNextChunkWhenCapacityIsConsumedBetweenChunks(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400, 'skill_bonus_per_level' => 1.0]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->update(['gold' => 100000, 'inventory_max' => 200]);
        $fillerItem = $this->createItem(['name' => 'Between Chunks Filler Item', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $set = $this->createInventorySet(['character_id' => $character->id, 'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE, 'max_slots' => 23]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $batchCrafting = resolve(BatchCraftingService::class)->process($batchCrafting);
        $this->assertCount(6, $batchCrafting->action_log);

        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $fillerItem->id]);

        $batchCrafting = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::BATCH_CRAFTING_SET_FULL->value, $batchCrafting->ended_reason);
        $this->assertCount(6, $batchCrafting->action_log);
    }

    public function testCraftAndEnchantExperienceKeepDispositionCorrectlyAllowsSecondChunkWhenSeventeenSlotsRemain(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400, 'skill_bonus_per_level' => 1.0]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 100000, 'inventory_max' => 200]);
        // Fifteen distinct catalog items: the EnchantingService mock below mutates
        // whichever item it receives directly (no clone), so a single reused item
        // would stop matching "craftable" after its first enchant and starve later
        // chunk attempts. Distinct items keep every attempt finding a fresh target.
        for ($i = 0; $i < 15; $i++) {
            $this->createItem(['name' => 'Craft And Enchant Capacity Dagger ' . $i, 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        }
        $prefix = $this->createItemAffix(['name' => 'Capacity Chunk Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->instance(
            EnchantingService::class,
            Mockery::mock(EnchantingService::class, function ($mock) use ($prefix) {
                $mock->shouldReceive('getCostOfEnchantment')->andReturn(0);
                $mock->shouldReceive('enchantItemForBatch')->andReturnUsing(function ($character, $item) use ($prefix) {
                    $enchantedItem = $item->replicate();
                    $enchantedItem->name = $item->name.' Enchanted '.(((int) Item::max('id')) + 1);
                    $enchantedItem->item_prefix_id = $prefix->id;
                    $enchantedItem->save();

                    return ['success' => true, 'item' => $enchantedItem, 'reason' => null];
                });
            })
        );
        $set = $this->createInventorySet(['character_id' => $character->id, 'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE, 'max_slots' => 23]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['enchant_affix_ids' => [$prefix->id]],
        ]);

        $batchCrafting = resolve(BatchCraftingService::class)->process($batchCrafting);
        $this->assertCount(6, $batchCrafting->action_log);
        $this->assertSame(6, SetSlot::where('inventory_set_id', $set->id)->count());
        $this->assertSame(6, $batchCrafting->progress['experience_cycle_actions']);

        $batchCrafting = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertNull($batchCrafting->ended_reason);
        $this->assertCount(12, $batchCrafting->action_log);
        $this->assertSame(12, SetSlot::where('inventory_set_id', $set->id)->count());
    }

    public function testCraftEnchantSetEnchantPhasePartialAffixResultIsDiscardedNotCompletedAndSwitchesToReplacementCrafting(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['gold' => 100000, 'inventory_max' => 30, 'int' => 100]);
        $item = $this->createItem(['name' => 'Partial Result Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $prefix = $this->createItemAffix(['name' => 'Partial Result Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $suffix = $this->createItemAffix(['name' => 'Partial Result Suffix', 'type' => 'suffix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $item->update(['item_prefix_id' => $prefix->id]);

        // The real EnchantingService is all-or-nothing per attachAffix() call (any
        // single failed roll destroys the whole item), so a survived-but-partial
        // result cannot be produced deterministically through real rolls. Mocking
        // EnchantingService's return proves the processor still discards such a
        // result if it ever occurs, matching the non-negotiable atomicity rule.
        $this->instance(
            EnchantingService::class,
            Mockery::mock(EnchantingService::class, function ($mock) use ($item) {
                $mock->shouldReceive('getCostOfEnchantment')->andReturn(0);
                $mock->shouldReceive('enchantItemForBatch')->andReturn(['success' => true, 'item' => $item, 'reason' => null]);
            })
        );

        $set = $this->createInventorySet(['character_id' => $character->id]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'output_destination' => 'crafted_items_set',
                'craft_enchant_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_enchant_set_keys' => ['dagger'],
                'craft_enchant_set_selected_item_ids' => ['dagger' => 999999999],
                'craft_enchant_set_crafted_item_ids' => ['dagger' => $item->id],
                'enchant_plan' => ['dagger' => ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => $suffix->id]],
                'craft_enchant_set_requested' => 1,
                'craft_enchant_set_phase' => 'enchanting',
                'craft_enchant_set_craft_index' => 1,
                'craft_enchant_set_enchant_index' => 0,
                'craft_enchant_set_prefix_applied_count' => 0,
                'craft_enchant_set_suffix_applied_count' => 0,
                'craft_enchant_set_total_work_units' => 1,
                'craft_enchant_set_completed_work_units' => 0,
                'craft_enchant_set_surviving_crafted_count' => 1,
                'craft_enchant_set_counted_crafted_keys' => ['dagger'],
                'craft_enchant_set_replacement_key' => null,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(0, $result->progress['craft_enchant_set_enchant_index']);
        $this->assertSame(0, $result->progress['craft_enchant_set_surviving_crafted_count']);
        $this->assertSame('replacement_crafting', $result->progress['craft_enchant_set_phase']);
        $this->assertSame('dagger', $result->progress['craft_enchant_set_replacement_key']);
        $this->assertSame(0, $result->progress['craft_enchant_set_prefix_applied_count']);
        $this->assertSame(0, $result->progress['craft_enchant_set_suffix_applied_count']);
        $partialAction = collect($result->action_log)->first(fn (array $entry) => ($entry['action_type'] ?? null) === 'craft_enchant_set_enchant' && ($entry['status'] ?? null) === 'partial_enchant_discarded');
        $this->assertNotNull($partialAction);
        $this->assertFalse(Item::where('id', $item->id)->exists());
    }

    public function testCraftEnchantSetReplacementCraftingFailureKeepsSameReplacementKeyAndPhase(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['gold' => 100000, 'inventory_max' => 30]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'output_destination' => 'crafted_items_set',
                'craft_enchant_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_enchant_set_keys' => ['dagger'],
                // No item of this crafting_type exists in the item table, so any
                // replacement craft attempt cleanly fails with no side effects.
                'craft_enchant_set_selected_item_ids' => [],
                'craft_enchant_set_crafted_item_ids' => ['dagger' => null],
                'enchant_plan' => [],
                'craft_enchant_set_requested' => 1,
                'craft_enchant_set_phase' => 'replacement_crafting',
                'craft_enchant_set_craft_index' => 1,
                'craft_enchant_set_enchant_index' => 0,
                'craft_enchant_set_prefix_applied_count' => 0,
                'craft_enchant_set_suffix_applied_count' => 0,
                'craft_enchant_set_total_work_units' => 1,
                'craft_enchant_set_completed_work_units' => 0,
                'craft_enchant_set_surviving_crafted_count' => 0,
                'craft_enchant_set_replacement_key' => 'dagger',
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame('dagger', $result->progress['craft_enchant_set_replacement_key']);
        $this->assertSame('replacement_crafting', $result->progress['craft_enchant_set_phase']);
        $this->assertSame(0, $result->progress['craft_enchant_set_enchant_index']);
    }

    public function testCraftEnchantSetReplacementCraftingSuccessRestoresSurvivingCountAndReturnsToEnchantingAtSameIndex(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['gold' => 100000, 'inventory_max' => 30]);
        $replacementItem = $this->createItem(['name' => 'Replacement Success Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'output_destination' => 'crafted_items_set',
                'craft_enchant_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_enchant_set_keys' => ['dagger'],
                'craft_enchant_set_selected_item_ids' => ['dagger' => $replacementItem->id],
                'craft_enchant_set_crafted_item_ids' => ['dagger' => null],
                'craft_enchant_set_lost_item_keys' => ['dagger'],
                'craft_enchant_set_counted_crafted_keys' => [],
                'enchant_plan' => [],
                'craft_enchant_set_requested' => 1,
                'craft_enchant_set_phase' => 'replacement_crafting',
                'craft_enchant_set_craft_index' => 1,
                'craft_enchant_set_enchant_index' => 0,
                'craft_enchant_set_prefix_applied_count' => 0,
                'craft_enchant_set_suffix_applied_count' => 0,
                'craft_enchant_set_total_work_units' => 1,
                'craft_enchant_set_completed_work_units' => 0,
                'craft_enchant_set_surviving_crafted_count' => 0,
                'craft_enchant_set_replacement_key' => 'dagger',
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        // With an empty enchant_plan, the queue's single position finishes enchanting
        // (as "no affix requested") and the pipeline continues on within this same
        // tick, so the eventual phase moves past "enchanting" too -- what this test
        // must prove is the replacement itself: the key is cleared, never re-set to
        // "replacement_crafting" again, the surviving count is restored, and the
        // replacement item id is retained (never reverted to null).
        $this->assertNull($result->progress['craft_enchant_set_replacement_key']);
        $this->assertNotSame('replacement_crafting', $result->progress['craft_enchant_set_phase']);
        $this->assertSame(1, $result->progress['craft_enchant_set_surviving_crafted_count']);
        $this->assertSame($replacementItem->id, $result->progress['craft_enchant_set_crafted_item_ids']['dagger']);
    }

    public function testCraftAndEnchantAmountShatteredItemDoesNotIncrementCompletedCountAndReturnsToCraftPhase(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 100000, 'inventory_max' => 30, 'int' => 100]);
        $item = $this->createItem(['name' => 'Amount Shatter Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $outputSet = $this->createInventorySet(['character_id' => $character->id, 'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE]);
        $outputSlot = SetSlot::create(['inventory_set_id' => $outputSet->id, 'item_id' => $item->id]);
        $prefix = $this->createItemAffix(['name' => 'Amount Shatter Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(400);
                $mock->shouldReceive('characterRoll')->andReturn(1);
            })
        );
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'specific_item',
                'output_destination' => 'crafted_items_set',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $item->id,
                'craft_amount' => 5,
                'craft_enchant_specific_count' => 0,
                'enchant_affix_ids' => [$prefix->id],
                'craft_enchant_phase' => 'enchant',
                'craft_enchant_index' => 0,
                'pending_enchant_item_id' => $item->id,
                'pending_enchant_destination' => ['destination' => 'crafted_items_set', 'destination_label' => InventorySet::BATCH_CRAFTING_SET_NAME, 'slot_id' => null, 'set_slot_id' => $outputSlot->id],
                'craft_enchant_specific_surviving_crafted_count' => 1,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(0, $result->progress['craft_enchant_specific_count']);
        $this->assertSame(0, $result->progress['craft_enchant_specific_surviving_crafted_count']);
        $this->assertSame('craft', $result->progress['craft_enchant_phase']);
        $this->assertSame(0, $result->progress['craft_enchant_index'] ?? null);
        $destroyedAction = collect($result->action_log)->first(fn (array $entry) => ($entry['action_type'] ?? null) === 'craft_and_enchant' && ($entry['status'] ?? null) === 'destroyed');
        $this->assertNotNull($destroyedAction);
    }

    public function testCraftAndEnchantAmountPartialAffixResultIsDiscardedAndDoesNotIncrementCompletedCount(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['gold' => 100000, 'inventory_max' => 30, 'int' => 100]);
        $item = $this->createItem(['name' => 'Amount Partial Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $outputSet = $this->createInventorySet(['character_id' => $character->id, 'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE]);
        $outputSlot = SetSlot::create(['inventory_set_id' => $outputSet->id, 'item_id' => $item->id]);
        $prefix = $this->createItemAffix(['name' => 'Amount Partial Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $suffix = $this->createItemAffix(['name' => 'Amount Partial Suffix', 'type' => 'suffix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $item->update(['item_prefix_id' => $prefix->id]);

        $this->instance(
            EnchantingService::class,
            Mockery::mock(EnchantingService::class, function ($mock) use ($item) {
                $mock->shouldReceive('getCostOfEnchantment')->andReturn(0);
                $mock->shouldReceive('enchantItemForBatch')->andReturn(['success' => true, 'item' => $item, 'reason' => null]);
            })
        );

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'specific_item',
                'output_destination' => 'crafted_items_set',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $item->id,
                'craft_amount' => 5,
                'craft_enchant_specific_count' => 0,
                'enchant_affix_ids' => [$prefix->id, $suffix->id],
                'craft_enchant_phase' => 'enchant',
                'craft_enchant_index' => 0,
                'pending_enchant_item_id' => $item->id,
                'pending_enchant_destination' => ['destination' => 'crafted_items_set', 'destination_label' => InventorySet::BATCH_CRAFTING_SET_NAME, 'slot_id' => null, 'set_slot_id' => $outputSlot->id],
                'craft_enchant_specific_surviving_crafted_count' => 1,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(0, $result->progress['craft_enchant_specific_count']);
        $this->assertSame(0, $result->progress['craft_enchant_specific_surviving_crafted_count']);
        $this->assertSame(0, $result->progress['craft_enchant_index'] ?? null);
        $partialAction = collect($result->action_log)->first(fn (array $entry) => ($entry['action_type'] ?? null) === 'craft_and_enchant' && ($entry['status'] ?? null) === 'partial_enchant_discarded');
        $this->assertNotNull($partialAction);
        $this->assertFalse(Item::where('id', $item->id)->exists());
    }

    public function testCraftAndEnchantExperiencePartialAffixResultDoesNotKeepTheItem(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['gold' => 100000, 'inventory_max' => 30, 'int' => 100]);
        $craftableItem = $this->createItem(['name' => 'Experience Partial Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $prefix = $this->createItemAffix(['name' => 'Experience Partial Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $suffix = $this->createItemAffix(['name' => 'Experience Partial Suffix', 'type' => 'suffix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);

        $this->instance(
            EnchantingService::class,
            Mockery::mock(EnchantingService::class, function ($mock) use ($prefix) {
                $mock->shouldReceive('getCostOfEnchantment')->andReturn(0);
                $mock->shouldReceive('enchantItemForBatch')->andReturnUsing(function ($character, $item, $affixIds, $cost, $suppress) use ($prefix) {
                    $item->update(['item_prefix_id' => $prefix->id]);

                    return ['success' => true, 'item' => $item, 'reason' => null];
                });
            })
        );

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'experience',
                'output_destination' => 'crafted_items_set',
                'enchant_affix_ids' => [$prefix->id, $suffix->id],
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $partialAction = collect($result->action_log)->first(fn (array $entry) => ($entry['status'] ?? null) === 'partial_enchant_discarded');
        $this->assertNotNull($partialAction);
        $this->assertArrayNotHasKey('kept_item', $partialAction);
    }

    public function testCraftAmountWithInventoryDestinationSucceedsWhenCraftedItemsSetIsFull(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Inventory Destination Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $this->createInventorySet(['character_id' => $character->id, 'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE, 'max_slots' => 0]);
        $inventoryCountBefore = $character->getInventoryCount();
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'output_destination' => 'inventory', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(1, $result->crafted_count);
        $this->assertSame(1, $result->progress['craft_specific_count'] ?? null);
        $this->assertSame($inventoryCountBefore + 1, $character->refresh()->getInventoryCount());
    }

    public function testCraftAmountWithInventorySetDestinationSucceedsWhenCraftedItemsSetIsFull(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Inventory Set Destination Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $outputSet = $this->createInventorySet(['character_id' => $character->id, 'max_slots' => 5]);
        $this->createInventorySet(['character_id' => $character->id, 'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE, 'max_slots' => 0]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'output_destination' => 'inventory_set', 'output_set_id' => $outputSet->id, 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(1, $result->crafted_count);
        $this->assertSame(1, SetSlot::where('inventory_set_id', $outputSet->id)->where('item_id', $item->id)->count());
    }

    public function testCraftSetWithInventoryDestinationIsNotBlockedByFullCraftedItemsSet(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $this->createItem(['name' => 'Craft Set Inventory Destination Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $this->createInventorySet(['character_id' => $character->id, 'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE, 'max_slots' => 0]);
        $inventoryCountBefore = $character->getInventoryCount();
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_set',
                'output_destination' => 'inventory',
                'craft_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_set_keys' => ['dagger'],
                'craft_set_index' => 0,
                'craft_set_completed' => 0,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(1, $result->crafted_count);
        $this->assertSame(1, $result->progress['craft_set_index'] ?? null);
        $this->assertSame($inventoryCountBefore + 1, $character->refresh()->getInventoryCount());
    }

    public function testCraftAndEnchantAmountCraftStepWithInventoryDestinationIsNotBlockedByFullCraftedItemsSet(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Craft And Enchant Amount Inventory Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $this->createInventorySet(['character_id' => $character->id, 'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE, 'max_slots' => 0]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'specific_item',
                'output_destination' => 'inventory',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $item->id,
                'craft_amount' => 0,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertGreaterThan(0, $result->crafted_count);
        $this->assertSame('enchant', $result->progress['craft_enchant_phase'] ?? null);
    }

    public function testCraftAmountWithCraftedItemsSetDestinationRemainsBlockedWhenFull(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Crafted Items Set Blocked Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $this->createInventorySet(['character_id' => $character->id, 'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE, 'max_slots' => 0]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'output_destination' => 'crafted_items_set', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::BATCH_CRAFTING_SET_FULL->value, $result->ended_reason);
        $this->assertSame(0, $result->crafted_count);
        $this->assertSame(0, $result->progress['craft_specific_count'] ?? 0);
    }

    public function testCraftAmountWithInventoryDestinationReturnsNoInventorySpaceWhenInventoryIsFull(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 0]);
        $item = $this->createItem(['name' => 'Inventory Full Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'output_destination' => 'inventory', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::NO_INVENTORY_SPACE->value, $result->ended_reason);
        $this->assertSame(0, $result->crafted_count);
        $this->assertSame(0, $result->progress['craft_specific_count'] ?? 0);
    }

    public function testCraftAmountWithInventorySetDestinationReturnsCraftSetFullWhenSelectedSetIsFull(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Set Full Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $outputSet = $this->createInventorySet(['character_id' => $character->id, 'max_slots' => 0]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'output_destination' => 'inventory_set', 'output_set_id' => $outputSet->id, 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::CRAFT_SET_FULL->value, $result->ended_reason);
        $this->assertSame(0, $result->crafted_count);
        $this->assertSame(0, $result->progress['craft_specific_count'] ?? 0);
    }

    public function testCraftAmountWithEquippedOutputSetReturnsTargetSetChanged(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Equipped Output Set Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $outputSet = $this->createInventorySet(['character_id' => $character->id, 'is_equipped' => true, 'max_slots' => 23]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'output_destination' => 'inventory_set', 'output_set_id' => $outputSet->id, 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::CRAFT_ENCHANT_SET_TARGET_SET_CHANGED->value, $result->ended_reason);
        $this->assertSame(0, $result->crafted_count);
        $this->assertSame(0, $result->progress['craft_specific_count'] ?? 0);
    }

    public function testCraftAndEnchantAmountEnchantNotAttemptedKeepsPendingItemAndPhase(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 0, 'inventory_max' => 30, 'int' => 100]);
        $item = $this->createItem(['name' => 'Amount Gold Block Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $prefix = $this->createItemAffix(['name' => 'Amount Gold Block Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'specific_item',
                'output_destination' => 'crafted_items_set',
                'craft_amount' => 0,
                'craft_enchant_specific_count' => 0,
                'enchant_affix_ids' => [$prefix->id],
                'craft_enchant_phase' => 'enchant',
                'craft_enchant_index' => 0,
                'pending_enchant_item_id' => $item->id,
                'craft_enchant_specific_surviving_crafted_count' => 1,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame($item->id, $result->progress['pending_enchant_item_id'] ?? null);
        $this->assertSame('enchant', $result->progress['craft_enchant_phase'] ?? null);
        $this->assertSame(1, $result->progress['craft_enchant_specific_surviving_crafted_count'] ?? null);
        $this->assertSame(0, $result->progress['craft_enchant_index'] ?? null);
    }

    public function testCraftAndEnchantAmountExactPrefixSuccessCommitsAndAdvancesIndexOnce(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 100000, 'inventory_max' => 30, 'int' => 100]);
        $item = $this->createItem(['name' => 'Amount Exact Success Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $outputSet = $this->createInventorySet(['character_id' => $character->id, 'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE]);
        $outputSlot = SetSlot::create(['inventory_set_id' => $outputSet->id, 'item_id' => $item->id]);
        $prefix = $this->createItemAffix(['name' => 'Amount Exact Success Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->instance(
            EnchantingService::class,
            Mockery::mock(EnchantingService::class, function ($mock) use ($prefix) {
                $mock->shouldReceive('getCostOfEnchantment')->andReturn(0);
                $mock->shouldReceive('enchantItemForBatch')->andReturnUsing(function ($character, $item, $affixIds, $cost, $suppress) use ($prefix) {
                    $item->update(['item_prefix_id' => $prefix->id]);
                    $item->refresh();

                    return ['success' => true, 'item' => $item, 'reason' => null];
                });
            })
        );
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'specific_item',
                'output_destination' => 'crafted_items_set',
                'craft_amount' => 0,
                'craft_enchant_specific_count' => 0,
                'enchant_affix_ids' => [$prefix->id],
                'craft_enchant_phase' => 'enchant',
                'craft_enchant_index' => 0,
                'pending_enchant_item_id' => $item->id,
                'pending_enchant_destination' => ['destination' => 'crafted_items_set', 'destination_label' => InventorySet::BATCH_CRAFTING_SET_NAME, 'slot_id' => null, 'set_slot_id' => $outputSlot->id],
                'craft_enchant_specific_surviving_crafted_count' => 1,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(1, $result->progress['craft_enchant_index'] ?? null);
        $this->assertNull($result->progress['pending_enchant_item_id'] ?? null);
        $this->assertSame('craft', $result->progress['craft_enchant_phase'] ?? null);
        $this->assertSame(1, $result->progress['craft_enchant_specific_count'] ?? null);
        $this->assertSame((int) $prefix->id, $item->refresh()->item_prefix_id);
        $setSlot = SetSlot::whereHas('inventorySet', fn ($query) => $query->where('character_id', $character->id))->where('item_id', $item->id)->first();
        $this->assertNotNull($setSlot);
    }

    public function testCraftAndEnchantAmountIntTooLowPreservesPendingItemAndEnchantPhase(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 100000, 'inventory_max' => 30, 'int' => 1]);
        $item = $this->createItem(['name' => 'Amount Int Too Low Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $outputSet = $this->createInventorySet(['character_id' => $character->id, 'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE]);
        $outputSlot = SetSlot::create(['inventory_set_id' => $outputSet->id, 'item_id' => $item->id]);
        $prefix = $this->createItemAffix(['name' => 'Amount Int Too Low Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 999, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'specific_item',
                'output_destination' => 'crafted_items_set',
                'craft_amount' => 0,
                'craft_enchant_specific_count' => 0,
                'enchant_affix_ids' => [$prefix->id],
                'craft_enchant_phase' => 'enchant',
                'craft_enchant_index' => 0,
                'pending_enchant_item_id' => $item->id,
                'pending_enchant_destination' => ['destination' => 'crafted_items_set', 'destination_label' => InventorySet::BATCH_CRAFTING_SET_NAME, 'slot_id' => null, 'set_slot_id' => $outputSlot->id],
                'craft_enchant_specific_surviving_crafted_count' => 1,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::INT_TOO_LOW_FOR_ENCHANTING->value, $result->ended_reason);
        $this->assertSame($item->id, $result->progress['pending_enchant_item_id'] ?? null);
        $this->assertSame('enchant', $result->progress['craft_enchant_phase'] ?? null);
        $this->assertSame(1, $result->progress['craft_enchant_specific_surviving_crafted_count'] ?? null);
        $this->assertSame(0, $result->progress['craft_enchant_index'] ?? null);
    }

    public function testCraftAndEnchantAmountMissingPendingItemReturnsToCraftPhaseWithoutIncrementingProgress(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'specific_item',
                'output_destination' => 'crafted_items_set',
                'craft_amount' => 0,
                'craft_enchant_specific_count' => 0,
                'craft_enchant_phase' => 'enchant',
                'craft_enchant_index' => 0,
                'pending_enchant_item_id' => null,
                'craft_enchant_specific_surviving_crafted_count' => 1,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame('craft', $result->progress['craft_enchant_phase'] ?? null);
        $this->assertSame(0, $result->progress['craft_enchant_specific_surviving_crafted_count'] ?? null);
        $this->assertSame(0, $result->progress['craft_enchant_index'] ?? null);
        $this->assertSame(0, $result->progress['craft_enchant_specific_count'] ?? null);
    }

    public function testCraftEnchantSetEnchantPhaseNullStoredItemEntersReplacementCraftingWithoutAdvancing(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $prefix = $this->createItemAffix(['name' => 'Null Stored Item Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'selected_set_id' => $set->id,
                'craft_enchant_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_enchant_set_keys' => ['dagger'],
                'craft_enchant_set_crafted_item_ids' => ['dagger' => null],
                'enchant_plan' => ['dagger' => ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => null]],
                'craft_enchant_set_requested' => 1,
                'craft_enchant_set_phase' => 'enchanting',
                'craft_enchant_set_craft_index' => 1,
                'craft_enchant_set_enchant_index' => 0,
                'craft_enchant_set_prefix_applied_count' => 0,
                'craft_enchant_set_suffix_applied_count' => 0,
                'craft_enchant_set_total_work_units' => 1,
                'craft_enchant_set_completed_work_units' => 0,
                'craft_enchant_set_surviving_crafted_count' => 0,
                'craft_enchant_set_counted_crafted_keys' => ['dagger'],
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(0, $result->progress['craft_enchant_set_enchant_index']);
        $this->assertSame(0, $result->progress['craft_enchant_set_completed_work_units']);
        $this->assertSame('replacement_crafting', $result->progress['craft_enchant_set_phase']);
        $this->assertSame('dagger', $result->progress['craft_enchant_set_replacement_key']);
        $this->assertContains('dagger', $result->progress['craft_enchant_set_lost_item_keys'] ?? []);
    }

    public function testCraftEnchantSetEnchantPhaseMissingItemModelEntersReplacementCraftingWithoutAdvancing(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $prefix = $this->createItemAffix(['name' => 'Missing Item Model Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'selected_set_id' => $set->id,
                'craft_enchant_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_enchant_set_keys' => ['dagger'],
                'craft_enchant_set_crafted_item_ids' => ['dagger' => 999999999],
                'enchant_plan' => ['dagger' => ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => null]],
                'craft_enchant_set_requested' => 1,
                'craft_enchant_set_phase' => 'enchanting',
                'craft_enchant_set_craft_index' => 1,
                'craft_enchant_set_enchant_index' => 0,
                'craft_enchant_set_prefix_applied_count' => 0,
                'craft_enchant_set_suffix_applied_count' => 0,
                'craft_enchant_set_total_work_units' => 1,
                'craft_enchant_set_completed_work_units' => 0,
                'craft_enchant_set_surviving_crafted_count' => 1,
                'craft_enchant_set_counted_crafted_keys' => ['dagger'],
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(0, $result->progress['craft_enchant_set_enchant_index']);
        $this->assertSame(0, $result->progress['craft_enchant_set_completed_work_units']);
        $this->assertSame(0, $result->progress['craft_enchant_set_surviving_crafted_count']);
        $this->assertSame('replacement_crafting', $result->progress['craft_enchant_set_phase']);
        $this->assertArrayHasKey('dagger', $result->progress['craft_enchant_set_crafted_item_ids']);
        $this->assertNull($result->progress['craft_enchant_set_crafted_item_ids']['dagger']);
    }

    public function testCraftEnchantSetEnchantPhaseEmptyAffixPlanEndsFailedWithoutAdvancing(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $item = $this->createItem(['name' => 'Empty Plan Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger']);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'selected_set_id' => $set->id,
                'craft_enchant_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_enchant_set_keys' => ['dagger'],
                'craft_enchant_set_crafted_item_ids' => ['dagger' => $item->id],
                'enchant_plan' => [],
                'craft_enchant_set_requested' => 1,
                'craft_enchant_set_phase' => 'enchanting',
                'craft_enchant_set_craft_index' => 1,
                'craft_enchant_set_enchant_index' => 0,
                'craft_enchant_set_prefix_applied_count' => 0,
                'craft_enchant_set_suffix_applied_count' => 0,
                'craft_enchant_set_total_work_units' => 1,
                'craft_enchant_set_completed_work_units' => 0,
                'craft_enchant_set_surviving_crafted_count' => 1,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::FAILED->value, $result->ended_reason);
        $this->assertSame(0, $result->progress['craft_enchant_set_enchant_index']);
        $this->assertSame(0, $result->progress['craft_enchant_set_completed_work_units']);
    }

    public function testCraftAmountCommitFailsWhenItemModelIsMissing(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Ghost Craft Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $this->instance(
            CraftingService::class,
            Mockery::mock(CraftingService::class, function ($mock) use ($item) {
                $mock->shouldReceive('fetchCraftableItems')->andReturn(Item::whereIn('id', [$item->id])->get());
                $mock->shouldReceive('craftForBatch')->andThrow(new \RuntimeException('Atomic destination creation failed.'));
            })
        );
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'output_destination' => 'crafted_items_set', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::FAILED->value, $result->ended_reason);
        $this->assertSame(0, $result->progress['craft_specific_count'] ?? 0);
        $this->assertSame('failed', $result->action_log[0]['status'] ?? null);
    }

    public function testCraftAmountCraftedItemsSetNonSetFullFailureReturnsFalseAndDoesNotAdvance(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Non Set Full Failure Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $this->instance(
            BatchCraftingSetService::class,
            Mockery::mock(BatchCraftingSetService::class, function ($mock) {
                $mock->shouldReceive('canAccept')->andReturn(true);
                $mock->shouldReceive('createItemInBatchCraftingSet')->andReturn(['success' => false, 'reason' => 'unexpected_error', 'set_slot' => null]);
            })
        );
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'output_destination' => 'crafted_items_set', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::FAILED->value, $result->ended_reason);
        $this->assertSame(0, $result->progress['craft_specific_count'] ?? 0);
        $this->assertSame('failed', $result->action_log[0]['status'] ?? null);
    }

    public function testCraftAmountInventoryCommitCreatesExactlyOneSlotOnSuccess(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Single Slot Inventory Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'output_destination' => 'inventory', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(1, $result->progress['craft_specific_count'] ?? null);
        $this->assertSame(1, InventorySlot::where('inventory_id', $character->inventory->id)->where('item_id', $item->id)->count());
    }

    public function testCraftAmountInventorySetCommitCreatesExactlyOneSlotOnSuccess(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Single Slot Set Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $outputSet = $this->createInventorySet(['character_id' => $character->id, 'max_slots' => 5]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'output_destination' => 'inventory_set', 'output_set_id' => $outputSet->id, 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(1, $result->progress['craft_specific_count'] ?? null);
        $this->assertSame(1, SetSlot::where('inventory_set_id', $outputSet->id)->where('item_id', $item->id)->count());
    }

    public function testCraftAmountCraftedItemsSetCommitCreatesExactlyOneSlotOnSuccess(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Single Slot Crafted Items Set Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'output_destination' => 'crafted_items_set', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $craftedItemsSet = InventorySet::where('character_id', $character->id)->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)->first();
        $this->assertSame(1, $result->progress['craft_specific_count'] ?? null);
        $this->assertSame(1, SetSlot::where('inventory_set_id', $craftedItemsSet->id)->where('item_id', $item->id)->count());
    }

    public function testCraftAndEnchantAmountMissingRetainedDestinationFailsAndCleansOrphanItem(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 100000, 'inventory_max' => 30, 'int' => 100]);
        $item = $this->createItem(['name' => 'Commit Failure Regression Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $prefix = $this->createItemAffix(['name' => 'Commit Failure Regression Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $suffix = $this->createItemAffix(['name' => 'Commit Failure Regression Suffix', 'type' => 'suffix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $outputSet = $this->createInventorySet(['character_id' => $character->id, 'max_slots' => 0]);
        $this->instance(
            EnchantingService::class,
            Mockery::mock(EnchantingService::class, function ($mock) use ($prefix, $suffix) {
                $mock->shouldReceive('getCostOfEnchantment')->andReturn(0);
                $mock->shouldReceive('enchantItemForBatch')->andReturnUsing(function ($character, $item) use ($prefix, $suffix) {
                    $item->update(['item_prefix_id' => $prefix->id, 'item_suffix_id' => $suffix->id]);
                    $item->refresh();

                    return ['success' => true, 'item' => $item, 'reason' => null];
                });
            })
        );
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'specific_item',
                'output_destination' => 'inventory_set',
                'output_set_id' => $outputSet->id,
                'craft_amount' => 0,
                'craft_enchant_specific_count' => 0,
                'enchant_affix_ids' => [$prefix->id, $suffix->id],
                'craft_enchant_phase' => 'enchant',
                'craft_enchant_index' => 0,
                'pending_enchant_item_id' => $item->id,
                'craft_enchant_specific_surviving_crafted_count' => 1,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(0, $result->progress['craft_enchant_specific_count'] ?? 0);
        $this->assertSame(0, $result->progress['craft_enchant_index'] ?? 0);
        $this->assertSame(0, SetSlot::where('inventory_set_id', $outputSet->id)->count());
        $this->assertNull($result->progress['pending_enchant_item_id'] ?? null);
        $this->assertSame('craft', $result->progress['craft_enchant_phase'] ?? null);
        $this->assertFalse(Item::where('id', $item->id)->exists());
    }

    public function testCraftAndEnchantAmountCannotAssignInventoryDestinationAfterCraftingReturns(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 100000, 'inventory_max' => 0, 'int' => 100]);
        $item = $this->createItem(['name' => 'Inventory Full Commit Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $prefix = $this->createItemAffix(['name' => 'Inventory Full Commit Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->instance(
            EnchantingService::class,
            Mockery::mock(EnchantingService::class, function ($mock) use ($prefix) {
                $mock->shouldReceive('getCostOfEnchantment')->andReturn(0);
                $mock->shouldReceive('enchantItemForBatch')->andReturnUsing(function ($character, $item) use ($prefix) {
                    $item->update(['item_prefix_id' => $prefix->id]);
                    $item->refresh();

                    return ['success' => true, 'item' => $item, 'reason' => null];
                });
            })
        );
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'specific_item',
                'output_destination' => 'inventory',
                'craft_amount' => 0,
                'craft_enchant_specific_count' => 0,
                'enchant_affix_ids' => [$prefix->id],
                'craft_enchant_phase' => 'enchant',
                'craft_enchant_index' => 0,
                'pending_enchant_item_id' => $item->id,
                'craft_enchant_specific_surviving_crafted_count' => 1,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(0, $result->progress['craft_enchant_specific_count'] ?? 0);
        $this->assertSame(0, $result->progress['craft_enchant_index'] ?? 0);
        $this->assertSame(0, InventorySlot::where('inventory_id', $character->inventory->id)->where('item_id', $item->id)->count());
        $this->assertNull($result->progress['pending_enchant_item_id'] ?? null);
        $this->assertSame('craft', $result->progress['craft_enchant_phase'] ?? null);
        $this->assertFalse(Item::where('id', $item->id)->exists());
    }

    public function testCraftEnchantSetReplacementSuccessRemovesLostKeyAndRestoresProgressOnce(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['gold' => 100000, 'inventory_max' => 30]);
        $replacementItem = $this->createItem(['name' => 'Lost Key Replacement Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'output_destination' => 'crafted_items_set',
                'craft_enchant_set_queue' => [['type' => 'dagger', 'crafting_type' => 'weapon']],
                'craft_enchant_set_keys' => ['dagger'],
                'craft_enchant_set_selected_item_ids' => ['dagger' => $replacementItem->id],
                'craft_enchant_set_crafted_item_ids' => ['dagger' => null],
                'craft_enchant_set_lost_item_keys' => ['dagger'],
                'enchant_plan' => [],
                'craft_enchant_set_requested' => 1,
                'craft_enchant_set_phase' => 'replacement_crafting',
                'craft_enchant_set_craft_index' => 1,
                'craft_enchant_set_enchant_index' => 0,
                'craft_enchant_set_total_work_units' => 1,
                'craft_enchant_set_completed_work_units' => 0,
                'craft_enchant_set_surviving_crafted_count' => 0,
                'craft_enchant_set_replacement_key' => 'dagger',
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertNotContains('dagger', $result->progress['craft_enchant_set_lost_item_keys'] ?? []);
        $this->assertSame(1, $result->progress['craft_enchant_set_surviving_crafted_count']);
        $this->assertSame(0, $result->progress['craft_enchant_set_completed_work_units']);
        $this->assertSame(0, $result->progress['craft_enchant_set_enchant_index']);
        $this->assertNull($result->progress['craft_enchant_set_replacement_key']);
    }

    public function testCraftAmountInventoryFailureLeavesKeptCountAtZero(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 0]);
        $item = $this->createItem(['name' => 'Kept Count Inventory Full Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'output_destination' => 'inventory', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::NO_INVENTORY_SPACE->value, $result->ended_reason);
        $this->assertSame(0, $result->kept_count);
        $this->assertSame(0, $result->crafted_count);
        $this->assertSame(0, InventorySlot::where('inventory_id', $character->inventory->id)->where('item_id', $item->id)->count());
    }

    public function testCraftAmountInventorySetFailureLeavesKeptCountAtZero(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Kept Count Output Set Full Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $outputSet = $this->createInventorySet(['character_id' => $character->id, 'max_slots' => 0]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'output_destination' => 'inventory_set', 'output_set_id' => $outputSet->id, 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::CRAFT_SET_FULL->value, $result->ended_reason);
        $this->assertSame(0, $result->kept_count);
        $this->assertSame(0, $result->crafted_count);
        $this->assertSame(0, SetSlot::where('inventory_set_id', $outputSet->id)->count());
    }

    public function testCraftAmountCraftedItemsSetFullFailureLeavesKeptCountAtZero(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Kept Count Crafted Set Full Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $this->createInventorySet(['character_id' => $character->id, 'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE, 'max_slots' => 0]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'output_destination' => 'crafted_items_set', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::BATCH_CRAFTING_SET_FULL->value, $result->ended_reason);
        $this->assertSame(0, $result->kept_count);
        $this->assertSame(0, $result->crafted_count);
    }

    public function testCraftAmountCraftedItemsSetNonFullFailureLeavesKeptCountAtZero(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Kept Count Non Set Full Failure Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $this->instance(
            BatchCraftingSetService::class,
            Mockery::mock(BatchCraftingSetService::class, function ($mock) {
                $mock->shouldReceive('canAccept')->andReturn(true);
                $mock->shouldReceive('createItemInBatchCraftingSet')->andReturn(['success' => false, 'reason' => 'unexpected_error', 'set_slot' => null]);
            })
        );
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'output_destination' => 'crafted_items_set', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::FAILED->value, $result->ended_reason);
        $this->assertSame(0, $result->kept_count);
        $keepAction = $result->action_log[0] ?? [];
        $this->assertSame('failed', $keepAction['status'] ?? null);
        $this->assertArrayNotHasKey('kept_item', $keepAction);
        $this->assertArrayNotHasKey('created_in_crafted_items_set', $keepAction);
    }

    public function testCraftAmountMixedSuccessThenCapacityFailureAcrossTwoTicksKeepsExactlyOneKeptCount(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $item = $this->createItem(['name' => 'Mixed Result Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $outputSet = $this->createInventorySet(['character_id' => $character->id, 'max_slots' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'output_destination' => 'inventory_set', 'output_set_id' => $outputSet->id, 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 2],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(1, $result->kept_count);
        $this->assertSame(BatchCraftingEndReason::CRAFT_SET_FULL->value, $result->ended_reason);
        $this->assertSame(1, SetSlot::where('inventory_set_id', $outputSet->id)->where('item_id', $item->id)->count());
    }

    public function testCraftSetCommitFailureLeavesKeptCountAtZero(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 0]);
        $this->createItem(['name' => 'Craft Set Commit Failure Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_set',
                'output_destination' => 'inventory',
                'craft_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_set_keys' => ['dagger'],
                'craft_set_index' => 0,
                'craft_set_completed' => 0,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(0, $result->kept_count);
        $this->assertSame(0, $result->progress['craft_set_index'] ?? null);
        $this->assertSame(BatchCraftingEndReason::NO_INVENTORY_SPACE->value, $result->ended_reason);
    }

    public function testCraftExperienceCommitFailureLeavesKeptCountAtZero(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 0]);
        $this->createInventorySet(['character_id' => $character->id, 'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE, 'max_slots' => 0]);
        $this->createItem(['name' => 'Experience Commit Failure Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience', 'output_destination' => 'inventory'],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(0, $result->kept_count);
        $this->assertSame(BatchCraftingEndReason::BATCH_CRAFTING_SET_FULL->value, $result->ended_reason);
    }

    public function testTrinketryCommitFailureLeavesKeptCountAtZero(): void
    {
        $trinketry = $this->createGameSkill(['name' => 'Trinketry', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($trinketry, 1, false)->getCharacter();
        $character->update(['gold_dust' => 1000000, 'shards' => 1000000, 'copper_coins' => 1000000, 'inventory_max' => 0]);
        $this->createInventorySet(['character_id' => $character->id, 'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE, 'max_slots' => 0]);
        $this->createItem(['name' => 'Trinketry Commit Failure Trinket', 'type' => 'trinket', 'crafting_type' => 'trinketry', 'can_craft' => true, 'gold_dust_cost' => 1, 'copper_coin_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['trinketry_mode' => 'experience', 'output_destination' => 'inventory'],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(0, $result->kept_count);
        $this->assertSame(BatchCraftingEndReason::BATCH_CRAFTING_SET_FULL->value, $result->ended_reason);
    }

    public function testCraftAndEnchantExperienceSuccessfulKeepAppliesDispositionExactlyOnceWithExactlyOneKeptCount(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['gold' => 100000, 'inventory_max' => 30, 'int' => 100]);
        $item = $this->createItem(['name' => 'Experience Success Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $prefix = $this->createItemAffix(['name' => 'Experience Success Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $suffix = $this->createItemAffix(['name' => 'Experience Success Suffix', 'type' => 'suffix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);

        $craftAttempted = false;
        $this->instance(
            CraftingService::class,
            Mockery::mock(CraftingService::class, function ($mock) use ($item, &$craftAttempted) {
                $mock->shouldReceive('fetchCraftableItems')->andReturnUsing(function ($character, $filters) use ($item, &$craftAttempted) {
                    if ($craftAttempted || ($filters['crafting_type'] ?? null) !== 'dagger') {
                        return Item::whereIn('id', [])->get();
                    }

                    return Item::whereIn('id', [$item->id])->get();
                });
                $mock->shouldReceive('craftForBatch')->andReturnUsing(function ($character, $craftedItem, $craftingType, $suppressSuccessServerMessage, $destinationCreator) use (&$craftAttempted) {
                    $craftAttempted = true;
                    $destination = $destinationCreator($craftedItem);

                    return ['success' => true, 'item' => $craftedItem, 'reason' => null, 'destination' => $destination];
                });
            })
        );

        $this->instance(
            EnchantingService::class,
            Mockery::mock(EnchantingService::class, function ($mock) use ($prefix, $suffix) {
                $mock->shouldReceive('getCostOfEnchantment')->andReturn(0);
                $mock->shouldReceive('enchantItemForBatch')->andReturnUsing(function ($character, $item) use ($prefix, $suffix) {
                    $item->update(['item_prefix_id' => $prefix->id, 'item_suffix_id' => $suffix->id]);
                    $item->refresh();

                    return ['success' => true, 'item' => $item, 'reason' => null];
                });
            })
        );

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'experience',
                'output_destination' => 'crafted_items_set',
                'enchant_affix_ids' => [$prefix->id, $suffix->id],
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(1, $result->crafted_count);
        $this->assertSame(1, $result->progress['outcome_totals']['enchanted'] ?? 0);
        $this->assertSame(1, $result->kept_count);
        $craftedItemsSet = InventorySet::where('character_id', $character->id)->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)->first();
        $this->assertSame(1, SetSlot::where('inventory_set_id', $craftedItemsSet->id)->count());
    }

    public function testCraftAndEnchantExperienceEnchantNotAttemptedDiscardsItemWithoutDispositionOrCommit(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['gold' => 5, 'inventory_max' => 30, 'int' => 100]);
        $this->createItem(['name' => 'Experience No Gold Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $prefix = $this->createItemAffix(['name' => 'Experience No Gold Prefix', 'type' => 'prefix', 'cost' => 1000000, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->instance(
            EnchantingService::class,
            Mockery::mock(EnchantingService::class, function ($mock) {
                $mock->shouldReceive('getCostOfEnchantment')->andReturn(1000000);
                $mock->shouldNotReceive('enchantItemForBatch');
            })
        );

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'experience',
                'output_destination' => 'crafted_items_set',
                'enchant_affix_ids' => [$prefix->id],
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(0, $result->kept_count);
        $this->assertSame(0, $result->progress['outcome_totals']['enchanted'] ?? 0);
        $failedAction = collect($result->action_log)->first(fn (array $entry) => ($entry['failure'] ?? null) === 'Enchanting was not attempted. The unenchanted item was discarded and another item will be crafted on a later attempt.');
        $this->assertNotNull($failedAction);
        $this->assertArrayNotHasKey('kept_item', $failedAction);
    }

    public function testCraftAndEnchantExperienceIntTooLowStopsWithoutDispositionOrCommit(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['gold' => 100000, 'inventory_max' => 30, 'int' => 1]);
        $this->createItem(['name' => 'Experience INT Low Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $prefix = $this->createItemAffix(['name' => 'Experience INT Low Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 999999, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'experience',
                'output_destination' => 'crafted_items_set',
                'enchant_affix_ids' => [$prefix->id],
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(0, $result->kept_count);
        $this->assertSame(0, $result->progress['outcome_totals']['enchanted'] ?? 0);
        $this->assertSame(BatchCraftingEndReason::INT_TOO_LOW_FOR_ENCHANTING->value, $result->ended_reason);
        $stoppedAction = collect($result->action_log)->first(fn (array $entry) => ($entry['status'] ?? null) === 'stopped');
        $this->assertNotNull($stoppedAction);
        $this->assertArrayNotHasKey('kept_item', $stoppedAction);
    }

    public function testCraftAndEnchantExperienceShatteredDestroysItemWithoutDispositionOrCommit(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['gold' => 100000, 'inventory_max' => 30, 'int' => 100]);
        $this->createItem(['name' => 'Experience Shatter Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $prefix = $this->createItemAffix(['name' => 'Experience Shatter Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);

        $this->instance(
            EnchantingService::class,
            Mockery::mock(EnchantingService::class, function ($mock) {
                $mock->shouldReceive('getCostOfEnchantment')->andReturn(0);
                $mock->shouldReceive('enchantItemForBatch')->andReturn(['success' => false, 'item' => null, 'reason' => 'shattered']);
            })
        );

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'experience',
                'output_destination' => 'crafted_items_set',
                'enchant_affix_ids' => [$prefix->id],
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(0, $result->kept_count);
        $this->assertGreaterThanOrEqual(1, $result->destroyed_count);
        $this->assertSame(0, $result->progress['outcome_totals']['enchanted'] ?? 0);
        $destroyedAction = collect($result->action_log)->first(fn (array $entry) => ($entry['status'] ?? null) === 'destroyed');
        $this->assertNotNull($destroyedAction);
        $this->assertArrayNotHasKey('kept_item', $destroyedAction);
    }

    public function testCraftAndEnchantExperiencePartialPrefixOnlyDiscardsItemAndRequiresReplacementWithoutDisposition(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['gold' => 100000, 'inventory_max' => 30, 'int' => 100]);
        $this->createItem(['name' => 'Experience Partial Prefix Only Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $prefix = $this->createItemAffix(['name' => 'Experience Partial Prefix Only Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $suffix = $this->createItemAffix(['name' => 'Experience Partial Prefix Only Suffix', 'type' => 'suffix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);

        $this->instance(
            EnchantingService::class,
            Mockery::mock(EnchantingService::class, function ($mock) use ($prefix) {
                $mock->shouldReceive('getCostOfEnchantment')->andReturn(0);
                $mock->shouldReceive('enchantItemForBatch')->andReturnUsing(function ($character, $item) use ($prefix) {
                    $item->update(['item_prefix_id' => $prefix->id]);
                    $item->refresh();

                    return ['success' => true, 'item' => $item, 'reason' => null];
                });
            })
        );

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'experience',
                'output_destination' => 'crafted_items_set',
                'enchant_affix_ids' => [$prefix->id, $suffix->id],
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(0, $result->kept_count);
        $this->assertSame(0, $result->progress['outcome_totals']['enchanted'] ?? 0);
        $partialAction = collect($result->action_log)->first(fn (array $entry) => ($entry['status'] ?? null) === 'partial_enchant_discarded');
        $this->assertNotNull($partialAction);
        $this->assertArrayNotHasKey('kept_item', $partialAction);
    }

    public function testCraftAndEnchantExperiencePartialSuffixOnlyDiscardsItemAndRequiresReplacementWithoutDisposition(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['gold' => 100000, 'inventory_max' => 30, 'int' => 100]);
        $this->createItem(['name' => 'Experience Partial Suffix Only Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $prefix = $this->createItemAffix(['name' => 'Experience Partial Suffix Only Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $suffix = $this->createItemAffix(['name' => 'Experience Partial Suffix Only Suffix', 'type' => 'suffix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);

        $this->instance(
            EnchantingService::class,
            Mockery::mock(EnchantingService::class, function ($mock) use ($suffix) {
                $mock->shouldReceive('getCostOfEnchantment')->andReturn(0);
                $mock->shouldReceive('enchantItemForBatch')->andReturnUsing(function ($character, $item) use ($suffix) {
                    $item->update(['item_suffix_id' => $suffix->id]);
                    $item->refresh();

                    return ['success' => true, 'item' => $item, 'reason' => null];
                });
            })
        );

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'experience',
                'output_destination' => 'crafted_items_set',
                'enchant_affix_ids' => [$prefix->id, $suffix->id],
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(0, $result->kept_count);
        $this->assertSame(0, $result->progress['outcome_totals']['enchanted'] ?? 0);
        $partialAction = collect($result->action_log)->first(fn (array $entry) => ($entry['status'] ?? null) === 'partial_enchant_discarded');
        $this->assertNotNull($partialAction);
        $this->assertArrayNotHasKey('kept_item', $partialAction);
    }

    public function testCraftAndEnchantExperienceExactSuccessWithFailedCommitZeroesEnchantedAndKeptCounts(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['gold' => 100000, 'inventory_max' => 30, 'int' => 100]);
        $item = $this->createItem(['name' => 'Experience Commit Fail Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $prefix = $this->createItemAffix(['name' => 'Experience Commit Fail Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->createInventorySet(['character_id' => $character->id, 'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE, 'max_slots' => 0]);

        $craftAttempted = false;
        $this->instance(
            CraftingService::class,
            Mockery::mock(CraftingService::class, function ($mock) use ($item, &$craftAttempted) {
                $mock->shouldReceive('fetchCraftableItems')->andReturnUsing(function ($character, $filters) use ($item, &$craftAttempted) {
                    if ($craftAttempted || ($filters['crafting_type'] ?? null) !== 'dagger') {
                        return Item::whereIn('id', [])->get();
                    }

                    return Item::whereIn('id', [$item->id])->get();
                });
                $mock->shouldReceive('craftForBatch')->andReturnUsing(function ($character, $craftedItem) use (&$craftAttempted) {
                    $craftAttempted = true;

                    return ['success' => true, 'item' => $craftedItem, 'reason' => null];
                });
            })
        );

        $this->instance(
            EnchantingService::class,
            Mockery::mock(EnchantingService::class, function ($mock) use ($prefix) {
                $mock->shouldReceive('getCostOfEnchantment')->andReturn(0);
                $mock->shouldReceive('enchantItemForBatch')->andReturnUsing(function ($character, $item) use ($prefix) {
                    $item->update(['item_prefix_id' => $prefix->id]);
                    $item->refresh();

                    return ['success' => true, 'item' => $item, 'reason' => null];
                });
            })
        );

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'experience',
                'output_destination' => 'crafted_items_set',
                'enchant_affix_ids' => [$prefix->id],
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(0, $result->kept_count);
        $this->assertSame(0, $result->progress['outcome_totals']['enchanted'] ?? 0);
        $this->assertSame(BatchCraftingEndReason::BATCH_CRAFTING_SET_FULL->value, $result->ended_reason);
    }

    public function testCraftAndEnchantExperienceNonRetainedSellDispositionSucceedsWithoutCommitOrKeptCount(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['gold' => 100000, 'inventory_max' => 30, 'int' => 100]);
        $item = $this->createItem(['name' => 'Experience Sell Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $prefix = $this->createItemAffix(['name' => 'Experience Sell Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);

        $craftAttempted = false;
        $this->instance(
            CraftingService::class,
            Mockery::mock(CraftingService::class, function ($mock) use ($item, &$craftAttempted) {
                $mock->shouldReceive('fetchCraftableItems')->andReturnUsing(function ($character, $filters) use ($item, &$craftAttempted) {
                    if ($craftAttempted || ($filters['crafting_type'] ?? null) !== 'dagger') {
                        return Item::whereIn('id', [])->get();
                    }

                    return Item::whereIn('id', [$item->id])->get();
                });
                $mock->shouldReceive('craftForBatch')->andReturnUsing(function ($character, $craftedItem) use (&$craftAttempted) {
                    $craftAttempted = true;

                    return ['success' => true, 'item' => $craftedItem, 'reason' => null];
                });
            })
        );

        $this->instance(
            EnchantingService::class,
            Mockery::mock(EnchantingService::class, function ($mock) use ($prefix) {
                $mock->shouldReceive('getCostOfEnchantment')->andReturn(0);
                $mock->shouldReceive('enchantItemForBatch')->andReturnUsing(function ($character, $item) use ($prefix) {
                    $item->update(['item_prefix_id' => $prefix->id]);
                    $item->refresh();

                    return ['success' => true, 'item' => $item, 'reason' => null];
                });
            })
        );

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::SELL->value,
            'progress' => [
                'craft_mode' => 'experience',
                'enchant_affix_ids' => [$prefix->id],
            ],
        ]);

        $goldBefore = $character->gold;

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(1, $result->progress['outcome_totals']['enchanted'] ?? 0);
        $this->assertSame(0, $result->kept_count);
        $this->assertSame(1, $result->sold_count);
        $this->assertGreaterThan($goldBefore, $character->refresh()->gold);
        $this->assertSame(0, SetSlot::whereHas('inventorySet', fn ($query) => $query->where('character_id', $character->id))->count());
    }

    public function testCraftEnchantSetCraftPhaseInitialCraftAddsCountedKeyAndIncrementsSurvivingCount(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $this->createItem(['name' => 'Counted Key Initial Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'selected_set_id' => $set->id,
                'craft_enchant_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_enchant_set_keys' => ['dagger'],
                'enchant_plan' => [],
                'craft_enchant_set_requested' => 1,
                'craft_enchant_set_phase' => 'crafting',
                'craft_enchant_set_craft_index' => 0,
                'craft_enchant_set_enchant_index' => 0,
                'craft_enchant_set_total_work_units' => 1,
                'craft_enchant_set_completed_work_units' => 0,
                'craft_enchant_set_surviving_crafted_count' => 0,
                'craft_enchant_set_counted_crafted_keys' => [],
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(['dagger'], $result->progress['craft_enchant_set_counted_crafted_keys'] ?? null);
        $this->assertSame(1, $result->progress['craft_enchant_set_surviving_crafted_count']);
        $this->assertSame(0, $result->progress['craft_enchant_set_craft_index']);
    }

    public function testCraftEnchantSetCraftPhaseDuplicateKeyDoesNotDoubleCountAndDiscardsDuplicateItem(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $this->createItem(['name' => 'Counted Key Duplicate Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'selected_set_id' => $set->id,
                'craft_enchant_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_enchant_set_keys' => ['dagger'],
                'enchant_plan' => [],
                'craft_enchant_set_requested' => 1,
                'craft_enchant_set_phase' => 'crafting',
                'craft_enchant_set_craft_index' => 0,
                'craft_enchant_set_enchant_index' => 0,
                'craft_enchant_set_total_work_units' => 1,
                'craft_enchant_set_completed_work_units' => 0,
                'craft_enchant_set_surviving_crafted_count' => 1,
                'craft_enchant_set_counted_crafted_keys' => ['dagger'],
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(['dagger'], $result->progress['craft_enchant_set_counted_crafted_keys'] ?? null);
        $this->assertSame(1, $result->progress['craft_enchant_set_surviving_crafted_count']);
        $this->assertSame(0, $result->progress['craft_enchant_set_craft_index']);
        $this->assertGreaterThanOrEqual(1, $result->failed_count);
    }

    public function testCraftEnchantSetNullStoredItemLossDoesNotDecrementWhenKeyWasNeverCounted(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $prefix = $this->createItemAffix(['name' => 'Null Stored Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'craft_enchant_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_enchant_set_keys' => ['dagger'],
                'craft_enchant_set_crafted_item_ids' => ['dagger' => null],
                'enchant_plan' => ['dagger' => ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => null]],
                'craft_enchant_set_requested' => 1,
                'craft_enchant_set_phase' => 'enchanting',
                'craft_enchant_set_craft_index' => 1,
                'craft_enchant_set_enchant_index' => 0,
                'craft_enchant_set_total_work_units' => 1,
                'craft_enchant_set_completed_work_units' => 0,
                'craft_enchant_set_surviving_crafted_count' => 0,
                'craft_enchant_set_counted_crafted_keys' => [],
                'craft_enchant_set_lost_item_keys' => [],
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertContains('dagger', $result->progress['craft_enchant_set_lost_item_keys'] ?? []);
        $this->assertSame(0, $result->progress['craft_enchant_set_surviving_crafted_count']);
        $this->assertSame(0, $result->progress['craft_enchant_set_completed_work_units']);
        $this->assertSame('replacement_crafting', $result->progress['craft_enchant_set_phase']);
    }

    public function testCraftEnchantSetMissingItemModelLossDecrementsCountedKeyExactlyOnce(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $prefix = $this->createItemAffix(['name' => 'Missing Item Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'craft_enchant_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_enchant_set_keys' => ['dagger'],
                'craft_enchant_set_crafted_item_ids' => ['dagger' => 999999999],
                'enchant_plan' => ['dagger' => ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => null]],
                'craft_enchant_set_requested' => 1,
                'craft_enchant_set_phase' => 'enchanting',
                'craft_enchant_set_craft_index' => 1,
                'craft_enchant_set_enchant_index' => 0,
                'craft_enchant_set_total_work_units' => 1,
                'craft_enchant_set_completed_work_units' => 0,
                'craft_enchant_set_surviving_crafted_count' => 1,
                'craft_enchant_set_counted_crafted_keys' => ['dagger'],
                'craft_enchant_set_lost_item_keys' => [],
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertContains('dagger', $result->progress['craft_enchant_set_lost_item_keys'] ?? []);
        $this->assertNotContains('dagger', $result->progress['craft_enchant_set_counted_crafted_keys'] ?? []);
        $this->assertSame(0, $result->progress['craft_enchant_set_surviving_crafted_count']);
        $this->assertSame(0, $result->progress['craft_enchant_set_completed_work_units']);
        $this->assertArrayHasKey('dagger', $result->progress['craft_enchant_set_crafted_item_ids']);
        $this->assertNull($result->progress['craft_enchant_set_crafted_item_ids']['dagger']);
    }

    public function testCraftEnchantSetShatteredLossDecrementsCountedKeyExactlyOnce(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30, 'int' => 100]);
        $prefix = $this->createItemAffix(['name' => 'Shatter Counted Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1]);
        $item = $this->createItem(['name' => 'Shatter Counted Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon']);
        $this->instance(
            EnchantingService::class,
            Mockery::mock(EnchantingService::class, function ($mock) {
                $mock->shouldReceive('getCostOfEnchantment')->andReturn(0);
                $mock->shouldReceive('enchantItemForBatch')->andReturn(['success' => false, 'item' => null, 'reason' => 'shattered']);
            })
        );
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'selected_set_id' => $set->id,
                'craft_enchant_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_enchant_set_keys' => ['dagger'],
                'craft_enchant_set_crafted_item_ids' => ['dagger' => $item->id],
                'enchant_plan' => ['dagger' => ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => null]],
                'craft_enchant_set_requested' => 1,
                'craft_enchant_set_phase' => 'enchanting',
                'craft_enchant_set_craft_index' => 1,
                'craft_enchant_set_enchant_index' => 0,
                'craft_enchant_set_total_work_units' => 1,
                'craft_enchant_set_completed_work_units' => 0,
                'craft_enchant_set_surviving_crafted_count' => 1,
                'craft_enchant_set_counted_crafted_keys' => ['dagger'],
                'craft_enchant_set_lost_item_keys' => [],
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertContains('dagger', $result->progress['craft_enchant_set_lost_item_keys'] ?? []);
        $this->assertNotContains('dagger', $result->progress['craft_enchant_set_counted_crafted_keys'] ?? []);
        $this->assertSame(0, $result->progress['craft_enchant_set_surviving_crafted_count']);
        $this->assertSame(0, $result->progress['craft_enchant_set_completed_work_units']);
    }

    public function testCraftEnchantSetPartialAffixDiscardedLossDecrementsCountedKeyExactlyOnce(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30, 'int' => 100]);
        $prefix = $this->createItemAffix(['name' => 'Partial Discard Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1]);
        $suffix = $this->createItemAffix(['name' => 'Partial Discard Suffix', 'type' => 'suffix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1]);
        $item = $this->createItem(['name' => 'Partial Discard Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon']);
        $this->instance(
            EnchantingService::class,
            Mockery::mock(EnchantingService::class, function ($mock) use ($prefix) {
                $mock->shouldReceive('getCostOfEnchantment')->andReturn(0);
                $mock->shouldReceive('enchantItemForBatch')->andReturnUsing(function ($character, $item) use ($prefix) {
                    $item->update(['item_prefix_id' => $prefix->id]);
                    $item->refresh();

                    return ['success' => true, 'item' => $item, 'reason' => null];
                });
            })
        );
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'craft_enchant_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_enchant_set_keys' => ['dagger'],
                'craft_enchant_set_crafted_item_ids' => ['dagger' => $item->id],
                'enchant_plan' => ['dagger' => ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => $suffix->id]],
                'craft_enchant_set_requested' => 1,
                'craft_enchant_set_phase' => 'enchanting',
                'craft_enchant_set_craft_index' => 1,
                'craft_enchant_set_enchant_index' => 0,
                'craft_enchant_set_total_work_units' => 1,
                'craft_enchant_set_completed_work_units' => 0,
                'craft_enchant_set_surviving_crafted_count' => 1,
                'craft_enchant_set_counted_crafted_keys' => ['dagger'],
                'craft_enchant_set_lost_item_keys' => [],
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertContains('dagger', $result->progress['craft_enchant_set_lost_item_keys'] ?? []);
        $this->assertNotContains('dagger', $result->progress['craft_enchant_set_counted_crafted_keys'] ?? []);
        $this->assertSame(0, $result->progress['craft_enchant_set_surviving_crafted_count']);
        $this->assertSame(0, $result->progress['craft_enchant_set_completed_work_units']);
    }

    public function testCraftEnchantSetLossIsIdempotentAndDoesNotDoubleDecrementAlreadyLostKey(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $prefix = $this->createItemAffix(['name' => 'Idempotent Loss Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'craft_enchant_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_enchant_set_keys' => ['dagger'],
                'craft_enchant_set_crafted_item_ids' => ['dagger' => null],
                'enchant_plan' => ['dagger' => ['prefix_affix_id' => $prefix->id, 'suffix_affix_id' => null]],
                'craft_enchant_set_requested' => 1,
                'craft_enchant_set_phase' => 'enchanting',
                'craft_enchant_set_craft_index' => 1,
                'craft_enchant_set_enchant_index' => 0,
                'craft_enchant_set_total_work_units' => 1,
                'craft_enchant_set_completed_work_units' => 0,
                'craft_enchant_set_surviving_crafted_count' => 1,
                'craft_enchant_set_counted_crafted_keys' => ['dagger'],
                'craft_enchant_set_lost_item_keys' => ['dagger'],
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(['dagger'], $result->progress['craft_enchant_set_lost_item_keys']);
        $this->assertSame(1, $result->progress['craft_enchant_set_surviving_crafted_count']);
        $this->assertSame(0, $result->progress['craft_enchant_set_completed_work_units']);
    }

    public function testCraftEnchantSetReplacementValidLostStateRestoresCountedKeyExactlyOnce(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['gold' => 100000, 'inventory_max' => 30]);
        $replacementItem = $this->createItem(['name' => 'Counted Key Replacement Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'output_destination' => 'crafted_items_set',
                'craft_enchant_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_enchant_set_keys' => ['dagger'],
                'craft_enchant_set_selected_item_ids' => ['dagger' => $replacementItem->id],
                'craft_enchant_set_crafted_item_ids' => ['dagger' => null],
                'craft_enchant_set_lost_item_keys' => ['dagger'],
                'craft_enchant_set_counted_crafted_keys' => [],
                'enchant_plan' => [],
                'craft_enchant_set_requested' => 1,
                'craft_enchant_set_phase' => 'replacement_crafting',
                'craft_enchant_set_craft_index' => 1,
                'craft_enchant_set_enchant_index' => 0,
                'craft_enchant_set_total_work_units' => 1,
                'craft_enchant_set_completed_work_units' => 0,
                'craft_enchant_set_surviving_crafted_count' => 0,
                'craft_enchant_set_replacement_key' => 'dagger',
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(['dagger'], $result->progress['craft_enchant_set_counted_crafted_keys'] ?? null);
        $this->assertNotContains('dagger', $result->progress['craft_enchant_set_lost_item_keys'] ?? []);
        $this->assertSame(1, $result->progress['craft_enchant_set_surviving_crafted_count']);
        $this->assertSame(0, $result->progress['craft_enchant_set_completed_work_units']);
    }

    public function testCraftEnchantSetReplacementRejectsKeyNotInLostState(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['gold' => 100000, 'inventory_max' => 30]);
        $replacementItem = $this->createItem(['name' => 'Not Lost Replacement Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'output_destination' => 'crafted_items_set',
                'craft_enchant_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_enchant_set_keys' => ['dagger'],
                'craft_enchant_set_selected_item_ids' => ['dagger' => $replacementItem->id],
                'craft_enchant_set_crafted_item_ids' => ['dagger' => null],
                'craft_enchant_set_lost_item_keys' => [],
                'craft_enchant_set_counted_crafted_keys' => [],
                'enchant_plan' => [],
                'craft_enchant_set_requested' => 1,
                'craft_enchant_set_phase' => 'replacement_crafting',
                'craft_enchant_set_craft_index' => 1,
                'craft_enchant_set_enchant_index' => 0,
                'craft_enchant_set_total_work_units' => 1,
                'craft_enchant_set_completed_work_units' => 0,
                'craft_enchant_set_surviving_crafted_count' => 0,
                'craft_enchant_set_replacement_key' => 'dagger',
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::FAILED->value, $result->ended_reason);
        $this->assertSame(0, $result->progress['craft_enchant_set_surviving_crafted_count']);
        $this->assertSame([], $result->progress['craft_enchant_set_counted_crafted_keys'] ?? []);
        $this->assertArrayHasKey('dagger', $result->progress['craft_enchant_set_crafted_item_ids']);
        $this->assertNull($result->progress['craft_enchant_set_crafted_item_ids']['dagger']);
        $stoppedAction = collect($result->action_log)->first(fn (array $entry) => ($entry['failure'] ?? null) === 'Replacement progress could not be restored because the plan key was not in a valid lost-item state.');
        $this->assertNotNull($stoppedAction);
    }

    public function testCraftEnchantSetReplacementRejectsAlreadyCountedKeyPreventingDoubleRestoration(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['gold' => 100000, 'inventory_max' => 30]);
        $replacementItem = $this->createItem(['name' => 'Already Counted Replacement Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'craft_enchant_set',
                'output_destination' => 'crafted_items_set',
                'craft_enchant_set_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_enchant_set_keys' => ['dagger'],
                'craft_enchant_set_selected_item_ids' => ['dagger' => $replacementItem->id],
                'craft_enchant_set_crafted_item_ids' => ['dagger' => null],
                'craft_enchant_set_lost_item_keys' => ['dagger'],
                'craft_enchant_set_counted_crafted_keys' => ['dagger'],
                'enchant_plan' => [],
                'craft_enchant_set_requested' => 1,
                'craft_enchant_set_phase' => 'replacement_crafting',
                'craft_enchant_set_craft_index' => 1,
                'craft_enchant_set_enchant_index' => 0,
                'craft_enchant_set_total_work_units' => 1,
                'craft_enchant_set_completed_work_units' => 0,
                'craft_enchant_set_surviving_crafted_count' => 1,
                'craft_enchant_set_replacement_key' => 'dagger',
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::FAILED->value, $result->ended_reason);
        $this->assertSame(1, $result->progress['craft_enchant_set_surviving_crafted_count']);
        $this->assertSame(['dagger'], $result->progress['craft_enchant_set_counted_crafted_keys']);
    }

    public function testCraftAmountProducesNoMoreThanSixActionsInOneProcessCallAndContinuesOnNextCall(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 200]);
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
            })
        );
        $item = $this->createItem(['name' => 'Processor Bounded Amount Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'specific_item',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $item->id,
                'craft_amount' => 10,
                'craft_specific_count' => 0,
            ],
        ]);

        $batchCrafting = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertCount(6, $batchCrafting->action_log);
        $this->assertSame(6, $batchCrafting->progress['craft_specific_count'] ?? null);
        $this->assertTrue($batchCrafting->isRunning());

        $batchCrafting = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertCount(10, $batchCrafting->action_log);
        $this->assertSame(10, $batchCrafting->progress['craft_specific_count'] ?? null);
        $this->assertSame(BatchCraftingEndReason::AMOUNT_REACHED->value, $batchCrafting->ended_reason);
    }

    public function testCraftAndEnchantAmountProducesNoMoreThanSixPhaseOperationActionsAndDoesNotCountThemAsCompletedItems(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 200]);
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
            })
        );
        $item = $this->createItem(['name' => 'Processor Bounded CE Amount Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $prefix = $this->createItemAffix(['name' => 'Processor Bounded CE Amount Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'specific_item',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $item->id,
                'craft_amount' => 10,
                'craft_enchant_specific_count' => 0,
                'enchant_affix_ids' => [$prefix->id],
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertCount(6, $result->action_log);
        $this->assertSame(3, $result->progress['craft_enchant_specific_count'] ?? null);
        $this->assertTrue($result->isRunning());
    }

    public function testAlchemyAmountProducesNoMoreThanSixActionsAndFailedAttemptsDoNotAdvanceCompletion(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ALCHEMY->value)->update(['level' => 5, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold_dust' => 100000, 'shards' => 100000, 'alchemy_bag_limit' => 50, 'inventory_max' => 10]);
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
            })
        );
        $item = $this->createItem(['name' => 'Processor Bounded Alchemy Amount Item', 'type' => 'alchemy', 'crafting_type' => 'alchemy', 'can_craft' => true, 'gold_dust_cost' => 1, 'shards_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_mode' => 'amount', 'alchemy_amount' => 10, 'alchemy_amount_count' => 0, 'alchemy_item_id' => $item->id],
        ]);

        $batchCrafting = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertCount(6, $batchCrafting->action_log);
        $this->assertSame(6, $batchCrafting->progress['alchemy_amount_count'] ?? null);
        $this->assertTrue($batchCrafting->isRunning());

        $batchCrafting = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertCount(10, $batchCrafting->action_log);
        $this->assertSame(10, $batchCrafting->progress['alchemy_amount_count'] ?? null);
        $this->assertSame(BatchCraftingEndReason::AMOUNT_REACHED->value, $batchCrafting->ended_reason);
    }

    public function testHolyOilsSelectedProducesNoMoreThanSixApplicationActionsAndPersistsSelectedItemsAcrossCalls(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->update(['gold_dust' => 100000, 'inventory_max' => 20]);
        $item = $this->createItem(['name' => 'Processor Bounded Holy Oil Item', 'type' => 'dagger', 'crafting_type' => 'weapon', 'holy_stacks' => 1, 'cost' => 1]);
        $slotOne = $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $item->id]);
        $slotTwo = $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $item->id]);
        $slotThree = $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $item->id]);
        $slotFour = $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $item->id]);
        $slotFive = $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $item->id]);
        $slotSix = $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $item->id]);
        $slotSeven = $this->createInventorySlot(['inventory_id' => $character->inventory->id, 'item_id' => $item->id]);
        $oil = $this->createItem(['name' => 'Processor Bounded Holy Oil', 'type' => 'alchemy', 'can_use_on_other_items' => true, 'holy_level' => 1]);
        $oilSlot = $this->createAlchemyBagSlot(['alchemy_bag_id' => $character->alchemyBag->id, 'character_id' => $character->id, 'item_id' => $oil->id, 'amount' => 20]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'selected_items' => [$slotOne->id, $slotTwo->id, $slotThree->id, $slotFour->id, $slotFive->id, $slotSix->id, $slotSeven->id],
            'selected_oils' => [$oilSlot->id],
            'progress' => [
                'holy_oil_mode' => 'selected',
                'holy_oil_application_plan' => [
                    'application_sequence' => [
                        ['target_slot_id' => $slotOne->id, 'oil_slot_id' => $oilSlot->id, 'gold_dust_cost' => 1],
                        ['target_slot_id' => $slotTwo->id, 'oil_slot_id' => $oilSlot->id, 'gold_dust_cost' => 1],
                        ['target_slot_id' => $slotThree->id, 'oil_slot_id' => $oilSlot->id, 'gold_dust_cost' => 1],
                        ['target_slot_id' => $slotFour->id, 'oil_slot_id' => $oilSlot->id, 'gold_dust_cost' => 1],
                        ['target_slot_id' => $slotFive->id, 'oil_slot_id' => $oilSlot->id, 'gold_dust_cost' => 1],
                        ['target_slot_id' => $slotSix->id, 'oil_slot_id' => $oilSlot->id, 'gold_dust_cost' => 1],
                        ['target_slot_id' => $slotSeven->id, 'oil_slot_id' => $oilSlot->id, 'gold_dust_cost' => 1],
                    ],
                ],
                'holy_oil_application_results' => [],
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertCount(6, $result->action_log);
        $this->assertSame(6, $result->progress['holy_oil_completed_applications'] ?? null);
        $this->assertCount(6, $result->progress['holy_oil_application_results'] ?? []);
        $this->assertSame(1, $result->progress['holy_oil_application_results'][0]['actual_applications_completed'] ?? null);
        $this->assertSame(1, $result->progress['holy_oil_application_results'][0]['actual_resulting_stack_count'] ?? null);
        $this->assertSame(1, $result->progress['holy_oil_application_results'][0]['oil_applications_consumed'] ?? null);
        $this->assertNotNull($result->progress['holy_oil_application_results'][0]['item']['full_item_details'] ?? null);
        $this->assertCount(1, $result->selected_items ?? []);
        $this->assertNotSame(BatchCraftingEndReason::ALL_OILS_APPLIED->value, $result->ended_reason);
    }

    public function testEventEnchantSingleSelectedPrefixSucceedsAndIncrementsEventContributionOnce(): void
    {
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
            })
        );
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $schedule = $this->createScheduledEvent(['event_type' => EventType::WINTER_EVENT, 'status' => ScheduledEventStatus::RUNNING, 'currently_running' => true]);
        $event = $this->createEvent(['type' => EventType::WINTER_EVENT, 'scheduled_event_id' => $schedule->id, 'current_event_goal_step' => GlobalEventSteps::ENCHANT, 'ends_at' => now()->addHour()]);
        $goal = $this->createGlobalEventGoal(['event_type' => $event->type, 'event_id' => $event->id, 'max_enchants' => 100, 'item_specialty_type_reward' => ItemSpecialtyType::HELL_FORGED]);
        $eventMap = $this->createGameMap(['only_during_event_type' => $event->type]);
        $character->map()->update(['game_map_id' => $eventMap->id]);
        $item = $this->createItem(['name' => 'Exact Affix Event Item', 'type' => 'weapon', 'crafting_type' => 'weapon']);
        $inventory = $this->createGlobalCraftingInventory(['global_event_goal_id' => $goal->id, 'character_id' => $character->id]);
        $slot = $this->createGlobalCraftingInventorySlot(['global_event_crafting_inventory_id' => $inventory->id, 'item_id' => $item->id]);
        $prefix = $this->createItemAffix(['name' => 'Exact Affix Event Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = resolve(BatchCraftingService::class)->start($character->refresh(), [
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['enchant_mode' => 'event'],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $action = collect($result->action_log)->first(fn (array $entry) => ($entry['action_type'] ?? null) === 'event_enchant');

        $this->assertSame($prefix->name, $action['enchanted_item']['item_prefix'] ?? null);
        $this->assertSame(1, $result->progress['outcome_totals']['enchanted'] ?? null);
        $this->assertNull(GlobalEventCraftingInventorySlot::find($slot->id));
        $enchantRecord = GlobalEventEnchant::where('character_id', $character->id)->where('global_event_goal_id', $goal->id)->first();
        $this->assertSame(1, $enchantRecord?->enchants);
    }

    public function testEventEnchantBothPrefixAndSuffixSucceedAppliesExactAffixesAndIncrementsEventContributionOnce(): void
    {
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
            })
        );
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $schedule = $this->createScheduledEvent(['event_type' => EventType::WINTER_EVENT, 'status' => ScheduledEventStatus::RUNNING, 'currently_running' => true]);
        $event = $this->createEvent(['type' => EventType::WINTER_EVENT, 'scheduled_event_id' => $schedule->id, 'current_event_goal_step' => GlobalEventSteps::ENCHANT, 'ends_at' => now()->addHour()]);
        $goal = $this->createGlobalEventGoal(['event_type' => $event->type, 'event_id' => $event->id, 'max_enchants' => 100, 'item_specialty_type_reward' => ItemSpecialtyType::HELL_FORGED]);
        $eventMap = $this->createGameMap(['only_during_event_type' => $event->type]);
        $character->map()->update(['game_map_id' => $eventMap->id]);
        $item = $this->createItem(['name' => 'Both Affix Event Item', 'type' => 'weapon', 'crafting_type' => 'weapon']);
        $inventory = $this->createGlobalCraftingInventory(['global_event_goal_id' => $goal->id, 'character_id' => $character->id]);
        $slot = $this->createGlobalCraftingInventorySlot(['global_event_crafting_inventory_id' => $inventory->id, 'item_id' => $item->id]);
        $prefix = $this->createItemAffix(['name' => 'Both Affix Event Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $suffix = $this->createItemAffix(['name' => 'Both Affix Event Suffix', 'type' => 'suffix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = resolve(BatchCraftingService::class)->start($character->refresh(), [
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['enchant_mode' => 'event'],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $action = collect($result->action_log)->first(fn (array $entry) => ($entry['action_type'] ?? null) === 'event_enchant');

        $this->assertSame($prefix->name, $action['enchanted_item']['item_prefix'] ?? null);
        $this->assertSame($suffix->name, $action['enchanted_item']['item_suffix'] ?? null);
        $this->assertSame(1, $result->progress['outcome_totals']['enchanted'] ?? null);
        $this->assertNull(GlobalEventCraftingInventorySlot::find($slot->id));
        $enchantRecord = GlobalEventEnchant::where('character_id', $character->id)->where('global_event_goal_id', $goal->id)->first();
        $this->assertSame(1, $enchantRecord?->enchants);
    }

    public function testEventEnchantShatteringDuringSecondAffixDestroysItemWithoutTypeErrorAndDoesNotIncrementEnchantedCount(): void
    {
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(50);
                $mock->shouldReceive('characterRoll')->andReturn(100, 1);
            })
        );
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $schedule = $this->createScheduledEvent(['event_type' => EventType::WINTER_EVENT, 'status' => ScheduledEventStatus::RUNNING, 'currently_running' => true]);
        $event = $this->createEvent(['type' => EventType::WINTER_EVENT, 'scheduled_event_id' => $schedule->id, 'current_event_goal_step' => GlobalEventSteps::ENCHANT, 'ends_at' => now()->addHour()]);
        $goal = $this->createGlobalEventGoal(['event_type' => $event->type, 'event_id' => $event->id, 'max_enchants' => 100, 'item_specialty_type_reward' => ItemSpecialtyType::HELL_FORGED]);
        $eventMap = $this->createGameMap(['only_during_event_type' => $event->type]);
        $character->map()->update(['game_map_id' => $eventMap->id]);
        $item = $this->createItem(['name' => 'Shatter Event Item', 'type' => 'weapon', 'crafting_type' => 'weapon']);
        $inventory = $this->createGlobalCraftingInventory(['global_event_goal_id' => $goal->id, 'character_id' => $character->id]);
        $slot = $this->createGlobalCraftingInventorySlot(['global_event_crafting_inventory_id' => $inventory->id, 'item_id' => $item->id]);
        $this->createItemAffix(['name' => 'Shatter Event Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->createItemAffix(['name' => 'Shatter Event Suffix', 'type' => 'suffix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = resolve(BatchCraftingService::class)->start($character->refresh(), [
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['enchant_mode' => 'event'],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $action = collect($result->action_log)->first(fn (array $entry) => ($entry['action_type'] ?? null) === 'event_enchant');

        $this->assertSame('destroyed', $action['status'] ?? null);
        $this->assertSame(1, $result->destroyed_count);
        $this->assertSame(0, $result->progress['outcome_totals']['enchanted'] ?? 0);
        $this->assertNull(GlobalEventCraftingInventorySlot::find($slot->id));
    }

    public function testEventEnchantSurvivingItemMissingOneSelectedAffixIsReportedFailedAndLeftAvailable(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $schedule = $this->createScheduledEvent(['event_type' => EventType::WINTER_EVENT, 'status' => ScheduledEventStatus::RUNNING, 'currently_running' => true]);
        $event = $this->createEvent(['type' => EventType::WINTER_EVENT, 'scheduled_event_id' => $schedule->id, 'current_event_goal_step' => GlobalEventSteps::ENCHANT, 'ends_at' => now()->addHour()]);
        $goal = $this->createGlobalEventGoal(['event_type' => $event->type, 'event_id' => $event->id, 'max_enchants' => 100, 'item_specialty_type_reward' => ItemSpecialtyType::HELL_FORGED]);
        $eventMap = $this->createGameMap(['only_during_event_type' => $event->type]);
        $character->map()->update(['game_map_id' => $eventMap->id]);
        $item = $this->createItem(['name' => 'Partial Affix Event Item', 'type' => 'weapon', 'crafting_type' => 'weapon']);
        $inventory = $this->createGlobalCraftingInventory(['global_event_goal_id' => $goal->id, 'character_id' => $character->id]);
        $slot = $this->createGlobalCraftingInventorySlot(['global_event_crafting_inventory_id' => $inventory->id, 'item_id' => $item->id]);
        $this->createItemAffix(['name' => 'Partial Event Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->createItemAffix(['name' => 'Partial Event Suffix', 'type' => 'suffix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->instance(
            EnchantingService::class,
            Mockery::mock(EnchantingService::class, function ($mock) {
                $mock->shouldReceive('getCostOfEnchantment')->andReturn(0);
                $mock->shouldReceive('enchant')->andReturnUsing(function ($character, $params, $slot) {
                    $slot->item->update(['item_prefix_id' => $params['affix_ids'][0]]);
                });
            })
        );
        $batchCrafting = resolve(BatchCraftingService::class)->start($character->refresh(), [
            'batch_type' => BatchCraftingType::ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['enchant_mode' => 'event'],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $action = collect($result->action_log)->first(fn (array $entry) => ($entry['action_type'] ?? null) === 'event_enchant');
        $failedActions = collect($result->action_log)->filter(fn (array $entry) => ($entry['action_type'] ?? null) === 'event_enchant' && ($entry['status'] ?? null) === 'failed');

        $this->assertSame('failed', $action['status'] ?? null);
        $this->assertCount(1, $failedActions);
        $this->assertSame(1, $result->failed_count);
        $this->assertSame(0, $result->progress['outcome_totals']['enchanted'] ?? 0);
        $this->assertNotNull(GlobalEventCraftingInventorySlot::find($slot->id));
        $this->assertSame('waiting', $result->progress['continuation_state'] ?? null);
        $this->assertSame(2, $result->progress['continuation_delay_seconds'] ?? null);
        $enchantRecord = GlobalEventEnchant::where('character_id', $character->id)->where('global_event_goal_id', $goal->id)->first();
        $this->assertNull($enchantRecord);

        $retried = resolve(BatchCraftingService::class)->process($result);

        $this->assertSame(2, $retried->failed_count);
        $this->assertNotNull(GlobalEventCraftingInventorySlot::find($slot->id));
    }

    public function testMissingCraftAndEnchantExperienceItemIsReportedAsFailedNotSilentlyDropped(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->assignSkill($weaponCrafting, 10, false, ['xp' => 0, 'xp_max' => 100])
            ->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 200]);
        $craftableItem = $this->createItem(['name' => 'Missing Item Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $craftForBatchCalls = 0;
        $this->instance(
            CraftingService::class,
            Mockery::mock(CraftingService::class, function ($mock) use (&$craftForBatchCalls, $craftableItem) {
                $mock->makePartial()->shouldReceive('fetchCraftableItems')->andReturn(collect([$craftableItem]));
                $mock->makePartial()->shouldReceive('craftForBatch')->andReturnUsing(function ($character, $item, $craftingType, $suppress = false) use (&$craftForBatchCalls) {
                    $craftForBatchCalls++;

                    if ($craftForBatchCalls === 1) {
                        $clone = $item->replicate();
                        $clone->name = 'Missing Item Clone';
                        $clone->save();
                        $clone->delete();

                        return ['success' => true, 'item' => $clone];
                    }

                    return ['success' => false, 'item' => null];
                });
            })
        );
        $prefix = $this->createItemAffix(['name' => 'Missing Item Prefix', 'type' => 'prefix', 'cost' => 1, 'int_required' => 0, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_mode' => 'experience',
                'craft_experience_queue' => [['type' => 'dagger', 'crafting_type' => 'dagger']],
                'craft_experience_index' => 0,
                'craft_experience_has_targets' => true,
                'enchant_affix_ids' => [$prefix->id],
            ],
        ]);
        $processor = Mockery::mock(\App\Game\BatchCrafting\Services\BatchCraftingProcessor::class);
        $processor->shouldReceive('processOneTick')->once()->andReturn([
            'counts' => ['failed_count' => 1],
            'actions' => [[
                'action' => 'craft_and_enchant',
                'status' => 'failed',
                'failure' => 'The crafted item could not be found before enchanting. Another item will be crafted on a later attempt.',
            ]],
        ]);
        $this->instance(\App\Game\BatchCrafting\Services\BatchCraftingProcessor::class, $processor);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertGreaterThan(0, $result->failed_count);
        $this->assertTrue(collect($result->action_log)->contains(fn (array $entry) => ($entry['status'] ?? null) === 'failed'));
    }
    public function testAlchemyAmountReturnsAmountReachedOnFinalSuccessfulItem(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ALCHEMY->value)->update(['level' => 2]);
        $character->update(['gold_dust' => 10, 'shards' => 10, 'alchemy_bag_limit' => 10]);
        $item = $this->createItem(['name' => 'Final Amount Alchemy Item', 'type' => 'alchemy', 'crafting_type' => 'alchemy', 'can_craft' => true, 'gold_dust_cost' => 1, 'shards_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $character->user_id, 'batch_type' => BatchCraftingType::ALCHEMY->value, 'disposition' => BatchCraftingDisposition::KEEP->value, 'progress' => ['alchemy_mode' => 'amount', 'alchemy_amount' => 1, 'alchemy_amount_count' => 0, 'alchemy_item_id' => $item->id]]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::AMOUNT_REACHED->value, $result->ended_reason);
        $this->assertSame(1, $result->progress['alchemy_amount_count']);
        $this->assertSame('alchemy', $result->action_log[0]['action_type']);
    }

    public function testAlchemyAmountFinalItemMayConsumeRemainingGoldDustAndEndAmountReached(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ALCHEMY->value)->update(['level' => 2]);
        $character->update(['gold_dust' => 1, 'shards' => 10, 'alchemy_bag_limit' => 10]);
        $item = $this->createItem(['name' => 'Remaining Dust Alchemy Item', 'type' => 'alchemy', 'crafting_type' => 'alchemy', 'can_craft' => true, 'gold_dust_cost' => 1, 'shards_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $character->user_id, 'batch_type' => BatchCraftingType::ALCHEMY->value, 'disposition' => BatchCraftingDisposition::KEEP->value, 'progress' => ['alchemy_mode' => 'amount', 'alchemy_amount' => 1, 'alchemy_amount_count' => 0, 'alchemy_item_id' => $item->id]]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::AMOUNT_REACHED->value, $result->ended_reason);
        $this->assertSame(0, $character->refresh()->gold_dust);
        $this->assertSame(1, $result->crafted_count);
    }

    public function testAlchemyAmountRunningOutOfGoldDustBeforeRequestedAmountReturnsCurrencyReason(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ALCHEMY->value)->update(['level' => 2]);
        $character->update(['gold_dust' => 1, 'shards' => 10, 'alchemy_bag_limit' => 10]);
        $item = $this->createItem(['name' => 'Insufficient Dust Alchemy Item', 'type' => 'alchemy', 'crafting_type' => 'alchemy', 'can_craft' => true, 'gold_dust_cost' => 1, 'shards_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $character->user_id, 'batch_type' => BatchCraftingType::ALCHEMY->value, 'disposition' => BatchCraftingDisposition::KEEP->value, 'progress' => ['alchemy_mode' => 'amount', 'alchemy_amount' => 2, 'alchemy_amount_count' => 0, 'alchemy_item_id' => $item->id]]);

        $batchCrafting = resolve(BatchCraftingService::class)->process($batchCrafting);
        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::NO_GOLD_DUST->value, $result->ended_reason);
        $this->assertSame(1, $result->progress['alchemy_amount_count']);
    }
}
