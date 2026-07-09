<?php

namespace Tests\Unit\Game\BatchCrafting\Services;

use App\Admin\Services\MonitoredBugReportService;
use App\Flare\Values\ItemSpecialtyType;
use App\Game\BatchCrafting\Services\BatchCraftingService;
use App\Game\BatchCrafting\Values\BatchCraftingDisposition;
use App\Game\BatchCrafting\Values\BatchCraftingEndReason;
use App\Game\BatchCrafting\Values\BatchCraftingType;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\GlobalEventSteps;
use App\Game\Skills\Services\SkillCheckService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateBatchCrafting;
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

class BatchCraftingProcessorTest extends TestCase
{
    use CreateBatchCrafting, CreateEvent, CreateGameMap, CreateGameSkill, CreateGlobalCraftingInventory, CreateGlobalCraftingInventorySlot, CreateGlobalEventGoal, CreateInventorySets, CreateInventorySlot, CreateItem, CreateItemAffix, MockeryPHPUnitIntegration, RefreshDatabase;

    public function testCraftEnchantSetCraftPhaseCraftsSelectedItemIdInsteadOfHighestCraftableItem(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $this->createItem(['name' => 'Processor Selected High Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 5, 'skill_level_trivial' => 5]);
        $lowDagger = $this->createItem(['name' => 'Processor Selected Low Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
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
                'craft_enchant_set_selected_item_ids' => ['dagger' => $lowDagger->id],
                'enchant_plan' => [],
                'craft_enchant_set_requested' => 1,
                'craft_enchant_set_phase' => 'crafting',
                'craft_enchant_set_craft_index' => 0,
                'craft_enchant_set_enchant_index' => 0,
                'craft_enchant_set_finalize_index' => 0,
                'craft_enchant_set_crafted_slots' => [],
                'craft_enchant_set_prefix_applied_count' => 0,
                'craft_enchant_set_suffix_applied_count' => 0,
                'craft_enchant_set_total_work_units' => 3,
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
        $this->createItem(['name' => 'Processor Fallback High Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 5, 'skill_level_trivial' => 5]);
        $this->createItem(['name' => 'Processor Fallback Low Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
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
                'craft_enchant_set_selected_item_ids' => [],
                'enchant_plan' => [],
                'craft_enchant_set_requested' => 1,
                'craft_enchant_set_phase' => 'crafting',
                'craft_enchant_set_craft_index' => 0,
                'craft_enchant_set_enchant_index' => 0,
                'craft_enchant_set_finalize_index' => 0,
                'craft_enchant_set_crafted_slots' => [],
                'craft_enchant_set_prefix_applied_count' => 0,
                'craft_enchant_set_suffix_applied_count' => 0,
                'craft_enchant_set_total_work_units' => 3,
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
                'craft_enchant_set_finalize_index' => 0,
                'craft_enchant_set_prefix_applied_count' => 0,
                'craft_enchant_set_suffix_applied_count' => 0,
                'craft_enchant_set_total_work_units' => 3,
                'craft_enchant_set_completed_work_units' => 1,
            ],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::INT_TOO_LOW_FOR_ENCHANTING->value, $result->ended_reason);
    }

    public function testCraftAndEnchantForExperienceResolvesExactIntendedAutoAffixAndStopsWhenItRequiresTooMuchInt(): void
    {
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

    public function testCraftAndEnchantForExperienceProcessesExactlyTwentyThreeWorkflowsPerTick(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 30]);
        $this->createItem(['name' => 'Twenty Three Workflow Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertCount(23, $result->action_log);
    }

    public function testEnchantForEventEnchantsExistingEventItemsBeforeCraftingFallbackSet(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $event = $this->createEvent(['type' => EventType::WINTER_EVENT, 'current_event_goal_step' => GlobalEventSteps::ENCHANT, 'ends_at' => now()->addHour()]);
        $goal = $this->createGlobalEventGoal(['event_type' => $event->type, 'max_enchants' => 100, 'item_specialty_type_reward' => ItemSpecialtyType::HELL_FORGED]);
        $eventMap = $this->createGameMap(['only_during_event_type' => $event->type]);
        $character->map()->update(['game_map_id' => $eventMap->id]);
        $item = $this->createItem(['name' => 'Existing Event Item', 'type' => 'weapon', 'crafting_type' => 'weapon']);
        $inventory = $this->createGlobalCraftingInventory(['global_event_id' => $goal->id, 'character_id' => $character->id]);
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
        $event = $this->createEvent(['type' => EventType::WINTER_EVENT, 'current_event_goal_step' => GlobalEventSteps::ENCHANT, 'ends_at' => now()->addHour()]);
        $this->createGlobalEventGoal(['event_type' => $event->type, 'max_enchants' => 100, 'item_specialty_type_reward' => ItemSpecialtyType::HELL_FORGED]);
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
}
