<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Handlers;

use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Flare\Models\MarketBoard as MarketBoardModel;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingEndReason;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Handlers\CraftAndEnchantAmountHandler;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class CraftAndEnchantAmountHandlerTest extends TestCase
{
    use CreateBatchCrafting, CreateGameSkill, CreateItem, CreateItemAffix, RefreshDatabase;

    private ?Character $character;

    private ?CraftAndEnchantAmountHandler $handler;

    private ?Item $item;

    private ?ItemAffix $prefix;

    protected function setUp(): void
    {
        parent::setUp();

        $weaponCrafting = $this->createGameSkill(['name' => 'Weapon Crafting', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);

        $this->character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->assignSkill($weaponCrafting, 10, false)
            ->getCharacter();

        $enchantingGameSkill = GameSkill::where('type', SkillTypeValue::ENCHANTING->value)->first();
        $this->character->skills()->where('game_skill_id', $enchantingGameSkill->id)->update(['level' => 10]);

        $this->character->update(['gold' => 10000, 'inventory_max' => 30]);
        $this->character = $this->character->refresh();

        $this->item = $this->createItem(['name' => 'Craft and Enchant Dagger', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);

        $this->prefix = $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => false, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => -10, 'cost' => 0]);

        $this->handler = resolve(CraftAndEnchantAmountHandler::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->handler = null;
        $this->item = null;
        $this->prefix = null;
    }

    public function test_handle_returns_amount_reached_when_already_completed(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [
                'craft_enchant_mode' => 'amount',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $this->item->id,
                'prefix_id' => $this->prefix->id,
                'suffix_id' => null,
                'craft_amount' => 1,
                'completed_amount' => 1,
                'output_destination' => null,
                'output_set_id' => null,
                'listing_price' => null,
            ],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertSame(BatchCraftingEndReason::AMOUNT_REACHED, $result->endReason());
    }

    public function test_handle_returns_maxed_or_nothing_left_when_item_no_longer_craftable(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [
                'craft_enchant_mode' => 'amount',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => 999999,
                'prefix_id' => $this->prefix->id,
                'suffix_id' => null,
                'craft_amount' => 1,
                'completed_amount' => 0,
                'output_destination' => null,
                'output_set_id' => null,
                'listing_price' => null,
            ],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertSame(BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT, $result->endReason());
    }

    public function test_handle_keep_disposition_places_enchanted_item_in_inventory(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_enchant_mode' => 'amount',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $this->item->id,
                'prefix_id' => $this->prefix->id,
                'suffix_id' => null,
                'craft_amount' => 1,
                'completed_amount' => 0,
                'output_destination' => 'inventory',
                'output_set_id' => null,
                'listing_price' => null,
            ],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertTrue($result->didCraft());
        $this->assertSame(1, $result->goldSpent());

        $slot = $this->character->inventory->slots()->first();
        $this->assertNotNull($slot);
        $this->assertSame($this->prefix->id, $slot->item->item_prefix_id);
        $this->assertSame(1, $batchCrafting->refresh()->progress['completed_amount']);
    }

    public function test_handle_destroy_disposition_completes_without_placing_item(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [
                'craft_enchant_mode' => 'amount',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $this->item->id,
                'prefix_id' => $this->prefix->id,
                'suffix_id' => null,
                'craft_amount' => 1,
                'completed_amount' => 0,
                'output_destination' => null,
                'output_set_id' => null,
                'listing_price' => null,
            ],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertTrue($result->didCraft());
        $this->assertSame(1, $result->goldSpent());
        $this->assertSame(BatchCraftingEndReason::AMOUNT_REACHED, $result->endReason());
    }

    public function test_handle_list_disposition_creates_a_market_listing(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::LIST->value,
            'progress' => [
                'craft_enchant_mode' => 'amount',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $this->item->id,
                'prefix_id' => $this->prefix->id,
                'suffix_id' => null,
                'craft_amount' => 1,
                'completed_amount' => 0,
                'output_destination' => null,
                'output_set_id' => null,
                'listing_price' => 500,
            ],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertTrue($result->didCraft());
        $this->assertSame(1, MarketBoardModel::where('character_id', $this->character->id)->count());
    }

    public function test_handle_disenchant_disposition_grants_gold_dust(): void
    {
        $goldDustBefore = $this->character->gold_dust;
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DISENCHANT->value,
            'progress' => [
                'craft_enchant_mode' => 'amount',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $this->item->id,
                'prefix_id' => $this->prefix->id,
                'suffix_id' => null,
                'craft_amount' => 1,
                'completed_amount' => 0,
                'output_destination' => null,
                'output_set_id' => null,
                'listing_price' => null,
            ],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertTrue($result->didCraft());
        $this->assertGreaterThan($goldDustBefore, $this->character->refresh()->gold_dust);
    }

    public function test_handle_gold_cost_includes_both_craft_and_enchant(): void
    {
        $expensivePrefix = $this->createItemAffix(['type' => 'prefix', 'randomly_generated' => false, 'int_required' => 1, 'skill_level_required' => 1, 'skill_level_trivial' => -10, 'cost' => 250]);

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [
                'craft_enchant_mode' => 'amount',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $this->item->id,
                'prefix_id' => $expensivePrefix->id,
                'suffix_id' => null,
                'craft_amount' => 1,
                'completed_amount' => 0,
                'output_destination' => null,
                'output_set_id' => null,
                'listing_price' => null,
            ],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertTrue($result->didCraft());
        $this->assertSame(1 + 250, $result->goldSpent());
    }

    public function test_handle_does_not_advance_when_craft_fails_due_to_insufficient_gold(): void
    {
        $this->character->update(['gold' => 0]);
        $item = $this->createItem(['name' => 'Too Expensive', 'type' => 'dagger', 'crafting_type' => 'weapon', 'default_position' => 'dagger', 'can_craft' => true, 'cost' => 500, 'skill_level_required' => 1, 'skill_level_trivial' => 5]);

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [
                'craft_enchant_mode' => 'amount',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $item->id,
                'prefix_id' => $this->prefix->id,
                'suffix_id' => null,
                'craft_amount' => 1,
                'completed_amount' => 0,
                'output_destination' => null,
                'output_set_id' => null,
                'listing_price' => null,
            ],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertFalse($result->didCraft());
        $this->assertSame(BatchCraftingEndReason::NO_GOLD, $result->endReason());
        $this->assertSame(0, $batchCrafting->refresh()->progress['completed_amount']);
    }

    public function test_handle_does_not_advance_when_requested_affix_is_invalid(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [
                'craft_enchant_mode' => 'amount',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $this->item->id,
                'prefix_id' => 999999,
                'suffix_id' => null,
                'craft_amount' => 1,
                'completed_amount' => 0,
                'output_destination' => null,
                'output_set_id' => null,
                'listing_price' => null,
            ],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertFalse($result->didCraft());
        $this->assertSame(0, $batchCrafting->refresh()->progress['completed_amount']);
    }

    public function test_handle_does_not_advance_when_intelligence_is_too_low_and_does_not_choose_a_weaker_affix(): void
    {
        $this->prefix->update(['int_required' => 100000]);

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [
                'craft_enchant_mode' => 'amount',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $this->item->id,
                'prefix_id' => $this->prefix->id,
                'suffix_id' => null,
                'craft_amount' => 1,
                'completed_amount' => 0,
                'output_destination' => null,
                'output_set_id' => null,
                'listing_price' => null,
            ],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertFalse($result->didCraft());
        $this->assertSame(0, $batchCrafting->refresh()->progress['completed_amount']);
    }

    public function test_handle_returns_amount_reached_after_final_completion(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [
                'craft_enchant_mode' => 'amount',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $this->item->id,
                'prefix_id' => $this->prefix->id,
                'suffix_id' => null,
                'craft_amount' => 1,
                'completed_amount' => 0,
                'output_destination' => null,
                'output_set_id' => null,
                'listing_price' => null,
            ],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertTrue($result->didCraft());
        $this->assertSame(BatchCraftingEndReason::AMOUNT_REACHED, $result->endReason());
    }

    public function test_handle_continues_running_when_the_requested_amount_is_not_yet_reached(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [
                'craft_enchant_mode' => 'amount',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $this->item->id,
                'prefix_id' => $this->prefix->id,
                'suffix_id' => null,
                'craft_amount' => 2,
                'completed_amount' => 0,
                'output_destination' => null,
                'output_set_id' => null,
                'listing_price' => null,
            ],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertTrue($result->didCraft());
        $this->assertNull($result->endReason());
        $this->assertSame(1, $batchCrafting->refresh()->progress['completed_amount']);
    }

    public function test_handle_returns_no_inventory_space_when_keep_destination_is_full(): void
    {
        $this->character->update(['inventory_max' => 0]);

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'craft_enchant_mode' => 'amount',
                'specific_crafting_type' => 'dagger',
                'specific_item_id' => $this->item->id,
                'prefix_id' => $this->prefix->id,
                'suffix_id' => null,
                'craft_amount' => 1,
                'completed_amount' => 0,
                'output_destination' => 'inventory',
                'output_set_id' => null,
                'listing_price' => null,
            ],
        ]);

        $result = $this->handler->handle($batchCrafting, $this->character);

        $this->assertSame(BatchCraftingEndReason::NO_INVENTORY_SPACE, $result->endReason());
    }
}
