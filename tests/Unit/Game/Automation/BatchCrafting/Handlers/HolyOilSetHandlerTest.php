<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Handlers;

use App\Flare\Models\Character;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingEndReason;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Enums\HolyOilTargetKind;
use App\Game\Automation\BatchCrafting\Handlers\HolyOilSetHandler;
use App\Game\Automation\BatchCrafting\Services\HolyOilOilPoolResolver;
use App\Game\Automation\BatchCrafting\Values\HolyOilApplicationPlanEntry;
use App\Game\Core\Currency\Services\CurrencyLimit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateAlchemyBagSlot;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateInventorySets;
use Tests\Traits\CreateItem;

class HolyOilSetHandlerTest extends TestCase
{
    use CreateAlchemyBagSlot, CreateBatchCrafting, CreateInventorySets, CreateItem, RefreshDatabase;

    private ?Character $character;

    private ?HolyOilSetHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $this->character->update(['gold_dust' => CurrencyLimit::MAX_GOLD_DUST]);
        $this->character = $this->character->refresh();

        $this->handler = resolve(HolyOilSetHandler::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->handler = null;
    }

    public function test_handle_ends_with_no_holy_oil_targets_when_set_no_longer_exists(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['holy_oils_mode' => 'inventory_set', 'inventory_set_id' => 999999, 'inventory_set_name' => 'Set 1', 'oil_slot_ids' => [], 'plan' => [['target_kind' => 'set_slot', 'target_slot_id' => 1, 'target_item_id' => 1]], 'plan_index' => 0, 'current_target_slot_id' => 1, 'current_target_item_id' => null, 'current_target_item_name' => null, 'current_oil_item_id' => null, 'current_oil_item_name' => null, 'current_holy_stacks' => null, 'max_holy_stacks' => null, 'applications_completed' => 0],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertSame(BatchCraftingEndReason::NO_HOLY_OIL_TARGETS, $result->endReason());
    }

