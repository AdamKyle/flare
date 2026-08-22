<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services\Status;

use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Enums\TrinketryBatchMode;
use App\Game\Automation\BatchCrafting\Services\Status\TrinketryStatusSection;
use App\Game\Character\CharacterInventory\Services\BatchCraftingSetService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;

class TrinketryStatusSectionTest extends TestCase
{
    use CreateBatchCrafting, CreateGameSkill, CreateItem, RefreshDatabase;

    private ?TrinketryStatusSection $section;

    protected function setUp(): void
    {
        parent::setUp();

        $this->section = resolve(TrinketryStatusSection::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->section = null;
    }

    public function test_supports_only_trinketry_experience(): void
    {
        $this->assertTrue($this->section->supports(BatchCraftingType::TRINKETRY, 'experience'));
        $this->assertFalse($this->section->supports(BatchCraftingType::TRINKETRY, 'amount'));
        $this->assertFalse($this->section->supports(BatchCraftingType::ALCHEMY, 'experience'));
    }

    public function test_build_reports_factual_progress_and_actions_per_minute(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $item = $this->createItem(['type' => 'trinket', 'name' => 'Status Trinket']);

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'trinketry_xp_gained' => 75,
                'current_item_id' => $item->id,
                'current_item_name' => $item->name,
            ],
        ]);

        $result = $this->section->build($character, $batchCrafting, $batchCrafting->progress);

        $this->assertSame($item->id, $result['current_item_id']);
        $this->assertSame('Status Trinket', $result['current_item_name']);
        $this->assertSame(TrinketryBatchMode::EXPERIENCE->executionWindowSize(), $result['trinketry_progress']['actions_per_minute']);
        $this->assertSame(75, $result['trinketry_progress']['trinketry_xp_gained']);
        $this->assertNull($result['set_progress']);
        $this->assertNull($result['experience_progress']);
        $this->assertNull($result['event_progress']);
    }

    public function test_build_reports_the_characters_trinketry_skill_progress(): void
    {
        $trinketrySkill = $this->createGameSkill(['name' => 'Trinketry', 'type' => SkillTypeValue::CRAFTING->value, 'max_level' => 400]);
        $character = (new CharacterFactory)->createBaseCharacter()->assignSkill($trinketrySkill, 1, false)->givePlayerLocation()->getCharacter();

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'trinketry_xp_gained' => 0,
                'current_item_id' => null,
                'current_item_name' => null,
            ],
        ]);

        $result = $this->section->build($character, $batchCrafting, $batchCrafting->progress);

        $this->assertSame('Trinketry', $result['trinketry_progress']['trinketry_skill']['skill_name']);
        $this->assertArrayHasKey('level', $result['trinketry_progress']['trinketry_skill']);
        $this->assertArrayHasKey('is_maxed', $result['trinketry_progress']['trinketry_skill']);
    }

    public function test_build_exposes_the_crafted_items_set_for_a_direct_keep_disposition(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $item = $this->createItem(['type' => 'trinket', 'name' => 'Status Trinket']);
        $craftedItemsSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($character);

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => [
                'trinketry_xp_gained' => 0,
                'current_item_id' => $item->id,
                'current_item_name' => $item->name,
            ],
        ]);

        $result = $this->section->build($character, $batchCrafting, $batchCrafting->progress);

        $this->assertSame($craftedItemsSet->id, $result['trinketry_progress']['destination_set_id']);
        $this->assertSame($craftedItemsSet->name, $result['trinketry_progress']['destination_set_name']);
    }

    public function test_build_reports_no_destination_for_a_destroy_disposition(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $item = $this->createItem(['type' => 'trinket', 'name' => 'Status Trinket']);
        resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($character);

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::DESTROY->value,
            'progress' => [
                'trinketry_xp_gained' => 0,
                'current_item_id' => $item->id,
                'current_item_name' => $item->name,
            ],
        ]);

        $result = $this->section->build($character, $batchCrafting, $batchCrafting->progress);

        $this->assertNull($result['trinketry_progress']['destination_set_id']);
        $this->assertNull($result['trinketry_progress']['destination_set_name']);
    }

    public function test_build_exposes_the_crafted_items_set_when_current_item_matches_kept_best(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $item = $this->createItem(['type' => 'trinket', 'name' => 'Status Trinket']);
        $craftedItemsSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($character);

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_DESTROY_REST->value,
            'progress' => [
                'trinketry_xp_gained' => 0,
                'current_item_id' => $item->id,
                'current_item_name' => $item->name,
                'trinketry_kept_best' => ['item_id' => $item->id, 'set_slot_id' => 1, 'quality' => 5],
            ],
        ]);

        $result = $this->section->build($character, $batchCrafting, $batchCrafting->progress);

        $this->assertSame($craftedItemsSet->id, $result['trinketry_progress']['destination_set_id']);
    }

    public function test_build_reports_no_destination_when_current_item_was_destroyed_as_not_the_best(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $item = $this->createItem(['type' => 'trinket', 'name' => 'Status Trinket']);
        $keptItem = $this->createItem(['type' => 'trinket', 'name' => 'Kept Trinket']);
        resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($character);

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_DESTROY_REST->value,
            'progress' => [
                'trinketry_xp_gained' => 0,
                'current_item_id' => $item->id,
                'current_item_name' => $item->name,
                'trinketry_kept_best' => ['item_id' => $keptItem->id, 'set_slot_id' => 1, 'quality' => 5],
            ],
        ]);

        $result = $this->section->build($character, $batchCrafting, $batchCrafting->progress);

        $this->assertNull($result['trinketry_progress']['destination_set_id']);
        $this->assertNull($result['trinketry_progress']['destination_set_name']);
    }
}
