<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Handlers;

use App\Flare\Models\Character;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingEndReason;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Enums\HolyOilTargetKind;
use App\Game\Automation\BatchCrafting\Handlers\HolyOilSelectedItemsHandler;
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
use Tests\Traits\CreateItem;

class HolyOilSelectedItemsHandlerTest extends TestCase
{
    use CreateAlchemyBagSlot, CreateBatchCrafting, CreateItem, RefreshDatabase;

    private ?CharacterFactory $characterFactory;

    private ?Character $character;

    private ?HolyOilSelectedItemsHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $this->character = $this->characterFactory->getCharacter();
        $this->character->update(['gold_dust' => CurrencyLimit::MAX_GOLD_DUST]);
        $this->character = $this->character->refresh();

        $this->handler = resolve(HolyOilSelectedItemsHandler::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->characterFactory = null;
        $this->character = null;
        $this->handler = null;
    }

    public function test_handle_returns_amount_reached_when_plan_already_exhausted(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['holy_oils_mode' => 'selected_items', 'oil_slot_ids' => [], 'plan' => [], 'plan_index' => 0, 'current_target_slot_id' => null, 'current_target_item_id' => null, 'current_target_item_name' => null, 'current_oil_item_id' => null, 'current_oil_item_name' => null, 'current_holy_stacks' => null, 'max_holy_stacks' => null, 'applications_completed' => 0],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertSame(BatchCraftingEndReason::AMOUNT_REACHED, $result->endReason());
    }

