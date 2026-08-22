<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services\Status;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Services\Status\CraftAndEnchantAmountStatusSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class CraftAndEnchantAmountStatusSectionTest extends TestCase
{
    use CreateBatchCrafting, CreateItem, CreateItemAffix, RefreshDatabase;

    private ?CraftAndEnchantAmountStatusSection $section;

    protected function setUp(): void
    {
        parent::setUp();

        $this->section = resolve(CraftAndEnchantAmountStatusSection::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->section = null;
    }

    public function test_supports_only_craft_and_enchant_amount(): void
    {
        $this->assertTrue($this->section->supports(BatchCraftingType::CRAFT_AND_ENCHANT, 'amount'));
        $this->assertFalse($this->section->supports(BatchCraftingType::CRAFT_AND_ENCHANT, 'set'));
        $this->assertFalse($this->section->supports(BatchCraftingType::CRAFT, 'amount'));
    }

    public function test_build_reports_item_prefix_suffix_and_amount_progress(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $item = $this->createItem(['name' => 'Status Dagger']);
        $prefix = $this->createItemAffix(['type' => 'prefix', 'name' => 'Sharp']);
        $suffix = $this->createItemAffix(['type' => 'suffix', 'name' => 'of Fire']);

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [
                'craft_enchant_mode' => 'amount',
                'specific_crafting_type' => 'weapon',
                'specific_item_id' => $item->id,
                'prefix_id' => $prefix->id,
                'suffix_id' => $suffix->id,
                'craft_amount' => 5,
                'completed_amount' => 2,
                'output_destination' => null,
                'output_set_id' => null,
                'listing_price' => null,
            ],
        ]);

        $result = $this->section->build($character, $batchCrafting, $batchCrafting->progress);

        $this->assertSame($item->id, $result['current_item_id']);
        $this->assertSame('Status Dagger', $result['current_item_name']);
        $this->assertSame('Sharp', $result['current_prefix_name']);
        $this->assertSame('of Fire', $result['current_suffix_name']);
        $this->assertSame(5, $result['requested_amount']);
        $this->assertSame(2, $result['completed_amount']);
        $this->assertSame(3, $result['remaining_amount']);
        $this->assertNull($result['set_progress']);
        $this->assertNull($result['experience_progress']);
        $this->assertNull($result['event_progress']);
    }

    public function test_build_reports_null_prefix_and_suffix_names_when_unselected(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $item = $this->createItem(['name' => 'Status Dagger']);

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [
                'craft_enchant_mode' => 'amount',
                'specific_crafting_type' => 'weapon',
                'specific_item_id' => $item->id,
                'prefix_id' => null,
                'suffix_id' => null,
                'craft_amount' => 1,
                'completed_amount' => 0,
                'output_destination' => null,
                'output_set_id' => null,
                'listing_price' => null,
            ],
        ]);

        $result = $this->section->build($character, $batchCrafting, $batchCrafting->progress);

        $this->assertNull($result['current_prefix_name']);
        $this->assertNull($result['current_suffix_name']);
    }
}