    public function test_handle_applies_oil_to_set_slot_and_completes_the_plan(): void
    {
        $targetItem = $this->createItem(['type' => 'weapon', 'holy_stacks' => 1]);
        $oil = $this->createItem(['type' => 'alchemy', 'holy_level' => 1, 'can_use_on_other_items' => true]);

        $set = $this->createInventorySet(['character_id' => $this->character->id]);
        $setSlot = $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $targetItem->id]);
        $oilSlot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $this->character->alchemyBag->id,
            'character_id' => $this->character->id,
            'item_id' => $oil->id,
            'amount' => 3,
        ]);

        $plan = [(new HolyOilApplicationPlanEntry(HolyOilTargetKind::SET_SLOT, $setSlot->id, $targetItem->id))->toArray()];

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['holy_oils_mode' => 'inventory_set', 'inventory_set_id' => $set->id, 'inventory_set_name' => $set->name ?? ('Set '.$set->id), 'oil_slot_ids' => [$oilSlot->id], 'plan' => $plan, 'plan_index' => 0, 'current_target_slot_id' => $setSlot->id, 'current_target_item_id' => null, 'current_target_item_name' => null, 'current_oil_item_id' => null, 'current_oil_item_name' => null, 'current_holy_stacks' => null, 'max_holy_stacks' => null, 'applications_completed' => 0],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertSame(BatchCraftingEndReason::AMOUNT_REACHED, $result->endReason());
        $this->assertTrue($result->didCraft());
        $this->assertEquals(2, $oilSlot->refresh()->amount);
    }

    public function test_handle_returns_amount_reached_when_the_plan_is_already_exhausted(): void
    {
        $set = $this->createInventorySet(['character_id' => $this->character->id]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['holy_oils_mode' => 'inventory_set', 'inventory_set_id' => $set->id, 'inventory_set_name' => $set->name ?? ('Set '.$set->id), 'oil_slot_ids' => [], 'plan' => [], 'plan_index' => 0, 'current_target_slot_id' => null, 'current_target_item_id' => null, 'current_target_item_name' => null, 'current_oil_item_id' => null, 'current_oil_item_name' => null, 'current_holy_stacks' => null, 'max_holy_stacks' => null, 'applications_completed' => 0],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertSame(BatchCraftingEndReason::AMOUNT_REACHED, $result->endReason());
    }

    public function test_handle_skips_trinket_target_and_advances_to_the_next_target(): void
    {
        $trinketItem = $this->createItem(['type' => 'trinket', 'holy_stacks' => 5]);
        $weaponItem = $this->createItem(['type' => 'weapon', 'holy_stacks' => 5]);

        $set = $this->createInventorySet(['character_id' => $this->character->id]);
        $trinketSlot = $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $trinketItem->id]);
        $weaponSlot = $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $weaponItem->id]);

        $plan = [
            (new HolyOilApplicationPlanEntry(HolyOilTargetKind::SET_SLOT, $trinketSlot->id, $trinketItem->id))->toArray(),
            (new HolyOilApplicationPlanEntry(HolyOilTargetKind::SET_SLOT, $weaponSlot->id, $weaponItem->id))->toArray(),
        ];

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::SELL->value,
            'progress' => ['holy_oils_mode' => 'inventory_set', 'inventory_set_id' => $set->id, 'inventory_set_name' => $set->name ?? ('Set '.$set->id), 'oil_slot_ids' => [], 'plan' => $plan, 'plan_index' => 0, 'current_target_slot_id' => $trinketSlot->id, 'current_target_item_id' => null, 'current_target_item_name' => null, 'current_oil_item_id' => null, 'current_oil_item_name' => null, 'current_holy_stacks' => null, 'max_holy_stacks' => null, 'applications_completed' => 0],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertNull($result->endReason());
        $this->assertFalse($result->didCraft());
        $this->assertSame(1, $batchCrafting->refresh()->progress['plan_index']);
        $this->assertSame($weaponSlot->id, $batchCrafting->fresh()->progress['current_target_slot_id']);
    }

    public function test_handle_completes_a_set_target_that_is_already_at_capacity_when_the_tick_starts(): void
    {
        $targetItem = $this->createItem(['type' => 'weapon', 'holy_stacks' => 0]);
        $set = $this->createInventorySet(['character_id' => $this->character->id]);
        $setSlot = $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $targetItem->id]);

        $plan = [(new HolyOilApplicationPlanEntry(HolyOilTargetKind::SET_SLOT, $setSlot->id, $targetItem->id))->toArray()];

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['holy_oils_mode' => 'inventory_set', 'inventory_set_id' => $set->id, 'inventory_set_name' => $set->name ?? ('Set '.$set->id), 'oil_slot_ids' => [], 'plan' => $plan, 'plan_index' => 0, 'current_target_slot_id' => $setSlot->id, 'current_target_item_id' => null, 'current_target_item_name' => null, 'current_oil_item_id' => null, 'current_oil_item_name' => null, 'current_holy_stacks' => null, 'max_holy_stacks' => null, 'applications_completed' => 0],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertSame(BatchCraftingEndReason::AMOUNT_REACHED, $result->endReason());
        $this->assertTrue($result->didCraft());
        $this->assertEquals(0, $set->slots()->where('id', $setSlot->id)->count());
    }

    public function test_handle_ends_with_no_holy_oils_when_the_pool_is_exhausted(): void
    {
        $targetItem = $this->createItem(['type' => 'weapon', 'holy_stacks' => 5]);
        $set = $this->createInventorySet(['character_id' => $this->character->id]);
        $setSlot = $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $targetItem->id]);

        $plan = [(new HolyOilApplicationPlanEntry(HolyOilTargetKind::SET_SLOT, $setSlot->id, $targetItem->id))->toArray()];

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['holy_oils_mode' => 'inventory_set', 'inventory_set_id' => $set->id, 'inventory_set_name' => $set->name ?? ('Set '.$set->id), 'oil_slot_ids' => [999999], 'plan' => $plan, 'plan_index' => 0, 'current_target_slot_id' => $setSlot->id, 'current_target_item_id' => null, 'current_target_item_name' => null, 'current_oil_item_id' => null, 'current_oil_item_name' => null, 'current_holy_stacks' => null, 'max_holy_stacks' => null, 'applications_completed' => 0],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertSame(BatchCraftingEndReason::NO_HOLY_OILS, $result->endReason());
    }

    public function test_handle_ends_with_no_holy_oils_when_the_resolved_oil_fails_domain_revalidation(): void
    {
        $targetItem = $this->createItem(['type' => 'weapon', 'holy_stacks' => 5]);
        $set = $this->createInventorySet(['character_id' => $this->character->id]);
        $setSlot = $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $targetItem->id]);

        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $otherOil = $this->createItem(['type' => 'alchemy', 'holy_level' => 1, 'can_use_on_other_items' => true]);
        $otherOilSlot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $otherCharacter->alchemyBag->id,
            'character_id' => $otherCharacter->id,
            'item_id' => $otherOil->id,
            'amount' => 5,
        ]);

        $plan = [(new HolyOilApplicationPlanEntry(HolyOilTargetKind::SET_SLOT, $setSlot->id, $targetItem->id))->toArray()];

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['holy_oils_mode' => 'inventory_set', 'inventory_set_id' => $set->id, 'inventory_set_name' => $set->name ?? ('Set '.$set->id), 'oil_slot_ids' => [$otherOilSlot->id], 'plan' => $plan, 'plan_index' => 0, 'current_target_slot_id' => $setSlot->id, 'current_target_item_id' => null, 'current_target_item_name' => null, 'current_oil_item_id' => null, 'current_oil_item_name' => null, 'current_holy_stacks' => null, 'max_holy_stacks' => null, 'applications_completed' => 0],
        ]);

        $poolResolver = Mockery::mock(HolyOilOilPoolResolver::class, function (MockInterface $mock) use ($otherOilSlot) {
            $mock->shouldReceive('nextAvailableOil')->once()->andReturn($otherOilSlot);
        });
        $this->app->instance(HolyOilOilPoolResolver::class, $poolResolver);

        $result = resolve(HolyOilSetHandler::class)->handle($batchCrafting, $this->character);

        $this->assertSame(BatchCraftingEndReason::NO_HOLY_OILS, $result->endReason());
    }

    public function test_handle_ends_with_no_gold_dust_when_the_application_cost_cannot_be_afforded(): void
    {
        $targetItem = $this->createItem(['type' => 'weapon', 'holy_stacks' => 5]);
        $oil = $this->createItem(['type' => 'alchemy', 'holy_level' => 1, 'can_use_on_other_items' => true]);
        $this->character->update(['gold_dust' => 0]);
        $this->character = $this->character->refresh();

        $set = $this->createInventorySet(['character_id' => $this->character->id]);
        $setSlot = $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $targetItem->id]);
        $oilSlot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $this->character->alchemyBag->id,
            'character_id' => $this->character->id,
            'item_id' => $oil->id,
            'amount' => 3,
        ]);

        $plan = [(new HolyOilApplicationPlanEntry(HolyOilTargetKind::SET_SLOT, $setSlot->id, $targetItem->id))->toArray()];

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['holy_oils_mode' => 'inventory_set', 'inventory_set_id' => $set->id, 'inventory_set_name' => $set->name ?? ('Set '.$set->id), 'oil_slot_ids' => [$oilSlot->id], 'plan' => $plan, 'plan_index' => 0, 'current_target_slot_id' => $setSlot->id, 'current_target_item_id' => null, 'current_target_item_name' => null, 'current_oil_item_id' => null, 'current_oil_item_name' => null, 'current_holy_stacks' => null, 'max_holy_stacks' => null, 'applications_completed' => 0],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertSame(BatchCraftingEndReason::NO_GOLD_DUST, $result->endReason());
    }

    public function test_handle_applies_a_non_saturating_oil_application_to_a_set_slot(): void
    {
        $targetItem = $this->createItem(['type' => 'weapon', 'holy_stacks' => 2]);
        $oil = $this->createItem(['type' => 'alchemy', 'holy_level' => 1, 'can_use_on_other_items' => true]);

        $set = $this->createInventorySet(['character_id' => $this->character->id]);
        $setSlot = $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $targetItem->id]);
        $oilSlot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $this->character->alchemyBag->id,
            'character_id' => $this->character->id,
            'item_id' => $oil->id,
            'amount' => 3,
        ]);

        $plan = [(new HolyOilApplicationPlanEntry(HolyOilTargetKind::SET_SLOT, $setSlot->id, $targetItem->id))->toArray()];

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['holy_oils_mode' => 'inventory_set', 'inventory_set_id' => $set->id, 'inventory_set_name' => $set->name ?? ('Set '.$set->id), 'oil_slot_ids' => [$oilSlot->id], 'plan' => $plan, 'plan_index' => 0, 'current_target_slot_id' => $setSlot->id, 'current_target_item_id' => null, 'current_target_item_name' => null, 'current_oil_item_id' => null, 'current_oil_item_name' => null, 'current_holy_stacks' => null, 'max_holy_stacks' => null, 'applications_completed' => 0],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertNull($result->endReason());
        $this->assertFalse($result->didCraft());
        $this->assertEquals(2, $oilSlot->refresh()->amount);
        $this->assertEquals(1, $set->slots()->where('id', $setSlot->id)->count());
    }
}
