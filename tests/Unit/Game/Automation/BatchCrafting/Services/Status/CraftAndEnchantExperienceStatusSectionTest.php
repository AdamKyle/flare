<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services\Status;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Services\Status\CraftAndEnchantExperienceStatusSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateBatchCrafting;

class CraftAndEnchantExperienceStatusSectionTest extends TestCase
{
    use CreateBatchCrafting, RefreshDatabase;

    private ?CraftAndEnchantExperienceStatusSection $section;

    protected function setUp(): void
    {
        parent::setUp();

        $this->section = resolve(CraftAndEnchantExperienceStatusSection::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->section = null;
    }

    public function test_supports_only_craft_and_enchant_experience(): void
    {
        $this->assertTrue($this->section->supports(BatchCraftingType::CRAFT_AND_ENCHANT, 'experience'));
        $this->assertFalse($this->section->supports(BatchCraftingType::CRAFT_AND_ENCHANT, 'amount'));
        $this->assertFalse($this->section->supports(BatchCraftingType::CRAFT, 'experience'));
    }

    public function test_build_reports_experience_progress_facts(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [
                'craft_enchant_mode' => 'experience',
                'cycle_position' => 3,
                'crafting_xp_gained' => 150,
                'enchanting_xp_gained' => 75,
                'current_item_id' => 42,
                'current_item_name' => 'Progress Dagger',
                'current_crafting_type' => 'weapon',
                'current_prefix_name' => 'Sharp',
                'current_suffix_name' => null,
                'listing_price' => null,
            ],
        ]);

        $result = $this->section->build($character, $batchCrafting, $batchCrafting->progress);

        $this->assertSame(42, $result['current_item_id']);
        $this->assertSame('Progress Dagger', $result['current_item_name']);
        $this->assertSame('Sharp', $result['current_prefix_name']);
        $this->assertNull($result['current_suffix_name']);
        $this->assertSame(23, $result['experience_progress']['actions_per_minute']);
        $this->assertSame(3, $result['experience_progress']['current_cycle_position']);
        $this->assertSame(150, $result['experience_progress']['crafting_xp_gained']);
        $this->assertSame(75, $result['experience_progress']['enchanting_xp_gained']);
        $this->assertNotNull($result['experience_progress']['enchanting_skill']);
        $this->assertNull($result['set_progress']);
        $this->assertNull($result['event_progress']);
    }
}
