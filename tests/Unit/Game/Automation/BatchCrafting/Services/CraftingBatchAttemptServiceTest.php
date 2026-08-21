<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingActionStatus;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingEndReason;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingOutputDestination;
use App\Game\Automation\BatchCrafting\Services\CraftingBatchAttemptService;
use App\Game\Character\CharacterInventory\Services\BatchCraftingSetService;
use App\Game\Skills\Services\SkillCheckService;
use App\Game\Skills\Values\SkillTypeValue;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateInventorySets;
use Tests\Traits\CreateItem;

class CraftingBatchAttemptServiceTest extends TestCase
{
    use CreateGameSkill, CreateInventorySets, CreateItem, RefreshDatabase;

    private ?Character $character;

    private ?Item $item;

    private ?CraftingBatchAttemptService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($weaponCrafting, 10, false)->getCharacter();
        $this->character->update(['gold' => 100000, 'inventory_max' => 30]);

        $this->item = $this->createItem(['name' => 'Attempt Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 5, 'skill_level_required' => 0, 'skill_level_trivial' => 100]);

        $this->service = resolve(CraftingBatchAttemptService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->item = null;
        $this->service = null;
    }

    public function test_gold_cost_for_returns_the_class_adjusted_cost(): void
    {
        $result = $this->service->goldCostFor($this->character, $this->item);

        $this->assertSame($this->item->cost, $result);
    }

    public function test_attempt_sell_disposition_returns_sold_result_with_gold_gained(): void
    {
        $this->app->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
            })
        );

        $result = resolve(CraftingBatchAttemptService::class)->attempt($this->character, BatchCraftingDisposition::SELL, $this->item, 'dagger', 5, null);

