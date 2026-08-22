<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\Character;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingActionStatus;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Services\CraftAndEnchantExperienceKeepBestService;
use App\Game\Character\CharacterInventory\Services\BatchCraftingSetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateItem;

class CraftAndEnchantExperienceKeepBestServiceTest extends TestCase
{
    use CreateBatchCrafting, CreateItem, RefreshDatabase;

    private ?Character $character;

    private ?CraftAndEnchantExperienceKeepBestService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $this->character->update(['gold' => 100000]);
        $this->service = resolve(CraftAndEnchantExperienceKeepBestService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->service = null;
    }

    public function test_apply_keeps_the_first_enchanted_item_for_an_item_type(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_SELL_REST->value,
            'progress' => ['craft_enchant_mode' => 'experience', 'cycle_position' => 0, 'crafting_xp_gained' => 0, 'enchanting_xp_gained' => 0, 'kept_best' => []],
        ]);

        $item = $this->createItem(['name' => 'First Item', 'skill_level_required' => 5]);

        $result = $this->service->apply($batchCrafting, $this->character, $item, BatchCraftingDisposition::KEEP_BEST_SELL_REST, 10);

        $this->assertSame(BatchCraftingActionStatus::KEPT, $result->actionStatus());
        $batchCraftingSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($this->character);
        $this->assertSame(1, $batchCraftingSet->slots()->where('item_id', $item->id)->count());
        $this->assertSame(5, $batchCrafting->refresh()->progress['kept_best']['weapon']['quality']);
    }

    public function test_apply_returns_kept_without_placement_when_the_same_item_is_already_best(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_SELL_REST->value,
            'progress' => ['craft_enchant_mode' => 'experience', 'cycle_position' => 0, 'crafting_xp_gained' => 0, 'enchanting_xp_gained' => 0, 'kept_best' => []],
        ]);

        $item = $this->createItem(['name' => 'Same Item', 'skill_level_required' => 5]);
        $this->service->apply($batchCrafting, $this->character, $item, BatchCraftingDisposition::KEEP_BEST_SELL_REST, 10);

        $result = $this->service->apply($batchCrafting->refresh(), $this->character, $item, BatchCraftingDisposition::KEEP_BEST_SELL_REST, 10);

        $this->assertSame(BatchCraftingActionStatus::KEPT, $result->actionStatus());
        $batchCraftingSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($this->character);
        $this->assertSame(1, $batchCraftingSet->slots()->where('item_id', $item->id)->count());
    }

    public function test_apply_sells_an_inferior_item_and_keeps_the_existing_best(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_SELL_REST->value,
            'progress' => ['craft_enchant_mode' => 'experience', 'cycle_position' => 0, 'crafting_xp_gained' => 0, 'enchanting_xp_gained' => 0, 'kept_best' => []],
        ]);

        $strongItem = $this->createItem(['name' => 'Strong Item', 'skill_level_required' => 50]);
        $this->service->apply($batchCrafting, $this->character, $strongItem, BatchCraftingDisposition::KEEP_BEST_SELL_REST, 10);
        $weakItem = $this->createItem(['name' => 'Weak Item', 'skill_level_required' => 1, 'cost' => 5]);

        $result = $this->service->apply($batchCrafting->refresh(), $this->character, $weakItem, BatchCraftingDisposition::KEEP_BEST_SELL_REST, 5);

        $this->assertSame(BatchCraftingActionStatus::SOLD, $result->actionStatus());
    }

    public function test_apply_destroys_an_inferior_item_when_destroy_rest_is_selected(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_DESTROY_REST->value,
            'progress' => ['craft_enchant_mode' => 'experience', 'cycle_position' => 0, 'crafting_xp_gained' => 0, 'enchanting_xp_gained' => 0, 'kept_best' => []],
        ]);

        $strongItem = $this->createItem(['name' => 'Strong Item', 'skill_level_required' => 50]);
        $this->service->apply($batchCrafting, $this->character, $strongItem, BatchCraftingDisposition::KEEP_BEST_DESTROY_REST, 10);
        $weakItem = $this->createItem(['name' => 'Weak Item', 'skill_level_required' => 1]);

        $result = $this->service->apply($batchCrafting->refresh(), $this->character, $weakItem, BatchCraftingDisposition::KEEP_BEST_DESTROY_REST, 5);

        $this->assertSame(BatchCraftingActionStatus::DESTROYED, $result->actionStatus());
    }

    public function test_apply_disenchants_an_inferior_item_when_disenchant_rest_is_selected(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_DISENCHANT_REST->value,
            'progress' => ['craft_enchant_mode' => 'experience', 'cycle_position' => 0, 'crafting_xp_gained' => 0, 'enchanting_xp_gained' => 0, 'kept_best' => []],
        ]);

        $strongItem = $this->createItem(['name' => 'Strong Item', 'skill_level_required' => 50]);
        $this->service->apply($batchCrafting, $this->character, $strongItem, BatchCraftingDisposition::KEEP_BEST_DISENCHANT_REST, 10);
        $weakItem = $this->createItem(['name' => 'Weak Item', 'skill_level_required' => 1]);

        $result = $this->service->apply($batchCrafting->refresh(), $this->character, $weakItem, BatchCraftingDisposition::KEEP_BEST_DISENCHANT_REST, 5);

        $this->assertSame(BatchCraftingActionStatus::DISENCHANTED, $result->actionStatus());
    }

    public function test_apply_displaces_the_best_and_sells_the_previous_item_when_stronger_is_crafted(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_SELL_REST->value,
            'progress' => ['craft_enchant_mode' => 'experience', 'cycle_position' => 0, 'crafting_xp_gained' => 0, 'enchanting_xp_gained' => 0, 'kept_best' => []],
        ]);

        $weakItem = $this->createItem(['name' => 'Weak Item', 'skill_level_required' => 1, 'cost' => 100]);
        $this->service->apply($batchCrafting, $this->character, $weakItem, BatchCraftingDisposition::KEEP_BEST_SELL_REST, 5);
        $strongItem = $this->createItem(['name' => 'Strong Item', 'skill_level_required' => 50]);

        $result = $this->service->apply($batchCrafting->refresh(), $this->character, $strongItem, BatchCraftingDisposition::KEEP_BEST_SELL_REST, 10);

        $this->assertSame(BatchCraftingActionStatus::KEPT, $result->actionStatus());
        $this->assertSame(1, $result->additionalSoldCount());
        $batchCraftingSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($this->character);
        $this->assertSame(1, $batchCraftingSet->slots()->where('item_id', $strongItem->id)->count());
        $this->assertSame(0, $batchCraftingSet->slots()->where('item_id', $weakItem->id)->count());
    }

    public function test_apply_displaces_the_best_and_destroys_the_previous_item_when_stronger_is_crafted(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_DESTROY_REST->value,
            'progress' => ['craft_enchant_mode' => 'experience', 'cycle_position' => 0, 'crafting_xp_gained' => 0, 'enchanting_xp_gained' => 0, 'kept_best' => []],
        ]);

        $weakItem = $this->createItem(['name' => 'Weak Item', 'skill_level_required' => 1]);
        $this->service->apply($batchCrafting, $this->character, $weakItem, BatchCraftingDisposition::KEEP_BEST_DESTROY_REST, 5);
        $strongItem = $this->createItem(['name' => 'Strong Item', 'skill_level_required' => 50]);

        $result = $this->service->apply($batchCrafting->refresh(), $this->character, $strongItem, BatchCraftingDisposition::KEEP_BEST_DESTROY_REST, 10);

        $this->assertSame(BatchCraftingActionStatus::KEPT, $result->actionStatus());
        $this->assertSame(1, $result->additionalDestroyedCount());
    }

    public function test_apply_displaces_the_best_and_disenchants_the_previous_item_when_stronger_is_crafted(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_DISENCHANT_REST->value,
            'progress' => ['craft_enchant_mode' => 'experience', 'cycle_position' => 0, 'crafting_xp_gained' => 0, 'enchanting_xp_gained' => 0, 'kept_best' => []],
        ]);

        $weakItem = $this->createItem(['name' => 'Weak Item', 'skill_level_required' => 1]);
        $this->service->apply($batchCrafting, $this->character, $weakItem, BatchCraftingDisposition::KEEP_BEST_DISENCHANT_REST, 5);
        $strongItem = $this->createItem(['name' => 'Strong Item', 'skill_level_required' => 50]);

        $result = $this->service->apply($batchCrafting->refresh(), $this->character, $strongItem, BatchCraftingDisposition::KEEP_BEST_DISENCHANT_REST, 10);

        $this->assertSame(BatchCraftingActionStatus::KEPT, $result->actionStatus());
        $this->assertSame(1, $result->additionalDisenchantedCount());
        $batchCraftingSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($this->character);
        $this->assertSame(1, $batchCraftingSet->slots()->where('item_id', $strongItem->id)->count());
    }

    public function test_apply_ends_failed_when_the_crafted_items_set_cannot_accept_the_first_item(): void
    {
        $batchCraftingSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($this->character);
        $batchCraftingSet->update(['max_slots' => 0]);

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT_AND_ENCHANT->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_SELL_REST->value,
            'progress' => ['craft_enchant_mode' => 'experience', 'cycle_position' => 0, 'crafting_xp_gained' => 0, 'enchanting_xp_gained' => 0, 'kept_best' => []],
        ]);

        $item = $this->createItem(['name' => 'Blocked Item', 'skill_level_required' => 5]);

        $result = $this->service->apply($batchCrafting, $this->character, $item, BatchCraftingDisposition::KEEP_BEST_SELL_REST, 10);

        $this->assertSame(BatchCraftingActionStatus::FAILED, $result->actionStatus());
        $this->assertNotNull($result->endReason());
    }
}
