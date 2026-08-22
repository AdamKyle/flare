<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services\Status;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Services\Status\AlchemyAmountStatusSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateItem;

class AlchemyAmountStatusSectionTest extends TestCase
{
    use CreateBatchCrafting, CreateItem, RefreshDatabase;

    private ?AlchemyAmountStatusSection $section;

    protected function setUp(): void
    {
        parent::setUp();

        $this->section = resolve(AlchemyAmountStatusSection::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->section = null;
    }

    public function test_supports_only_alchemy_amount(): void
    {
        $this->assertTrue($this->section->supports(BatchCraftingType::ALCHEMY, 'amount'));
        $this->assertFalse($this->section->supports(BatchCraftingType::ALCHEMY, 'experience'));
        $this->assertFalse($this->section->supports(BatchCraftingType::TRINKETRY, 'amount'));
    }

    public function test_build_reports_factual_amount_progress_from_persisted_progress(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $item = $this->createItem(['type' => 'alchemy', 'crafting_type' => 'alchemy', 'name' => 'Status Elixir']);

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'alchemy_mode' => 'amount',
                'alchemy_item_id' => $item->id,
                'alchemy_amount' => 10,
                'completed_amount' => 4,
                'alchemy_xp_gained' => 120,
                'current_item_id' => $item->id,
                'current_item_name' => $item->name,
            ],
        ]);

        $result = $this->section->build($character, $batchCrafting, $batchCrafting->progress);

        $this->assertSame($item->id, $result['current_item_id']);
        $this->assertSame('Status Elixir', $result['current_item_name']);
        $this->assertSame(10, $result['requested_amount']);
        $this->assertSame(4, $result['completed_amount']);
        $this->assertSame(6, $result['remaining_amount']);
        $this->assertSame(10, $result['alchemy_amount_progress']['requested_amount']);
        $this->assertSame(4, $result['alchemy_amount_progress']['completed_amount']);
        $this->assertSame(6, $result['alchemy_amount_progress']['remaining_amount']);
        $this->assertSame(120, $result['alchemy_amount_progress']['alchemy_xp_gained']);
        $this->assertNull($result['set_progress']);
        $this->assertNull($result['experience_progress']);
        $this->assertNull($result['event_progress']);
    }
}
