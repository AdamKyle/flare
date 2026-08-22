<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services\Status;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Services\Status\CraftAndEnchantSetStatusSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateItem;

class CraftAndEnchantSetStatusSectionTest extends TestCase
{
    use CreateBatchCrafting, CreateItem, RefreshDatabase;

    private ?CraftAndEnchantSetStatusSection $section;

    protected function setUp(): void
    {
        parent::setUp();

        $this->section = resolve(CraftAndEnchantSetStatusSection::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->section = null;
    }

    public function test_supports_only_craft_and_enchant_set(): void
    {
        $this->assertTrue($this->section->supports(BatchCraftingType::CRAFT_AND_ENCHANT, 'set'));
        $this->assertFalse($this->section->supports(BatchCraftingType::CRAFT_AND_ENCHANT, 'amount'));
        $this->assertFalse($this->section->supports(BatchCraftingType::CRAFT, 'set'));
    }

    public function test_build_reports_set_progress_and_current_phase(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $item = $this->createItem(['name' => 'Status Body']);

        $queue = [
            ['position' => 'body', 'item_id' => $item->id, 'crafting_type' => 'armour', 'item_name' => $item->name, 'prefix_id' => null, 'suffix_id' => null],
            ['position' => 'leggings', 'item_id' => $item->id, 'crafting_type' => 'armour', 'item_name' => $item->name, 'prefix_id' => null, 'suffix_id' => null],
        ];

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [
                'craft_enchant_mode' => 'set',
                'set_queue' => $queue,
                'set_index' => 1,
                'set_phase' => 'enchanting',
                'output_destination' => null,
                'output_set_id' => null,
                'listing_price' => null,
                'current_item_id' => $item->id,
                'current_item_name' => $item->name,
                'current_position' => 'leggings',
                'current_prefix_name' => 'Sharp',
                'current_suffix_name' => null,
            ],
        ]);

        $result = $this->section->build($character, $batchCrafting, $batchCrafting->progress);

        $this->assertSame($item->id, $result['current_item_id']);
        $this->assertSame('Sharp', $result['current_prefix_name']);
        $this->assertSame(2, $result['set_progress']['total_entries']);
        $this->assertSame(1, $result['set_progress']['completed_entries']);
        $this->assertSame(1, $result['set_progress']['remaining_entries']);
        $this->assertSame('leggings', $result['set_progress']['current_position']);
        $this->assertSame('enchanting', $result['set_progress']['current_phase']);
        $this->assertNull($result['experience_progress']);
        $this->assertNull($result['event_progress']);
    }
}
