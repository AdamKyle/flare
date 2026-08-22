<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Handlers;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingEndReason;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Handlers\AlchemyAmountHandler;
use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Core\Currency\Services\CurrencyLimit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateItem;

class AlchemyAmountHandlerTest extends TestCase
{
    use CreateBatchCrafting, CreateItem, RefreshDatabase;

    private ?Character $character;

    private ?Item $alchemyItem;

    private ?AlchemyAmountHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->alchemyItem = $this->createItem([
            'gold_dust_cost' => 100,
            'shards_cost' => 10,
            'skill_level_required' => 1,
            'skill_level_trivial' => 0,
            'crafting_type' => 'alchemy',
            'can_craft' => true,
            'type' => 'alchemy',
        ]);

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $this->character->update(['gold_dust' => CurrencyLimit::MAX_GOLD_DUST, 'shards' => CurrencyLimit::MAX_SHARDS]);
        $this->character = $this->character->refresh();

        $this->handler = resolve(AlchemyAmountHandler::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->alchemyItem = null;
        $this->handler = null;
    }

    public function test_handle_returns_amount_reached_when_requested_amount_already_completed(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_mode' => 'amount', 'alchemy_item_id' => $this->alchemyItem->id, 'alchemy_amount' => 1, 'completed_amount' => 1, 'alchemy_xp_gained' => 0, 'current_item_id' => null, 'current_item_name' => null],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertSame(BatchCraftingEndReason::AMOUNT_REACHED, $result->endReason());
    }

    public function test_handle_ends_with_no_alchemy_items_when_item_is_no_longer_valid(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_mode' => 'amount', 'alchemy_item_id' => 999999, 'alchemy_amount' => 1, 'completed_amount' => 0, 'alchemy_xp_gained' => 0, 'current_item_id' => null, 'current_item_name' => null],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertSame(BatchCraftingEndReason::NO_ALCHEMY_ITEMS, $result->endReason());
    }

    public function test_handle_ends_with_no_gold_dust_when_insufficient(): void
    {
        $this->character->update(['gold_dust' => 0]);

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_mode' => 'amount', 'alchemy_item_id' => $this->alchemyItem->id, 'alchemy_amount' => 1, 'completed_amount' => 0, 'alchemy_xp_gained' => 0, 'current_item_id' => null, 'current_item_name' => null],
        ]);

        $result = $this->handler->handle($batchCrafting->fresh(), $this->character->refresh());

        $this->assertSame(BatchCraftingEndReason::NO_GOLD_DUST, $result->endReason());
    }

    public function test_handle_ends_with_no_shards_when_insufficient(): void
    {
        $this->character->update(['shards' => 0]);

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_mode' => 'amount', 'alchemy_item_id' => $this->alchemyItem->id, 'alchemy_amount' => 1, 'completed_amount' => 0, 'alchemy_xp_gained' => 0, 'current_item_id' => null, 'current_item_name' => null],
        ]);

        $result = $this->handler->handle($batchCrafting->fresh(), $this->character->refresh());

        $this->assertSame(BatchCraftingEndReason::NO_SHARDS, $result->endReason());
    }

    public function test_handle_keeps_transmuted_item_and_advances_completed_amount(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_mode' => 'amount', 'alchemy_item_id' => $this->alchemyItem->id, 'alchemy_amount' => 5, 'completed_amount' => 0, 'alchemy_xp_gained' => 0, 'current_item_id' => null, 'current_item_name' => null],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $progress = $batchCrafting->fresh()->progress;

        $this->assertNull($result->endReason());
        $this->assertSame(1, $progress['completed_amount']);
        $this->assertSame($this->alchemyItem->id, $progress['current_item_id']);
        $this->assertEquals(1, $this->character->refresh()->alchemyBag->slots()->where('item_id', $this->alchemyItem->id)->value('amount'));
    }

    public function test_handle_returns_failed_result_when_the_transmute_roll_fails(): void
    {
        $this->instance(
            RandomNumberGenerator::class,
            Mockery::mock(RandomNumberGenerator::class, function (MockInterface $mock): void {
                $mock->shouldReceive('numberBetween')->with(1, 400)->andReturn(1, 400);
            })
        );
        $rollableItem = $this->createItem([
            'gold_dust_cost' => 100,
            'shards_cost' => 10,
            'skill_level_required' => 1,
            'skill_level_trivial' => 100,
            'crafting_type' => 'alchemy',
            'can_craft' => true,
            'type' => 'alchemy',
        ]);
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_mode' => 'amount', 'alchemy_item_id' => $rollableItem->id, 'alchemy_amount' => 5, 'completed_amount' => 0, 'alchemy_xp_gained' => 0, 'current_item_id' => null, 'current_item_name' => null],
        ]);

        $result = resolve(AlchemyAmountHandler::class)->handle($batchCrafting, $this->character);

        $this->assertFalse($result->didCraft());
        $this->assertNull($result->endReason());
    }

    public function test_handle_destroys_transmuted_item_when_disposition_is_destroy(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => ['alchemy_mode' => 'amount', 'alchemy_item_id' => $this->alchemyItem->id, 'alchemy_amount' => 5, 'completed_amount' => 0, 'alchemy_xp_gained' => 0, 'current_item_id' => null, 'current_item_name' => null],
        ]);

        $this->handler->handle($batchCrafting, $this->character);

        $this->assertEquals(0, $this->character->refresh()->alchemyBag->slots()->where('item_id', $this->alchemyItem->id)->count());
    }

    public function test_handle_reaches_amount_after_final_successful_attempt(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_mode' => 'amount', 'alchemy_item_id' => $this->alchemyItem->id, 'alchemy_amount' => 1, 'completed_amount' => 0, 'alchemy_xp_gained' => 0, 'current_item_id' => null, 'current_item_name' => null],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertSame(BatchCraftingEndReason::AMOUNT_REACHED, $result->endReason());
    }
}
