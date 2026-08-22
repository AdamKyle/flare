<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services\Status;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Services\Status\HolyOilSelectedItemsStatusSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateItem;

class HolyOilSelectedItemsStatusSectionTest extends TestCase
{
    use CreateBatchCrafting, CreateItem, RefreshDatabase;

    private ?HolyOilSelectedItemsStatusSection $section;

    protected function setUp(): void
    {
        parent::setUp();

        $this->section = resolve(HolyOilSelectedItemsStatusSection::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->section = null;
    }

    public function test_supports_only_holy_oils_selected_items(): void
    {
        $this->assertTrue($this->section->supports(BatchCraftingType::HOLY_OILS, 'selected_items'));
        $this->assertFalse($this->section->supports(BatchCraftingType::HOLY_OILS, 'inventory_set'));
        $this->assertFalse($this->section->supports(BatchCraftingType::TRINKETRY, 'selected_items'));
    }

    public function test_build_reports_factual_progress_from_the_persisted_plan(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $targetItem = $this->createItem(['type' => 'weapon', 'name' => 'Status Blade']);
        $oilItem = $this->createItem(['type' => 'alchemy', 'name' => 'Status Oil']);

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::HOLY_OILS->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'holy_oils_mode' => 'selected_items',
                'plan' => [['target_kind' => 'inventory_slot', 'target_slot_id' => 1, 'target_item_id' => 1], ['target_kind' => 'inventory_slot', 'target_slot_id' => 2, 'target_item_id' => 2]],
                'plan_index' => 1,
                'current_target_slot_id' => 2,
                'current_target_item_id' => $targetItem->id,
                'current_target_item_name' => $targetItem->name,
                'current_oil_item_id' => $oilItem->id,
                'current_oil_item_name' => $oilItem->name,
                'current_holy_stacks' => 1,
                'max_holy_stacks' => 3,
            ],
        ]);

        $result = $this->section->build($character, $batchCrafting, $batchCrafting->progress);

        $this->assertSame($targetItem->id, $result['current_item_id']);
        $this->assertSame('Status Blade', $result['current_item_name']);
        $this->assertNull($result['destination_set_id']);
        $this->assertNull($result['destination_set_name']);
        $this->assertSame($targetItem->id, $result['holy_oils_progress']['current_target_item_id']);
        $this->assertSame($oilItem->id, $result['holy_oils_progress']['current_oil_item_id']);
        $this->assertSame(1, $result['holy_oils_progress']['current_holy_stacks']);
        $this->assertSame(3, $result['holy_oils_progress']['max_holy_stacks']);
        $this->assertSame(2, $result['holy_oils_progress']['total_planned_targets']);
        $this->assertSame(1, $result['holy_oils_progress']['completed_targets']);
        $this->assertSame(1, $result['holy_oils_progress']['remaining_targets']);
        $this->assertNull($result['holy_oils_progress']['inventory_set_id']);
        $this->assertNull($result['holy_oils_progress']['inventory_set_name']);
    }
}
