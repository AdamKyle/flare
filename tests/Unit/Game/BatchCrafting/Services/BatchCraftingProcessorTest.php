<?php

namespace Tests\Unit\Game\BatchCrafting\Services;

use App\Admin\Services\MonitoredBugReportService;
use App\Flare\Models\AlchemyBagSlot;
use App\Flare\Models\Character;
use App\Flare\Models\GlobalEventCraftingInventorySlot;
use App\Flare\Models\GlobalEventEnchant;
use App\Flare\Models\InventorySet;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\SetSlot;
use App\Flare\Models\SuggestionAndBugs;
use App\Game\BatchCrafting\Services\BatchCraftingProcessor;
use App\Game\BatchCrafting\Services\BatchCraftingService;
use App\Game\BatchCrafting\Values\BatchCraftingDisposition;
use App\Game\BatchCrafting\Values\BatchCraftingEndReason;
use App\Game\BatchCrafting\Values\BatchCraftingType;
use App\Game\Character\CharacterInventory\Exceptions\BatchCraftingDestinationFullException;
use App\Game\Character\CharacterInventory\Jobs\CharacterBoonJob;
use App\Game\Character\CharacterInventory\Services\BatchCraftingSetService;
use App\Game\Core\Items\Values\ItemSpecialtyType;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\GlobalEventSteps;
use App\Game\Events\Values\ScheduledEventStatus;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Services\EnchantingService;
use App\Game\Skills\Services\SkillCheckService;
use App\Game\Skills\Services\TrinketCraftingService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateAlchemyBagSlot;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateCharacterBoon;
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
    use CreateAlchemyBagSlot, CreateBatchCrafting, CreateCharacterBoon, CreateEvent, CreateGameMap, CreateGameSkill, CreateGlobalCraftingInventory, CreateGlobalCraftingInventorySlot, CreateGlobalEventGoal, CreateInventorySets, CreateInventorySlot, CreateItem, CreateItemAffix, CreateScheduledEvent, MockeryPHPUnitIntegration, RefreshDatabase;

    private ?CharacterFactory $character;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function test_unexpected_xp_eligible_item_lookup_exception_fails_batch_instead_of_skipping(): void
    {
        $weaponCrafting = $this->createGameSkill([
            'name' => 'Weapon Crafting',
            'type' => SkillTypeValue::CRAFTING->value,
            'max_level' => 400,
        ]);
        $character = $this->character->assignSkill($weaponCrafting, 1, false)->getCharacter();
        $craftingService = Mockery::mock(CraftingService::class);
        $craftingService->shouldReceive('fetchCraftableItems')
            ->once()
            ->andThrow(new \RuntimeException('craftable item query failed'));
        $this->instance(CraftingService::class, $craftingService);
        $batch = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'experience'],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batch);

        $this->assertSame(BatchCraftingEndReason::FAILED->value, $result->ended_reason);
        $this->assertSame(0, $result->skipped_count);
        $this->assertSame(0, $result->failed_count);
        $this->assertSame(1, SuggestionAndBugs::query()->count());
    }

    public function test_trinketry_stops_cleanly_for_insufficient_gold_dust(): void
    {
        Event::fake();
        $trinketry = $this->createGameSkill(['name' => 'Trinketry', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->assignSkill($trinketry, 1, false)->getCharacter();
        $character->update(['gold_dust' => 9, 'copper_coins' => 100, 'shards' => 1000000, 'inventory_max' => 30]);
        $this->createItem(['name' => 'Dusty Trinket', 'type' => 'trinket', 'gold_dust_cost' => 10, 'copper_coin_cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batch = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['trinketry_mode' => 'experience'],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batch);

        $this->assertSame(BatchCraftingEndReason::TRINKETRY_INSUFFICIENT_CURRENCIES->value, $result->ended_reason);
        $this->assertSame(0, $result->failed_count);
        $this->assertSame(0, $result->skipped_count);
        $this->assertStringContainsString('Gold Dust required 10, available 9, missing 1', $result->progress['trinketry_end_message']);
        Event::assertDispatchedTimes(ServerMessageEvent::class, 1);
    }

    public function test_trinketry_stops_cleanly_for_insufficient_copper_coins(): void
    {
        Event::fake();
        $trinketry = $this->createGameSkill(['name' => 'Trinketry', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->assignSkill($trinketry, 1, false)->getCharacter();
        $character->update(['gold_dust' => 100, 'copper_coins' => 9, 'shards' => 1000000, 'inventory_max' => 30]);
        $this->createItem(['name' => 'Copper Trinket', 'type' => 'trinket', 'gold_dust_cost' => 10, 'copper_coin_cost' => 10, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batch = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['trinketry_mode' => 'experience'],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batch);

        $this->assertSame(BatchCraftingEndReason::TRINKETRY_INSUFFICIENT_CURRENCIES->value, $result->ended_reason);
        $this->assertSame(0, $result->failed_count);
        $this->assertSame(0, $result->skipped_count);
        $this->assertStringContainsString('Copper Coins required 10, available 9, missing 1', $result->progress['trinketry_end_message']);
        Event::assertDispatchedTimes(ServerMessageEvent::class, 1);
    }

    public function test_trinketry_reports_both_missing_currencies_and_ignores_high_shard_balance(): void
    {
        Event::fake();
        $trinketry = $this->createGameSkill(['name' => 'Trinketry', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->assignSkill($trinketry, 1, false)->getCharacter();
        $character->update(['gold_dust' => 1, 'copper_coins' => 2, 'shards' => 999999999, 'inventory_max' => 30]);
        $this->createItem(['name' => 'Dual Cost Trinket', 'type' => 'trinket', 'gold_dust_cost' => 10, 'copper_coin_cost' => 20, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $batch = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['trinketry_mode' => 'experience'],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batch);

        $this->assertSame(BatchCraftingEndReason::TRINKETRY_INSUFFICIENT_CURRENCIES->value, $result->ended_reason);
        $this->assertSame(0, $result->failed_count);
        $this->assertSame(0, $result->skipped_count);
        $this->assertStringContainsString('Gold Dust required 10, available 1, missing 9', $result->progress['trinketry_end_message']);
        $this->assertStringContainsString('Copper Coins required 20, available 2, missing 18', $result->progress['trinketry_end_message']);
        Event::assertDispatchedTimes(ServerMessageEvent::class, 1);
    }

    public function test_trinketry_destination_full_race_ends_cleanly_without_failure(): void
    {
        Event::fake();
        $trinketry = $this->createGameSkill(['name' => 'Trinketry', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->assignSkill($trinketry, 1, false)->getCharacter();
        $character->update(['gold_dust' => 100, 'copper_coins' => 100, 'inventory_max' => 30]);
        $this->createInventorySet(['character_id' => $character->id, 'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE, 'max_slots' => 10]);
        $item = $this->createItem(['name' => 'Racing Trinket', 'type' => 'trinket', 'gold_dust_cost' => 10, 'copper_coin_cost' => 20, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->instance(TrinketCraftingService::class, Mockery::mock(TrinketCraftingService::class, function ($mock) use ($item) {
            $mock->shouldReceive('fetchItemsToCraft')->once()->with(Mockery::type(Character::class), false)->andReturn([$item->toArray()]);
            $mock->shouldReceive('craftingCost')->once()->with(Mockery::type(Character::class), Mockery::type(Item::class))->andReturn([
                'item_id' => $item->id,
                'item_name' => $item->name,
                'gold_dust' => ['required' => 10, 'available' => 100, 'missing' => 0],
                'copper_coins' => ['required' => 20, 'available' => 100, 'missing' => 0],
            ]);
            $mock->shouldReceive('craftForBatch')->once()->andThrow(
                new BatchCraftingDestinationFullException('destination filled after precheck'),
            );
        }));
        $batch = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['trinketry_mode' => 'experience'],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batch);

        $this->assertSame(
            BatchCraftingEndReason::BATCH_CRAFTING_SET_FULL->value,
            $result->ended_reason,
            json_encode($result->progress),
        );
        $this->assertSame(0, $result->failed_count);
        $this->assertSame(0, $result->skipped_count);
    }

    public function test_trinketry_affordability_race_ends_cleanly_with_one_detailed_message(): void
    {
        Event::fake();
        $trinketry = $this->createGameSkill(['name' => 'Trinketry', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->assignSkill($trinketry, 1, false)->getCharacter();
        $character->update(['gold_dust' => 100, 'copper_coins' => 100, 'shards' => 1000000, 'inventory_max' => 30]);
        $this->createInventorySet(['character_id' => $character->id, 'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE, 'max_slots' => 10]);
        $item = $this->createItem(['name' => 'Affordability Race Trinket', 'type' => 'trinket', 'gold_dust_cost' => 10, 'copper_coin_cost' => 20, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->instance(TrinketCraftingService::class, Mockery::mock(TrinketCraftingService::class, function ($mock) use ($item) {
            $mock->shouldReceive('fetchItemsToCraft')->once()->andReturn([$item->toArray()]);
            $mock->shouldReceive('craftingCost')->once()->andReturn([
                'item_id' => $item->id,
                'item_name' => $item->name,
                'gold_dust' => ['required' => 10, 'available' => 100, 'missing' => 0],
                'copper_coins' => ['required' => 20, 'available' => 100, 'missing' => 0],
            ]);
            $mock->shouldReceive('craftForBatch')->once()->andReturn([
                'success' => false,
                'item' => null,
                'reason' => 'not_enough_currency',
                'destination' => null,
                'cost' => [
                    'item_id' => $item->id,
                    'item_name' => $item->name,
                    'gold_dust' => ['required' => 10, 'available' => 4, 'missing' => 6],
                    'copper_coins' => ['required' => 20, 'available' => 7, 'missing' => 13],
                ],
            ]);
        }));
        $batch = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['trinketry_mode' => 'experience'],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batch);

        $this->assertSame(BatchCraftingEndReason::TRINKETRY_INSUFFICIENT_CURRENCIES->value, $result->ended_reason);
        $this->assertSame(0, $result->failed_count);
        $this->assertSame(0, $result->skipped_count);
        $this->assertStringContainsString('Affordability Race Trinket', $result->progress['trinketry_end_message']);
        $this->assertStringContainsString('Gold Dust required 10, available 4, missing 6', $result->progress['trinketry_end_message']);
        $this->assertStringContainsString('Copper Coins required 20, available 7, missing 13', $result->progress['trinketry_end_message']);
        Event::assertDispatchedTimes(ServerMessageEvent::class, 1);
    }

    public function test_genuine_trinketry_failed_roll_remains_failure_and_can_continue(): void
    {
        Event::fake();
        $trinketry = $this->createGameSkill(['name' => 'Trinketry', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->assignSkill($trinketry, 1, false)->getCharacter();
        $character->update(['gold_dust' => 100, 'copper_coins' => 100, 'inventory_max' => 30]);
        $this->createInventorySet(['character_id' => $character->id, 'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE, 'max_slots' => 10]);
        $item = $this->createItem(['name' => 'Failed Roll Trinket', 'type' => 'trinket', 'gold_dust_cost' => 10, 'copper_coin_cost' => 20, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->instance(TrinketCraftingService::class, Mockery::mock(TrinketCraftingService::class, function ($mock) use ($item) {
            $mock->shouldReceive('fetchItemsToCraft')->times(6)->with(Mockery::type(Character::class), false)->andReturn([$item->toArray()]);
            $mock->shouldReceive('craftingCost')->times(6)->andReturn([
                'item_id' => $item->id,
                'item_name' => $item->name,
                'gold_dust' => ['required' => 10, 'available' => 100, 'missing' => 0],
                'copper_coins' => ['required' => 20, 'available' => 100, 'missing' => 0],
            ]);
            $mock->shouldReceive('craftForBatch')->times(6)->andReturn([
                'success' => false,
                'item' => null,
                'reason' => 'failed_roll',
                'destination' => null,
            ]);
        }));
        $batch = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['trinketry_mode' => 'experience'],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batch);

        $this->assertSame(6, $result->failed_count, json_encode($result->progress));
        $this->assertNull($result->ended_reason);
        $this->assertNull($result->completed_at);
    }

    public function test_craft_enchant_set_craft_phase_crafts_selected_item_id_instead_of_highest_craftable_item(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
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

    public function test_craft_enchant_set_craft_phase_falls_back_to_highest_craftable_item_when_no_selection_stored(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
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

    public function test_craft_enchant_set_enchant_phase_hard_stops_with_int_too_low_end_reason_when_planned_affix_requires_more_int_than_character_has(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
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

    public function test_craft_enchant_set_craft_phase_failed_attempt_does_not_advance_index_or_work_units(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
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

    public function test_craft_enchant_set_craft_phase_successful_entry_advances_index_and_work_units_by_one_each(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
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

    public function test_craft_enchant_set_enchant_phase_attempt_not_made_when_gold_insufficient_does_not_advance_index_or_work_units(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
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

    public function test_craft_enchant_set_enchant_phase_successful_completion_advances_index_and_work_units_by_one_not_two(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
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

    public function test_craft_enchant_set_enchant_phase_shattered_item_does_not_advance_index_decreases_surviving_count_and_switches_to_replacement_crafting(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
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

    public function test_craft_enchant_set_three_entry_fixture_reaches_full_work_unit_completion(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
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

    public function test_craft_enchant_set_enchant_phase_prefix_and_suffix_applied_counts_and_disposition_remain_unchanged(): void
    {
        $character = $this->character->givePlayerLocation()->getCharacter();
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

    public function test_craft_and_enchant_for_experience_resolves_exact_intended_auto_affix_and_stops_when_it_requires_too_much_int(): void
    {
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(400);
            })
        );
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
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

    public function test_craft_and_enchant_for_experience_persists_and_returns_exact_automatically_resolved_int_stop_affixes(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
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

    public function test_craft_and_enchant_for_experience_does_not_fall_back_to_lower_int_affix_when_highest_eligible_affix_is_too_high(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
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

    public function test_int_hard_stop_does_not_create_monitored_bug_report(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
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

    public function test_normal_non_int_enchant_failure_still_continues_as_before(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
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

    public function test_try_enchant_slot_cannot_bypass_int_check_when_used_by_standalone_enchant_set_path(): void
    {
        $character = $this->character->getCharacter();
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

    public function test_craft_set_processor_crafts_manually_selected_item_instead_of_highest(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
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

    public function test_craft_set_processor_stops_without_fallback_when_persisted_selected_item_is_unavailable(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
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

    public function test_craft_set_processor_resolves_the_same_selected_item_for_both_hand_keys(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
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

    public function test_craft_and_enchant_for_experience_processes_at_most_six_workflows_per_tick(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
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

    public function test_craft_and_enchant_for_experience_reaches_twenty_three_actions_across_four_chunked_six_six_six_five_ticks(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400, 'skill_bonus_per_level' => 1.0]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
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

    public function test_craft_and_enchant_for_experience_auto_selects_one_eligible_prefix_and_one_eligible_suffix(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400, 'skill_bonus_per_level' => 1.0]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
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

    public function test_craft_and_enchant_for_experience_enchanted_item_receives_both_prefix_and_suffix_ids(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400, 'skill_bonus_per_level' => 1.0]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
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

    public function test_craft_and_enchant_for_experience_action_log_lists_affix_names_in_prefix_then_suffix_order(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400, 'skill_bonus_per_level' => 1.0]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
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

        $this->assertSame($prefix->name.', '.$suffix->name, $enchantedEntry['enchant_affix_name']);
    }

    public function test_craft_and_enchant_for_experience_kept_item_server_message_contains_both_affix_names(): void
    {
        Event::fake([ServerMessageEvent::class]);
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400, 'skill_bonus_per_level' => 1.0]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
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
            return str_starts_with($event->message, 'Applied enchantment: '.$prefix->name.', '.$suffix->name.' to:');
        });
    }

    public function test_craft_and_enchant_for_experience_applies_only_one_affix_when_only_one_type_is_eligible(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400, 'skill_bonus_per_level' => 1.0]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
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

    public function test_craft_and_enchant_for_experience_never_selects_two_prefixes_when_no_suffix_exists(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400, 'skill_bonus_per_level' => 1.0]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
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

    public function test_craft_and_enchant_for_experience_never_selects_two_suffixes_when_no_prefix_exists(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400, 'skill_bonus_per_level' => 1.0]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
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

    public function test_craft_and_enchant_for_experience_explicit_single_affix_selection_overrides_automatic_double_selection(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400, 'skill_bonus_per_level' => 1.0]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
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

    public function test_craft_and_enchant_for_experience_int_check_evaluates_both_automatically_selected_affixes(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400, 'skill_bonus_per_level' => 1.0]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
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

    public function test_craft_and_enchant_for_experience_total_cost_includes_both_automatically_selected_affixes(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400, 'skill_bonus_per_level' => 1.0]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
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

    public function test_enchant_for_event_enchants_existing_event_items_before_crafting_fallback_set(): void
    {
        $character = $this->character->givePlayerLocation()->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $schedule = $this->createScheduledEvent(['event_type' => EventType::WINTER_EVENT, 'status' => ScheduledEventStatus::RUNNING, 'currently_running' => true]);
        $event = $this->createEvent(['type' => EventType::WINTER_EVENT, 'scheduled_event_id' => $schedule->id, 'current_event_goal_step' => GlobalEventSteps::ENCHANT, 'ends_at' => now()->addHour()]);
        $goal = $this->createGlobalEventGoal(['event_type' => $event->type, 'event_id' => $event->id, 'max_enchants' => 100, 'item_specialty_type_reward' => ItemSpecialtyType::HELL_FORGED->value]);
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

    public function test_enchant_for_event_falls_back_to_crafting_twenty_three_items_when_no_event_items_exist(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $armourCrafting = $this->createGameSkill(['name' => 'Armour Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $ringCrafting = $this->createGameSkill(['name' => 'Ring Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $spellCrafting = $this->createGameSkill(['name' => 'Spell Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character
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
        $this->createGlobalEventGoal(['event_type' => $event->type, 'event_id' => $event->id, 'max_enchants' => 100, 'item_specialty_type_reward' => ItemSpecialtyType::HELL_FORGED->value]);
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

    public function test_retained_alchemy_action_exposes_live_alchemy_bag_slot_and_specialized_details(): void
    {
        $character = $this->character->getCharacter();
        $character->update(['gold_dust' => 1000, 'shards' => 1000, 'alchemy_bag_limit' => 20]);
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ALCHEMY->value)->update(['level' => 10, 'xp' => 0, 'xp_max' => 100]);
        Item::where('type', 'alchemy')->update(['can_craft' => false]);
        $item = $this->createItem([
            'name' => 'Retained Specialized Alchemy Item',
            'type' => 'alchemy',
            'crafting_type' => 'alchemy',
            'can_craft' => true,
            'skill_level_required' => 1,
            'skill_level_trivial' => 400,
            'gold_dust_cost' => 1,
            'shards_cost' => 1,
            'lasts_for' => 17,
            'can_stack' => true,
            'gains_additional_level' => true,
            'xp_bonus' => 0.23,
            'increase_stat_by' => 0.14,
        ]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_mode' => 'amount', 'alchemy_amount' => 1, 'alchemy_amount_count' => 0, 'alchemy_item_id' => $item->id],
        ]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);
        $action = collect($result->action_log)->first(fn (array $entry) => isset($entry['kept_item']));
        $retainedSlotId = $action['kept_item']['alchemy_slot_id'];

        $this->assertSame(BatchCraftingEndReason::AMOUNT_REACHED->value, $result->ended_reason);
        $this->assertTrue($action['kept_item']['can_view']);
        $this->assertSame($retainedSlotId, $action['kept_item']['slot_id']);
        $this->assertTrue(AlchemyBagSlot::where('character_id', $character->id)->whereKey($retainedSlotId)->exists());
        $this->assertSame('alchemy', $action['kept_item']['full_item_details']['type']);
        $this->assertSame(17, $action['kept_item']['full_item_details']['lasts_for']);
        $this->assertTrue($action['kept_item']['full_item_details']['can_stack']);
        $this->assertTrue($action['kept_item']['full_item_details']['gain_additional_level']);
        $this->assertSame(0.23, $action['kept_item']['full_item_details']['xp_bonus']);
        $this->assertSame(0.14, $action['kept_item']['full_item_details']['stat_increase']);
    }

    public function test_removed_alchemy_action_keeps_specialized_snapshot_without_live_slot(): void
    {
        $character = $this->character->getCharacter();
        $character->update(['gold_dust' => 1000, 'shards' => 1000, 'alchemy_bag_limit' => 20]);
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ALCHEMY->value)->update(['level' => 10, 'xp' => 0, 'xp_max' => 100]);
        Item::where('type', 'alchemy')->update(['can_craft' => false]);
        $item = $this->createItem([
            'name' => 'Removed Specialized Alchemy Item',
            'type' => 'alchemy',
            'crafting_type' => 'alchemy',
            'can_craft' => true,
            'skill_level_required' => 1,
            'skill_level_trivial' => 400,
            'gold_dust_cost' => 1,
            'shards_cost' => 1,
            'lasts_for' => 29,
            'can_stack' => false,
            'gains_additional_level' => true,
            'xp_bonus' => 0.31,
            'increase_stat_by' => 0.16,
        ]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['alchemy_mode' => 'amount', 'alchemy_amount' => 1, 'alchemy_amount_count' => 0, 'alchemy_item_id' => $item->id],
        ]);
        $bagCountBefore = $character->getAlchemyBagCount();

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);
        $action = collect($result->action_log)->first(fn (array $entry) => isset($entry['destroyed_item']));
        $snapshot = $action['destroyed_item'];

        $this->assertSame(BatchCraftingEndReason::AMOUNT_REACHED->value, $result->ended_reason);
        $this->assertSame($bagCountBefore, $character->refresh()->getAlchemyBagCount());
        $this->assertFalse($snapshot['can_view']);
        $this->assertNull($snapshot['alchemy_slot_id']);
        $this->assertNull($snapshot['item_id_for_modal']);
        $this->assertNull($snapshot['slot_id_for_modal']);
        $this->assertSame('destroyed', $snapshot['status']);
        $this->assertSame('alchemy', $snapshot['full_item_details']['type']);
        $this->assertSame(29, $snapshot['full_item_details']['lasts_for']);
        $this->assertFalse($snapshot['full_item_details']['can_stack']);
        $this->assertTrue($snapshot['full_item_details']['gain_additional_level']);
        $this->assertSame(0.31, $snapshot['full_item_details']['xp_bonus']);
        $this->assertSame(0.16, $snapshot['full_item_details']['stat_increase']);
    }

    public function test_alchemy_use_now_emits_aggregate_used_message_for_usable_boon_item(): void
    {
        Event::fake([ServerMessageEvent::class]);
        Bus::fake([CharacterBoonJob::class]);
        $character = $this->character->getCharacter();
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

    public function test_alchemy_use_now_emits_aggregate_kept_message_for_kingdom_bomb_item(): void
    {
        Event::fake([ServerMessageEvent::class]);
        $character = $this->character->getCharacter();
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

    public function test_alchemy_use_now_kept_message_includes_link_metadata_when_item_remains_in_bag(): void
    {
        Event::fake([ServerMessageEvent::class]);
        $character = $this->character->getCharacter();
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

    public function test_alchemy_use_now_emits_per_operation_messages_when_ten_boon_cap_is_reached(): void
    {
        Event::fake([ServerMessageEvent::class]);
        Bus::fake([CharacterBoonJob::class]);
        $character = $this->character->getCharacter();
        $character->update(['gold_dust' => 1000]);
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ALCHEMY->value)->update(['level' => 50, 'xp' => 0, 'xp_max' => 100]);
        $existingBoonItem = $this->createItem(['name' => 'Existing Boon Item', 'type' => 'alchemy', 'usable' => true, 'lasts_for' => 60, 'can_stack' => true]);
        $this->createCharacterBoon([
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

    public function test_alchemy_use_now_action_log_still_records_per_item_disposition_details(): void
    {
        Event::fake([ServerMessageEvent::class]);
        Bus::fake([CharacterBoonJob::class]);
        $character = $this->character->getCharacter();
        $character->update(['gold_dust' => 1000]);
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ALCHEMY->value)->update(['level' => 50, 'xp' => 0, 'xp_max' => 100]);
        $existingBoonItem = $this->createItem(['name' => 'Action Log Existing Boon', 'type' => 'alchemy', 'usable' => true, 'lasts_for' => 60, 'can_stack' => true]);
        $this->createCharacterBoon([
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

    public function test_craft_experience_keep_disposition_checks_decreasing_crafed_items_set_capacity_across_six_six_six_five_chunks(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400, 'skill_bonus_per_level' => 1.0]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
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

    public function test_craft_experience_keep_disposition_stops_before_crafting_when_crafted_items_set_has_insufficient_capacity_for_a_new_cycle(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400, 'skill_bonus_per_level' => 1.0]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
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

    public function test_craft_experience_keep_disposition_stops_next_chunk_when_capacity_is_consumed_between_chunks(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400, 'skill_bonus_per_level' => 1.0]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
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

    public function test_craft_and_enchant_experience_keep_disposition_correctly_allows_second_chunk_when_seventeen_slots_remain(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400, 'skill_bonus_per_level' => 1.0]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 5, false)->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 100000, 'inventory_max' => 200]);
        // Fifteen distinct catalog items: the EnchantingService mock below mutates
        // whichever item it receives directly (no clone), so a single reused item
        // would stop matching "craftable" after its first enchant and starve later
        // chunk attempts. Distinct items keep every attempt finding a fresh target.
        $this->createDistinctlyNamedItems(15, 'Craft And Enchant Capacity Dagger', ['type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
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

    public function test_craft_enchant_set_enchant_phase_partial_affix_result_is_discarded_not_completed_and_switches_to_replacement_crafting(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
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

    public function test_craft_enchant_set_replacement_crafting_failure_keeps_same_replacement_key_and_phase(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
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

    public function test_craft_enchant_set_replacement_crafting_success_restores_surviving_count_and_returns_to_enchanting_at_same_index(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
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

    public function test_craft_and_enchant_amount_shattered_item_does_not_increment_completed_count_and_returns_to_craft_phase(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 100000, 'inventory_max' => 30, 'int' => 100]);
        $item = $this->createItem(['name' => 'Amount Shatter Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $outputSet = $this->createInventorySet(['character_id' => $character->id, 'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE]);
        $outputSlot = $this->createInventorySetSlot(['inventory_set_id' => $outputSet->id, 'item_id' => $item->id]);
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

    public function test_craft_and_enchant_amount_partial_affix_result_is_discarded_and_does_not_increment_completed_count(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['gold' => 100000, 'inventory_max' => 30, 'int' => 100]);
        $item = $this->createItem(['name' => 'Amount Partial Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $outputSet = $this->createInventorySet(['character_id' => $character->id, 'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE]);
        $outputSlot = $this->createInventorySetSlot(['inventory_set_id' => $outputSet->id, 'item_id' => $item->id]);
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

    public function test_craft_and_enchant_experience_partial_affix_result_does_not_keep_the_item(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
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

    public function test_craft_amount_with_inventory_destination_succeeds_when_crafted_items_set_is_full(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
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

    public function test_craft_amount_with_inventory_set_destination_succeeds_when_crafted_items_set_is_full(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = $this->character->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
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

    public function test_craft_set_with_inventory_destination_is_not_blocked_by_full_crafted_items_set(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = $this->character->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
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

    public function test_craft_and_enchant_amount_craft_step_with_inventory_destination_is_not_blocked_by_full_crafted_items_set(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = $this->character->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
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

    public function test_craft_amount_with_crafted_items_set_destination_remains_blocked_when_full(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = $this->character->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
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

    public function test_craft_amount_with_inventory_destination_returns_no_inventory_space_when_inventory_is_full(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = $this->character->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
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

    public function test_craft_amount_with_inventory_set_destination_returns_craft_set_full_when_selected_set_is_full(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = $this->character->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
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

    public function test_craft_amount_with_equipped_output_set_returns_target_set_changed(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = $this->character->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
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

    public function test_craft_and_enchant_amount_enchant_not_attempted_keeps_pending_item_and_phase(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
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

    public function test_craft_and_enchant_amount_exact_prefix_success_commits_and_advances_index_once(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 100000, 'inventory_max' => 30, 'int' => 100]);
        $item = $this->createItem(['name' => 'Amount Exact Success Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $outputSet = $this->createInventorySet(['character_id' => $character->id, 'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE]);
        $outputSlot = $this->createInventorySetSlot(['inventory_set_id' => $outputSet->id, 'item_id' => $item->id]);
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

    public function test_craft_and_enchant_amount_int_too_low_preserves_pending_item_and_enchant_phase(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 100000, 'inventory_max' => 30, 'int' => 1]);
        $item = $this->createItem(['name' => 'Amount Int Too Low Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $outputSet = $this->createInventorySet(['character_id' => $character->id, 'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE]);
        $outputSlot = $this->createInventorySetSlot(['inventory_set_id' => $outputSet->id, 'item_id' => $item->id]);
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

    public function test_craft_and_enchant_amount_missing_pending_item_returns_to_craft_phase_without_incrementing_progress(): void
    {
        $character = $this->character->givePlayerLocation()->getCharacter();
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

    public function test_craft_enchant_set_enchant_phase_null_stored_item_enters_replacement_crafting_without_advancing(): void
    {
        $character = $this->character->givePlayerLocation()->getCharacter();
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

    public function test_craft_enchant_set_enchant_phase_missing_item_model_enters_replacement_crafting_without_advancing(): void
    {
        $character = $this->character->givePlayerLocation()->getCharacter();
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

    public function test_craft_enchant_set_enchant_phase_empty_affix_plan_ends_failed_without_advancing(): void
    {
        $character = $this->character->givePlayerLocation()->getCharacter();
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

    public function test_craft_amount_commit_fails_when_item_model_is_missing(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = $this->character->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
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

    public function test_craft_amount_crafted_items_set_non_set_full_failure_returns_false_and_does_not_advance(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = $this->character->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
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

    public function test_craft_amount_inventory_commit_creates_exactly_one_slot_on_success(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = $this->character->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
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

    public function test_craft_amount_inventory_set_commit_creates_exactly_one_slot_on_success(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = $this->character->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
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

    public function test_craft_amount_crafted_items_set_commit_creates_exactly_one_slot_on_success(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = $this->character->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
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

    public function test_craft_and_enchant_amount_missing_retained_destination_fails_and_cleans_orphan_item(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
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

    public function test_craft_and_enchant_amount_cannot_assign_inventory_destination_after_crafting_returns(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
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

    public function test_craft_enchant_set_replacement_success_removes_lost_key_and_restores_progress_once(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
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

    public function test_craft_amount_inventory_failure_leaves_kept_count_at_zero(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = $this->character->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
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

    public function test_craft_amount_inventory_set_failure_leaves_kept_count_at_zero(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = $this->character->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
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

    public function test_craft_amount_crafted_items_set_full_failure_leaves_kept_count_at_zero(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = $this->character->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
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

    public function test_craft_amount_crafted_items_set_non_full_failure_leaves_kept_count_at_zero(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = $this->character->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
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

    public function test_craft_amount_mixed_success_then_capacity_failure_across_two_ticks_keeps_exactly_one_kept_count(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = $this->character->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
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

    public function test_craft_set_commit_failure_leaves_kept_count_at_zero(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 5]);
        $character = $this->character->assignSkill($weaponCrafting, 5, false, ['xp' => 100, 'xp_max' => 100])->getCharacter();
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

    public function test_craft_experience_commit_failure_leaves_kept_count_at_zero(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
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

    public function test_trinketry_commit_failure_leaves_kept_count_at_zero(): void
    {
        $trinketry = $this->createGameSkill(['name' => 'Trinketry', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->givePlayerLocation()->assignSkill($trinketry, 1, false)->getCharacter();
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

    public function test_craft_and_enchant_experience_successful_keep_applies_disposition_exactly_once_with_exactly_one_kept_count(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
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

    public function test_craft_and_enchant_experience_enchant_not_attempted_discards_item_without_disposition_or_destination_write(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
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

    public function test_craft_and_enchant_experience_int_too_low_stops_without_disposition_or_destination_write(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
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

    public function test_craft_and_enchant_experience_shattered_destroys_item_without_disposition_or_destination_write(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
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

    public function test_craft_and_enchant_experience_partial_prefix_only_discards_item_and_requires_replacement_without_disposition(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
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

    public function test_craft_and_enchant_experience_partial_suffix_only_discards_item_and_requires_replacement_without_disposition(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
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

    public function test_craft_and_enchant_experience_exact_success_with_failed_commit_zeroes_enchanted_and_kept_counts(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
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

    public function test_craft_and_enchant_experience_non_retained_sell_disposition_succeeds_without_commit_or_kept_count(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
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

    public function test_craft_enchant_set_craft_phase_initial_craft_adds_counted_key_and_increments_surviving_count(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
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

    public function test_craft_enchant_set_craft_phase_duplicate_key_does_not_double_count_and_discards_duplicate_item(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
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

    public function test_craft_enchant_set_null_stored_item_loss_does_not_decrement_when_key_was_never_counted(): void
    {
        $character = $this->character->givePlayerLocation()->getCharacter();
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

    public function test_craft_enchant_set_missing_item_model_loss_decrements_counted_key_exactly_once(): void
    {
        $character = $this->character->givePlayerLocation()->getCharacter();
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

    public function test_craft_enchant_set_shattered_loss_decrements_counted_key_exactly_once(): void
    {
        $character = $this->character->givePlayerLocation()->getCharacter();
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

    public function test_craft_enchant_set_partial_affix_discarded_loss_decrements_counted_key_exactly_once(): void
    {
        $character = $this->character->givePlayerLocation()->getCharacter();
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

    public function test_craft_enchant_set_loss_is_idempotent_and_does_not_double_decrement_already_lost_key(): void
    {
        $character = $this->character->givePlayerLocation()->getCharacter();
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

    public function test_craft_enchant_set_replacement_valid_lost_state_restores_counted_key_exactly_once(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['gold' => 100000, 'inventory_max' => 30]);
        $replacementItem = $this->createItem(['name' => 'Counted Key Replacement Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
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

    public function test_craft_enchant_set_replacement_rejects_key_not_in_lost_state(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['gold' => 100000, 'inventory_max' => 30]);
        $replacementItem = $this->createItem(['name' => 'Not Lost Replacement Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
            })
        );
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

    public function test_craft_enchant_set_replacement_rejects_already_counted_key_preventing_double_restoration(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $character->update(['gold' => 100000, 'inventory_max' => 30]);
        $replacementItem = $this->createItem(['name' => 'Already Counted Replacement Dagger', 'type' => 'dagger', 'crafting_type' => 'dagger', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
            })
        );
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

    public function test_craft_amount_produces_no_more_than_six_actions_in_one_process_call_and_continues_on_next_call(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
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

    public function test_craft_and_enchant_amount_produces_no_more_than_six_phase_operation_actions_and_does_not_count_them_as_completed_items(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
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

    public function test_alchemy_amount_produces_no_more_than_six_actions_and_failed_attempts_do_not_advance_completion(): void
    {
        $character = $this->character->getCharacter();
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

    public function test_holy_oils_selected_produces_no_more_than_six_application_actions_and_persists_selected_items_across_calls(): void
    {
        $character = $this->character->getCharacter();
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

    public function test_event_enchant_single_selected_prefix_succeeds_and_increments_event_contribution_once(): void
    {
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
            })
        );
        $character = $this->character->givePlayerLocation()->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $schedule = $this->createScheduledEvent(['event_type' => EventType::WINTER_EVENT, 'status' => ScheduledEventStatus::RUNNING, 'currently_running' => true]);
        $event = $this->createEvent(['type' => EventType::WINTER_EVENT, 'scheduled_event_id' => $schedule->id, 'current_event_goal_step' => GlobalEventSteps::ENCHANT, 'ends_at' => now()->addHour()]);
        $goal = $this->createGlobalEventGoal(['event_type' => $event->type, 'event_id' => $event->id, 'max_enchants' => 100, 'item_specialty_type_reward' => ItemSpecialtyType::HELL_FORGED->value]);
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

    public function test_event_enchant_both_prefix_and_suffix_succeed_applies_exact_affixes_and_increments_event_contribution_once(): void
    {
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
            })
        );
        $character = $this->character->givePlayerLocation()->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $schedule = $this->createScheduledEvent(['event_type' => EventType::WINTER_EVENT, 'status' => ScheduledEventStatus::RUNNING, 'currently_running' => true]);
        $event = $this->createEvent(['type' => EventType::WINTER_EVENT, 'scheduled_event_id' => $schedule->id, 'current_event_goal_step' => GlobalEventSteps::ENCHANT, 'ends_at' => now()->addHour()]);
        $goal = $this->createGlobalEventGoal(['event_type' => $event->type, 'event_id' => $event->id, 'max_enchants' => 100, 'item_specialty_type_reward' => ItemSpecialtyType::HELL_FORGED->value]);
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

    public function test_event_enchant_shattering_during_second_affix_destroys_item_without_type_error_and_does_not_increment_enchanted_count(): void
    {
        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function ($mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(50);
                $mock->shouldReceive('characterRoll')->andReturn(100, 1);
            })
        );
        $character = $this->character->givePlayerLocation()->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $schedule = $this->createScheduledEvent(['event_type' => EventType::WINTER_EVENT, 'status' => ScheduledEventStatus::RUNNING, 'currently_running' => true]);
        $event = $this->createEvent(['type' => EventType::WINTER_EVENT, 'scheduled_event_id' => $schedule->id, 'current_event_goal_step' => GlobalEventSteps::ENCHANT, 'ends_at' => now()->addHour()]);
        $goal = $this->createGlobalEventGoal(['event_type' => $event->type, 'event_id' => $event->id, 'max_enchants' => 100, 'item_specialty_type_reward' => ItemSpecialtyType::HELL_FORGED->value]);
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

    public function test_event_enchant_surviving_item_missing_one_selected_affix_is_reported_failed_and_left_available(): void
    {
        $character = $this->character->givePlayerLocation()->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ENCHANTING->value)->update(['level' => 10, 'xp' => 0, 'xp_max' => 100]);
        $character->update(['gold' => 1000, 'inventory_max' => 10]);
        $schedule = $this->createScheduledEvent(['event_type' => EventType::WINTER_EVENT, 'status' => ScheduledEventStatus::RUNNING, 'currently_running' => true]);
        $event = $this->createEvent(['type' => EventType::WINTER_EVENT, 'scheduled_event_id' => $schedule->id, 'current_event_goal_step' => GlobalEventSteps::ENCHANT, 'ends_at' => now()->addHour()]);
        $goal = $this->createGlobalEventGoal(['event_type' => $event->type, 'event_id' => $event->id, 'max_enchants' => 100, 'item_specialty_type_reward' => ItemSpecialtyType::HELL_FORGED->value]);
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
                $mock->shouldReceive('enchant')->andReturnUsing(function ($character, $params, $slot, $cost) {
                    $slot->item->update(['item_prefix_id' => $params['affix_ids'][0]]);

                    return false;
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

    public function test_missing_craft_and_enchant_experience_item_is_reported_as_failed_not_silently_dropped(): void
    {
        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = $this->character
            ->givePlayerLocation()
            ->assignSkill($weaponCrafting, 10, false, ['xp' => 0, 'xp_max' => 100])
            ->getCharacter();
        $character->update(['gold' => 1000, 'inventory_max' => 200]);
        $craftableItem = $this->createItem(['name' => 'Missing Item Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 400]);
        $craftForBatchCalls = 0;
        $this->instance(
            CraftingService::class,
            Mockery::mock(CraftingService::class, function ($mock) use (&$craftForBatchCalls, $craftableItem) {
                $mock->makePartial()->shouldReceive('fetchCraftableItems')->andReturn(new \Illuminate\Database\Eloquent\Collection([$craftableItem]));
                $mock->makePartial()->shouldReceive('craftForBatch')->andReturnUsing(function ($character, $item, $craftingType, $suppress = false, $destinationCreator = null) use (&$craftForBatchCalls) {
                    $craftForBatchCalls++;

                    if ($craftForBatchCalls === 1) {
                        $clone = $item->replicate();
                        $clone->name = 'Missing Item Clone';
                        $clone->save();

                        $destination = $destinationCreator ? $destinationCreator($clone) : null;

                        $clone->delete();

                        return ['success' => true, 'item' => $clone, 'destination' => $destination];
                    }

                    return ['success' => false, 'item' => null, 'destination' => null];
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
        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertGreaterThan(0, $result->failed_count);
        $this->assertTrue(collect($result->action_log)->contains(fn (array $entry) => ($entry['status'] ?? null) === 'failed'));
    }

    public function test_alchemy_amount_returns_amount_reached_on_final_successful_item(): void
    {
        $character = $this->character->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ALCHEMY->value)->update(['level' => 2]);
        $character->update(['gold_dust' => 10, 'shards' => 10, 'alchemy_bag_limit' => 10]);
        $item = $this->createItem(['name' => 'Final Amount Alchemy Item', 'type' => 'alchemy', 'crafting_type' => 'alchemy', 'can_craft' => true, 'gold_dust_cost' => 1, 'shards_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $character->user_id, 'batch_type' => BatchCraftingType::ALCHEMY->value, 'disposition' => BatchCraftingDisposition::KEEP->value, 'progress' => ['alchemy_mode' => 'amount', 'alchemy_amount' => 1, 'alchemy_amount_count' => 0, 'alchemy_item_id' => $item->id]]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::AMOUNT_REACHED->value, $result->ended_reason);
        $this->assertSame(1, $result->progress['alchemy_amount_count']);
        $this->assertSame('alchemy', $result->action_log[0]['action_type']);
    }

    public function test_alchemy_amount_final_item_may_consume_remaining_gold_dust_and_end_amount_reached(): void
    {
        $character = $this->character->getCharacter();
        $character->skills->first(fn ($skill) => $skill->baseSkill->type === SkillTypeValue::ALCHEMY->value)->update(['level' => 2]);
        $character->update(['gold_dust' => 1, 'shards' => 10, 'alchemy_bag_limit' => 10]);
        $item = $this->createItem(['name' => 'Remaining Dust Alchemy Item', 'type' => 'alchemy', 'crafting_type' => 'alchemy', 'can_craft' => true, 'gold_dust_cost' => 1, 'shards_cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 1]);
        $batchCrafting = $this->createBatchCrafting(['character_id' => $character->id, 'user_id' => $character->user_id, 'batch_type' => BatchCraftingType::ALCHEMY->value, 'disposition' => BatchCraftingDisposition::KEEP->value, 'progress' => ['alchemy_mode' => 'amount', 'alchemy_amount' => 1, 'alchemy_amount_count' => 0, 'alchemy_item_id' => $item->id]]);

        $result = resolve(BatchCraftingService::class)->process($batchCrafting);

        $this->assertSame(BatchCraftingEndReason::AMOUNT_REACHED->value, $result->ended_reason);
        $this->assertSame(0, $character->refresh()->gold_dust);
        $this->assertSame(1, $result->crafted_count);
    }

    public function test_alchemy_amount_running_out_of_gold_dust_before_requested_amount_returns_currency_reason(): void
    {
        $character = $this->character->getCharacter();
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