    public function test_handle_applies_oil_completes_target_and_ends_the_plan(): void
    {
        $targetItem = $this->createItem(['type' => 'weapon', 'holy_stacks' => 1]);
        $oil = $this->createItem(['type' => 'alchemy', 'holy_level' => 1, 'can_use_on_other_items' => true]);

        $character = $this->characterFactory->inventoryManagement()->giveItem($targetItem)->getCharacter();
        $character->update(['gold_dust' => CurrencyLimit::MAX_GOLD_DUST]);
        $character = $character->refresh();

        $targetSlot = $character->inventory->slots()->where('item_id', $targetItem->id)->first();
        $oilSlot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $oil->id,
            'amount' => 5,
        ]);

        $plan = [(new HolyOilApplicationPlanEntry(HolyOilTargetKind::INVENTORY_SLOT, $targetSlot->id, $targetItem->id))->toArray()];

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['holy_oils_mode' => 'selected_items', 'oil_slot_ids' => [$oilSlot->id], 'plan' => $plan, 'plan_index' => 0, 'current_target_slot_id' => $targetSlot->id, 'current_target_item_id' => null, 'current_target_item_name' => null, 'current_oil_item_id' => null, 'current_oil_item_name' => null, 'current_holy_stacks' => null, 'max_holy_stacks' => null, 'applications_completed' => 0],
        ]);

        $result = $this->handler->handle($batchCrafting, $character);

        $this->assertSame(BatchCraftingEndReason::AMOUNT_REACHED, $result->endReason());
        $this->assertTrue($result->didCraft());
        $this->assertEquals(4, $oilSlot->refresh()->amount);
    }

    public function test_handle_destroys_target_when_disposition_is_destroy(): void
    {
        $targetItem = $this->createItem(['type' => 'weapon', 'holy_stacks' => 1]);
        $oil = $this->createItem(['type' => 'alchemy', 'holy_level' => 1, 'can_use_on_other_items' => true]);

        $character = $this->characterFactory->inventoryManagement()->giveItem($targetItem)->getCharacter();
        $character->update(['gold_dust' => CurrencyLimit::MAX_GOLD_DUST]);
        $character = $character->refresh();

        $targetSlot = $character->inventory->slots()->where('item_id', $targetItem->id)->first();
        $oilSlot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $oil->id,
            'amount' => 5,
        ]);

        $plan = [(new HolyOilApplicationPlanEntry(HolyOilTargetKind::INVENTORY_SLOT, $targetSlot->id, $targetItem->id))->toArray()];

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['holy_oils_mode' => 'selected_items', 'oil_slot_ids' => [$oilSlot->id], 'plan' => $plan, 'plan_index' => 0, 'current_target_slot_id' => $targetSlot->id, 'current_target_item_id' => null, 'current_target_item_name' => null, 'current_oil_item_id' => null, 'current_oil_item_name' => null, 'current_holy_stacks' => null, 'max_holy_stacks' => null, 'applications_completed' => 0],
        ]);

        $this->handler->handle($batchCrafting, $character);

        $this->assertEquals(0, $character->inventory->slots()->where('id', $targetSlot->id)->count());
    }

    public function test_handle_ends_with_no_holy_oils_when_pool_is_exhausted(): void
    {
        $targetItem = $this->createItem(['type' => 'weapon', 'holy_stacks' => 5]);

        $character = $this->characterFactory->inventoryManagement()->giveItem($targetItem)->getCharacter();
        $character->update(['gold_dust' => CurrencyLimit::MAX_GOLD_DUST]);
        $character = $character->refresh();

        $targetSlot = $character->inventory->slots()->where('item_id', $targetItem->id)->first();

        $plan = [(new HolyOilApplicationPlanEntry(HolyOilTargetKind::INVENTORY_SLOT, $targetSlot->id, $targetItem->id))->toArray()];

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['holy_oils_mode' => 'selected_items', 'oil_slot_ids' => [999999], 'plan' => $plan, 'plan_index' => 0, 'current_target_slot_id' => $targetSlot->id, 'current_target_item_id' => null, 'current_target_item_name' => null, 'current_oil_item_id' => null, 'current_oil_item_name' => null, 'current_holy_stacks' => null, 'max_holy_stacks' => null, 'applications_completed' => 0],
        ]);

        $result = $this->handler->handle($batchCrafting, $character);

        $this->assertSame(BatchCraftingEndReason::NO_HOLY_OILS, $result->endReason());
    }

    public function test_handle_skips_trinket_target_without_disposition(): void
    {
        $trinketItem = $this->createItem(['type' => 'trinket', 'holy_stacks' => 5]);

        $character = $this->characterFactory->inventoryManagement()->giveItem($trinketItem)->getCharacter();
        $character->update(['gold_dust' => CurrencyLimit::MAX_GOLD_DUST]);
        $character = $character->refresh();

        $targetSlot = $character->inventory->slots()->where('item_id', $trinketItem->id)->first();

        $plan = [(new HolyOilApplicationPlanEntry(HolyOilTargetKind::INVENTORY_SLOT, $targetSlot->id, $trinketItem->id))->toArray()];

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::SELL->value,
            'progress' => ['holy_oils_mode' => 'selected_items', 'oil_slot_ids' => [], 'plan' => $plan, 'plan_index' => 0, 'current_target_slot_id' => $targetSlot->id, 'current_target_item_id' => null, 'current_target_item_name' => null, 'current_oil_item_id' => null, 'current_oil_item_name' => null, 'current_holy_stacks' => null, 'max_holy_stacks' => null, 'applications_completed' => 0],
        ]);

        $result = $this->handler->handle($batchCrafting, $character);

        $this->assertSame(BatchCraftingEndReason::AMOUNT_REACHED, $result->endReason());
        $this->assertFalse($result->didCraft());
        $this->assertEquals(1, $character->inventory->slots()->where('id', $targetSlot->id)->count());
    }

    public function test_handle_advances_to_the_next_target_without_ending_when_the_plan_has_more_targets(): void
    {
        $trinketItem = $this->createItem(['type' => 'trinket', 'holy_stacks' => 5]);
        $weaponItem = $this->createItem(['type' => 'weapon', 'holy_stacks' => 5]);

        $character = $this->characterFactory->inventoryManagement()->giveItem($trinketItem)->giveItem($weaponItem)->getCharacter();
        $character->update(['gold_dust' => CurrencyLimit::MAX_GOLD_DUST]);
        $character = $character->refresh();

        $trinketSlot = $character->inventory->slots()->where('item_id', $trinketItem->id)->first();
        $weaponSlot = $character->inventory->slots()->where('item_id', $weaponItem->id)->first();

        $plan = [
            (new HolyOilApplicationPlanEntry(HolyOilTargetKind::INVENTORY_SLOT, $trinketSlot->id, $trinketItem->id))->toArray(),
            (new HolyOilApplicationPlanEntry(HolyOilTargetKind::INVENTORY_SLOT, $weaponSlot->id, $weaponItem->id))->toArray(),
        ];

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::SELL->value,
            'progress' => ['holy_oils_mode' => 'selected_items', 'oil_slot_ids' => [], 'plan' => $plan, 'plan_index' => 0, 'current_target_slot_id' => $trinketSlot->id, 'current_target_item_id' => null, 'current_target_item_name' => null, 'current_oil_item_id' => null, 'current_oil_item_name' => null, 'current_holy_stacks' => null, 'max_holy_stacks' => null, 'applications_completed' => 0],
        ]);

        $result = $this->handler->handle($batchCrafting, $character);

        $this->assertNull($result->endReason());
        $this->assertSame(1, $batchCrafting->refresh()->progress['plan_index']);
        $this->assertSame($weaponSlot->id, $batchCrafting->fresh()->progress['current_target_slot_id']);
    }

    public function test_handle_completes_a_target_that_is_already_at_capacity_when_the_tick_starts(): void
    {
        $targetItem = $this->createItem(['type' => 'weapon', 'holy_stacks' => 0]);

        $character = $this->characterFactory->inventoryManagement()->giveItem($targetItem)->getCharacter();
        $character->update(['gold_dust' => CurrencyLimit::MAX_GOLD_DUST]);
        $character = $character->refresh();

        $targetSlot = $character->inventory->slots()->where('item_id', $targetItem->id)->first();

        $plan = [(new HolyOilApplicationPlanEntry(HolyOilTargetKind::INVENTORY_SLOT, $targetSlot->id, $targetItem->id))->toArray()];

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['holy_oils_mode' => 'selected_items', 'oil_slot_ids' => [], 'plan' => $plan, 'plan_index' => 0, 'current_target_slot_id' => $targetSlot->id, 'current_target_item_id' => null, 'current_target_item_name' => null, 'current_oil_item_id' => null, 'current_oil_item_name' => null, 'current_holy_stacks' => null, 'max_holy_stacks' => null, 'applications_completed' => 0],
        ]);

        $result = $this->handler->handle($batchCrafting, $character);

        $this->assertSame(BatchCraftingEndReason::AMOUNT_REACHED, $result->endReason());
        $this->assertTrue($result->didCraft());
        $this->assertEquals(0, $character->inventory->slots()->where('id', $targetSlot->id)->count());
    }

    public function test_handle_applies_a_non_saturating_oil_application_and_continues(): void
    {
        $targetItem = $this->createItem(['type' => 'weapon', 'holy_stacks' => 2]);
        $oil = $this->createItem(['type' => 'alchemy', 'holy_level' => 1, 'can_use_on_other_items' => true]);

        $character = $this->characterFactory->inventoryManagement()->giveItem($targetItem)->getCharacter();
        $character->update(['gold_dust' => CurrencyLimit::MAX_GOLD_DUST]);
        $character = $character->refresh();

        $targetSlot = $character->inventory->slots()->where('item_id', $targetItem->id)->first();
        $oilSlot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $oil->id,
            'amount' => 5,
        ]);

        $plan = [(new HolyOilApplicationPlanEntry(HolyOilTargetKind::INVENTORY_SLOT, $targetSlot->id, $targetItem->id))->toArray()];

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['holy_oils_mode' => 'selected_items', 'oil_slot_ids' => [$oilSlot->id], 'plan' => $plan, 'plan_index' => 0, 'current_target_slot_id' => $targetSlot->id, 'current_target_item_id' => null, 'current_target_item_name' => null, 'current_oil_item_id' => null, 'current_oil_item_name' => null, 'current_holy_stacks' => null, 'max_holy_stacks' => null, 'applications_completed' => 0],
        ]);

        $result = $this->handler->handle($batchCrafting, $character);

        $this->assertNull($result->endReason());
        $this->assertFalse($result->didCraft());
        $this->assertEquals(4, $oilSlot->refresh()->amount);
        $this->assertSame(1, $batchCrafting->refresh()->progress['current_holy_stacks']);
        $this->assertSame(1, $character->inventory->slots()->count());
    }

    public function test_handle_ends_with_no_holy_oils_when_the_resolved_oil_fails_domain_revalidation(): void
    {
        $targetItem = $this->createItem(['type' => 'weapon', 'holy_stacks' => 5]);
        $character = $this->characterFactory->inventoryManagement()->giveItem($targetItem)->getCharacter();
        $character->update(['gold_dust' => CurrencyLimit::MAX_GOLD_DUST]);
        $character = $character->refresh();

        $targetSlot = $character->inventory->slots()->where('item_id', $targetItem->id)->first();

        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $otherOil = $this->createItem(['type' => 'alchemy', 'holy_level' => 1, 'can_use_on_other_items' => true]);
        $otherOilSlot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $otherCharacter->alchemyBag->id,
            'character_id' => $otherCharacter->id,
            'item_id' => $otherOil->id,
            'amount' => 5,
        ]);

        $plan = [(new HolyOilApplicationPlanEntry(HolyOilTargetKind::INVENTORY_SLOT, $targetSlot->id, $targetItem->id))->toArray()];

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['holy_oils_mode' => 'selected_items', 'oil_slot_ids' => [$otherOilSlot->id], 'plan' => $plan, 'plan_index' => 0, 'current_target_slot_id' => $targetSlot->id, 'current_target_item_id' => null, 'current_target_item_name' => null, 'current_oil_item_id' => null, 'current_oil_item_name' => null, 'current_holy_stacks' => null, 'max_holy_stacks' => null, 'applications_completed' => 0],
        ]);

        $poolResolver = Mockery::mock(HolyOilOilPoolResolver::class, function (MockInterface $mock) use ($otherOilSlot) {
            $mock->shouldReceive('nextAvailableOil')->once()->andReturn($otherOilSlot);
        });
        $this->app->instance(HolyOilOilPoolResolver::class, $poolResolver);

        $result = resolve(HolyOilSelectedItemsHandler::class)->handle($batchCrafting, $character);

        $this->assertSame(BatchCraftingEndReason::NO_HOLY_OILS, $result->endReason());
    }

    public function test_handle_ends_with_no_gold_dust_when_the_application_cost_cannot_be_afforded(): void
    {
        $targetItem = $this->createItem(['type' => 'weapon', 'holy_stacks' => 5]);
        $oil = $this->createItem(['type' => 'alchemy', 'holy_level' => 1, 'can_use_on_other_items' => true]);

        $character = $this->characterFactory->inventoryManagement()->giveItem($targetItem)->getCharacter();
        $character->update(['gold_dust' => 0]);
        $character = $character->refresh();

        $targetSlot = $character->inventory->slots()->where('item_id', $targetItem->id)->first();
        $oilSlot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $oil->id,
            'amount' => 5,
        ]);

        $plan = [(new HolyOilApplicationPlanEntry(HolyOilTargetKind::INVENTORY_SLOT, $targetSlot->id, $targetItem->id))->toArray()];

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['holy_oils_mode' => 'selected_items', 'oil_slot_ids' => [$oilSlot->id], 'plan' => $plan, 'plan_index' => 0, 'current_target_slot_id' => $targetSlot->id, 'current_target_item_id' => null, 'current_target_item_name' => null, 'current_oil_item_id' => null, 'current_oil_item_name' => null, 'current_holy_stacks' => null, 'max_holy_stacks' => null, 'applications_completed' => 0],
        ]);

        $result = $this->handler->handle($batchCrafting, $character);

        $this->assertSame(BatchCraftingEndReason::NO_GOLD_DUST, $result->endReason());
    }
}