        $this->assertSame(BatchCraftingActionStatus::SOLD, $result->actionStatus());
        $this->assertGreaterThanOrEqual(0, $result->goldGained());
    }

    public function test_attempt_destroy_disposition_returns_destroyed_result(): void
    {
        $this->app->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
            })
        );

        $result = resolve(CraftingBatchAttemptService::class)->attempt($this->character, BatchCraftingDisposition::DESTROY, $this->item, 'dagger', 5, null);

        $this->assertSame(BatchCraftingActionStatus::DESTROYED, $result->actionStatus());
    }

    public function test_attempt_keep_disposition_uses_the_placement_callback(): void
    {
        $this->app->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->andReturn(1);
                $mock->shouldReceive('characterRoll')->andReturn(100);
            })
        );

        $service = resolve(CraftingBatchAttemptService::class);
        $placeItem = $service->resolveRetainedDestination($this->character, BatchCraftingOutputDestination::CRAFTED_ITEMS_SET->value);

        $result = $service->attempt($this->character, BatchCraftingDisposition::KEEP, $this->item, 'dagger', 5, $placeItem);

        $this->assertSame(BatchCraftingActionStatus::KEPT, $result->actionStatus());
        $batchCraftingSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($this->character);
        $this->assertSame(1, $batchCraftingSet->slots()->where('item_id', $this->item->id)->count());
    }

    public function test_resolve_retained_destination_for_inventory_returns_a_placement_callback(): void
    {
        $result = $this->service->resolveRetainedDestination($this->character, BatchCraftingOutputDestination::INVENTORY->value);

        $this->assertInstanceOf(Closure::class, $result);

        $placed = $result($this->item);
        $this->assertSame(BatchCraftingOutputDestination::INVENTORY->value, $placed['destination']);
    }

    public function test_resolve_retained_destination_for_inventory_returns_no_inventory_space_when_full(): void
    {
        $this->character->update(['inventory_max' => 0]);
        $this->character->inventory->slots()->create(['item_id' => $this->item->id, 'inventory_id' => $this->character->inventory->id]);

        $result = $this->service->resolveRetainedDestination($this->character->refresh(), BatchCraftingOutputDestination::INVENTORY->value);

        $this->assertSame(BatchCraftingEndReason::NO_INVENTORY_SPACE, $result);
    }

    public function test_resolve_retained_destination_for_crafted_items_set_returns_a_placement_callback(): void
    {
        $result = $this->service->resolveRetainedDestination($this->character, BatchCraftingOutputDestination::CRAFTED_ITEMS_SET->value);

        $this->assertInstanceOf(Closure::class, $result);

        $placed = $result($this->item);
        $this->assertSame(BatchCraftingOutputDestination::CRAFTED_ITEMS_SET->value, $placed['destination']);
    }

    public function test_resolve_retained_destination_for_crafted_items_set_returns_set_full_when_no_capacity(): void
    {
        $batchCraftingSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($this->character);
        $batchCraftingSet->update(['max_slots' => 0]);

        $result = $this->service->resolveRetainedDestination($this->character, BatchCraftingOutputDestination::CRAFTED_ITEMS_SET->value);

        $this->assertSame(BatchCraftingEndReason::BATCH_CRAFTING_SET_FULL, $result);
    }

    public function test_resolve_retained_destination_for_inventory_set_returns_a_placement_callback(): void
    {
        $targetSet = $this->createInventorySet(['character_id' => $this->character->id, 'is_equipped' => false]);

        $result = $this->service->resolveRetainedDestination($this->character, BatchCraftingOutputDestination::INVENTORY_SET->value, $targetSet->id);

        $this->assertInstanceOf(Closure::class, $result);

        $placed = $result($this->item);
        $this->assertSame(BatchCraftingOutputDestination::INVENTORY_SET->value, $placed['destination']);
        $this->assertSame(1, $targetSet->slots()->where('item_id', $this->item->id)->count());
    }

    public function test_resolve_retained_destination_for_inventory_set_returns_craft_set_full_when_the_set_no_longer_exists(): void
    {
        $result = $this->service->resolveRetainedDestination($this->character, BatchCraftingOutputDestination::INVENTORY_SET->value, 999999);

        $this->assertSame(BatchCraftingEndReason::CRAFT_SET_FULL, $result);
    }

    public function test_resolve_retained_destination_for_inventory_set_returns_craft_set_full_without_a_set_id(): void
    {
        $result = $this->service->resolveRetainedDestination($this->character, BatchCraftingOutputDestination::INVENTORY_SET->value);

        $this->assertSame(BatchCraftingEndReason::CRAFT_SET_FULL, $result);
    }

    public function test_translate_failure_maps_not_enough_gold_to_no_gold_end_reason(): void
    {
        $result = $this->service->translateFailure('not_enough_gold', 0);

        $this->assertSame(BatchCraftingEndReason::NO_GOLD, $result->endReason());
    }

    public function test_translate_failure_maps_skill_too_low_to_maxed_or_nothing_left(): void
    {
        $result = $this->service->translateFailure('skill_too_low', 0);

        $this->assertSame(BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT, $result->endReason());
    }

    public function test_translate_failure_maps_failed_roll_to_a_failed_result_without_ending(): void
    {
        $result = $this->service->translateFailure('failed_roll', 5);

        $this->assertSame(BatchCraftingActionStatus::FAILED, $result->actionStatus());
        $this->assertNull($result->endReason());
    }

    public function test_translate_failure_maps_destination_failed_to_a_failed_and_ended_result(): void
    {
        $result = $this->service->translateFailure('destination_failed', 5);

        $this->assertSame(BatchCraftingActionStatus::FAILED, $result->actionStatus());
        $this->assertSame(BatchCraftingEndReason::FAILED, $result->endReason());
    }

    public function test_sell_for_displacement_credits_gold_and_returns_the_amount_gained(): void
    {
        $goldBefore = $this->character->gold;

        $goldGained = $this->service->sellForDisplacement($this->character, $this->item);

        $this->assertSame($goldBefore + $goldGained, $this->character->refresh()->gold);
    }

    public function test_destroy_for_displacement_does_not_change_gold(): void
    {
        $goldBefore = $this->character->gold;

        $this->service->destroyForDisplacement($this->character, $this->item);

        $this->assertSame($goldBefore, $this->character->refresh()->gold);
    }
}
