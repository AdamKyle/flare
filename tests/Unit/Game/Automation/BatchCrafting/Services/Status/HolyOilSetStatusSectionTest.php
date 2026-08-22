<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services\Status;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Services\Status\HolyOilSetStatusSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateInventorySets;
use Tests\Traits\CreateItem;

class HolyOilSetStatusSectionTest extends TestCase
{
    use CreateBatchCrafting, CreateInventorySets, CreateItem, RefreshDatabase;

    private ?HolyOilSetStatusSection $section;

    protected function setUp(): void
    {
        parent::setUp();

        $this->section = resolve(HolyOilSetStatusSection::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->section = null;
    }

    public function test_supports_only_holy_oils_inventory_set(): void
    {
        $this->assertTrue($this->section->supports(BatchCraftingType::HOLY_OILS, 'inventory_set'));
        $this->assertFalse($this->section->supports(BatchCraftingType::HOLY_OILS, 'selected_items'));
        $this->assertFalse($this->section->supports(BatchCraftingType::TRINKETRY, 'inventory_set'));
    }

    public function test_build_reports_the_target_set_id_and_name(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id, 'name' => 'Holy Set']);
        $targetItem = $this->createItem(['type' => 'weapon', 'name' => 'Status Set Blade']);
        $oilItem = $this->createItem(['type' => 'alchemy', 'name' => 'Status Set Oil']);

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'holy_oils_mode' => 'inventory_set',
                'inventory_set_id' => $set->id,
                'inventory_set_name' => 'Holy Set',
                'plan' => [['target_kind' => 'set_slot', 'target_slot_id' => 1, 'target_item_id' => 1]],
                'plan_index' => 0,
                'current_target_slot_id' => 1,
                'current_target_item_id' => $targetItem->id,
                'current_target_item_name' => $targetItem->name,
                'current_oil_item_id' => $oilItem->id,
                'current_oil_item_name' => $oilItem->name,
                'current_holy_stacks' => 0,
                'max_holy_stacks' => 2,
            ],
        ]);

        $result = $this->section->build($character, $batchCrafting, $batchCrafting->progress);

        $this->assertSame($set->id, $result['destination_set_id']);
        $this->assertSame('Holy Set', $result['destination_set_name']);
        $this->assertSame($set->id, $result['holy_oils_progress']['inventory_set_id']);
        $this->assertSame('Holy Set', $result['holy_oils_progress']['inventory_set_name']);
        $this->assertSame(1, $result['holy_oils_progress']['total_planned_targets']);
        $this->assertSame(0, $result['holy_oils_progress']['completed_targets']);
        $this->assertSame(1, $result['holy_oils_progress']['remaining_targets']);
    }
}
