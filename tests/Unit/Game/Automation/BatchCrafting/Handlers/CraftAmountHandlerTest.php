<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Handlers;

use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Inventory;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingEndReason;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Handlers\CraftAmountHandler;
use App\Game\Character\CharacterInventory\Services\BatchCraftingSetService;
use App\Game\Skills\Services\SkillCheckService;
use App\Game\Skills\Values\SkillTypeValue;
use Facades\App\Game\Core\Items\Pricing\SellItemCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;

class CraftAmountHandlerTest extends TestCase
{
    use CreateBatchCrafting, CreateGameSkill, CreateItem, RefreshDatabase;

    private ?GameSkill $weaponCrafting;

    private ?Character $character;

    private ?CraftAmountHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignSkill($this->weaponCrafting, 10, false)->getCharacter();
        $this->character->update(['gold' => 1000, 'inventory_max' => 30]);
        $this->handler = resolve(CraftAmountHandler::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->weaponCrafting = null;
        $this->character = null;
        $this->handler = null;
    }

    public function test_handle_returns_amount_reached_when_requested_amount_already_completed(): void
    {
        $item = $this->createItem(['name' => 'Craft Amount Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'craft_specific_count' => 1],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertSame(BatchCraftingEndReason::AMOUNT_REACHED, $result->endReason());
    }

    public function test_handle_returns_maxed_or_nothing_left_when_item_no_longer_exists(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => 999999, 'craft_amount' => 1, 'craft_specific_count' => 0],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertSame(BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT, $result->endReason());
    }

    public function test_handle_returns_maxed_or_nothing_left_when_item_is_no_longer_craftable(): void
    {
        $item = $this->createItem(['name' => 'No Longer Craftable Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => false, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'craft_specific_count' => 0],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertSame(BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT, $result->endReason());
    }

    public function test_handle_keep_disposition_with_crafted_items_set_destination_places_item_in_set(): void
    {
        $item = $this->createItem(['name' => 'Craft Amount Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'craft_specific_count' => 0, 'output_destination' => 'crafted_items_set'],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertTrue($result->didCraft());
        $batchCraftingSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($this->character);
        $this->assertSame(1, $batchCraftingSet->slots()->where('item_id', $item->id)->count());
    }

    public function test_handle_keep_disposition_with_inventory_destination_places_item_in_inventory(): void
    {
        $item = $this->createItem(['name' => 'Craft Amount Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'craft_specific_count' => 0, 'output_destination' => 'inventory'],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertTrue($result->didCraft());
        $this->assertSame(1, $this->character->inventory->slots()->where('item_id', $item->id)->count());
    }

    public function test_handle_sell_disposition_sells_crafted_item_for_gold(): void
    {
        $item = $this->createItem(['name' => 'Craft Amount Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 100, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::SELL->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'craft_specific_count' => 0],
        ]);
        $goldBeforeSale = $this->character->gold;
        $expectedGoldGained = max(0, SellItemCalculator::fetchSalePriceWithAffixes($item));

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertTrue($result->didCraft());
        $this->assertSame($goldBeforeSale - 100 + $expectedGoldGained, $this->character->refresh()->gold);
    }

    public function test_handle_destroy_disposition_destroys_crafted_item(): void
    {
        $item = $this->createItem(['name' => 'Craft Amount Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'craft_specific_count' => 0],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertTrue($result->didCraft());
    }

    public function test_handle_increments_craft_specific_count_only_on_successful_craft(): void
    {
        $item = $this->createItem(['name' => 'Craft Amount Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 5, 'craft_specific_count' => 0],
        ]);

        $this->handler->handle($batchCrafting, $this->character);

        $this->assertSame(1, $batchCrafting->refresh()->progress['craft_specific_count']);
    }

    public function test_handle_does_not_increment_progress_when_craft_fails_due_to_insufficient_gold(): void
    {
        $this->character->update(['gold' => 0]);
        $item = $this->createItem(['name' => 'Too Expensive Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 500, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'craft_specific_count' => 0],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertFalse($result->didCraft());
        $this->assertSame(BatchCraftingEndReason::NO_GOLD, $result->endReason());
        $this->assertSame(0, $batchCrafting->refresh()->progress['craft_specific_count']);
    }

    public function test_handle_returns_no_inventory_space_when_inventory_destination_is_full(): void
    {
        $this->character->update(['inventory_max' => 0]);
        $item = $this->createItem(['name' => 'Craft Amount Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'craft_specific_count' => 0, 'output_destination' => 'inventory'],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertSame(BatchCraftingEndReason::NO_INVENTORY_SPACE, $result->endReason());
    }

    public function test_handle_returns_batch_crafting_set_full_when_crafted_items_set_is_full(): void
    {
        $item = $this->createItem(['name' => 'Craft Amount Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $batchCraftingSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($this->character);
        $batchCraftingSet->update(['max_slots' => 0]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'craft_specific_count' => 0, 'output_destination' => 'crafted_items_set'],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertSame(BatchCraftingEndReason::BATCH_CRAFTING_SET_FULL, $result->endReason());
    }

    public function test_handle_keep_disposition_fails_cleanly_when_inventory_row_is_missing_at_commit_time(): void
    {
        $item = $this->createItem(['name' => 'Craft Amount Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        Inventory::where('character_id', $this->character->id)->delete();
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'craft_specific_count' => 0, 'output_destination' => 'inventory'],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertSame(BatchCraftingEndReason::FAILED, $result->endReason());
    }

    public function test_handle_returns_failed_action_status_when_craft_roll_fails(): void
    {
        $item = $this->createItem(['name' => 'Roll Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 20]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'craft_specific_count' => 0],
        ]);

        $this->app->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(100);
                $mock->shouldReceive('characterRoll')->once()->andReturn(1);
            })
        );

        $result = resolve(CraftAmountHandler::class)->handle($batchCrafting, $this->character);

        $this->assertFalse($result->didCraft());
        $this->assertNull($result->endReason());
    }

    public function test_handle_returns_failed_when_the_crafted_items_set_cannot_accept_the_item_at_commit_time(): void
    {
        $item = $this->createItem(['name' => 'Craft Amount Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['craft_mode' => 'specific_item', 'specific_crafting_type' => 'dagger', 'specific_item_id' => $item->id, 'craft_amount' => 1, 'craft_specific_count' => 0, 'output_destination' => 'crafted_items_set'],
        ]);

        $batchCraftingSetService = Mockery::mock(BatchCraftingSetService::class, function (MockInterface $mock) {
            $mock->shouldReceive('canAccept')->once()->andReturn(true);
            $mock->shouldReceive('createItemInBatchCraftingSet')->once()->andReturn(['success' => false, 'reason' => 'set_full', 'set_slot' => null]);
        });
        $this->app->instance(BatchCraftingSetService::class, $batchCraftingSetService);

        $result = resolve(CraftAmountHandler::class)->handle($batchCrafting, $this->character);

        $this->assertSame(BatchCraftingEndReason::FAILED, $result->endReason());
    }
}
