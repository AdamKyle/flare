<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\Character;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingActionStatus;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Services\CraftExperienceKeepBestService;
use App\Game\Character\CharacterInventory\Services\BatchCraftingSetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateItem;

class CraftExperienceKeepBestServiceTest extends TestCase
{
    use CreateBatchCrafting, CreateItem, RefreshDatabase;

    private ?Character $character;

    private ?CraftExperienceKeepBestService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $this->character->update(['gold' => 100000]);
        $this->service = resolve(CraftExperienceKeepBestService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->service = null;
    }

    public function test_apply_keeps_the_first_crafted_item_for_an_item_type(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_SELL_REST->value,
            'progress' => ['craft_mode' => 'experience', 'cycle_position' => 0, 'crafting_xp_gained' => 0],
        ]);
        $item = $this->createItem(['name' => 'First Item', 'skill_level_required' => 5]);

        $result = $this->service->apply($batchCrafting, $this->character, $item, BatchCraftingDisposition::KEEP_BEST_SELL_REST, 10);

        $this->assertSame(BatchCraftingActionStatus::KEPT, $result->actionStatus());
        $batchCraftingSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($this->character);
        $this->assertSame(1, $batchCraftingSet->slots()->where('item_id', $item->id)->count());
        $this->assertSame(5, $batchCrafting->refresh()->progress['kept_best']['weapon']['quality']);
    }

    public function test_apply_disposes_of_an_inferior_item_and_keeps_the_existing_best(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_SELL_REST->value,
            'progress' => ['craft_mode' => 'experience', 'cycle_position' => 0, 'crafting_xp_gained' => 0],
        ]);
        $strongItem = $this->createItem(['name' => 'Strong Item', 'skill_level_required' => 50]);
        $this->service->apply($batchCrafting, $this->character, $strongItem, BatchCraftingDisposition::KEEP_BEST_SELL_REST, 10);
        $weakItem = $this->createItem(['name' => 'Weak Item', 'skill_level_required' => 1, 'cost' => 5]);

        $result = $this->service->apply($batchCrafting->refresh(), $this->character, $weakItem, BatchCraftingDisposition::KEEP_BEST_SELL_REST, 5);

        $this->assertSame(BatchCraftingActionStatus::SOLD, $result->actionStatus());
        $batchCraftingSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($this->character);
        $this->assertSame(1, $batchCraftingSet->slots()->where('item_id', $strongItem->id)->count());
        $this->assertSame(0, $batchCraftingSet->slots()->where('item_id', $weakItem->id)->count());
        $this->assertSame(50, $batchCrafting->refresh()->progress['kept_best']['weapon']['quality']);
    }

    public function test_apply_destroys_an_inferior_item_when_destroy_rest_is_selected(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_DESTROY_REST->value,
            'progress' => ['craft_mode' => 'experience', 'cycle_position' => 0, 'crafting_xp_gained' => 0],
        ]);
        $strongItem = $this->createItem(['name' => 'Strong Item', 'skill_level_required' => 50]);
        $this->service->apply($batchCrafting, $this->character, $strongItem, BatchCraftingDisposition::KEEP_BEST_DESTROY_REST, 10);
        $weakItem = $this->createItem(['name' => 'Weak Item', 'skill_level_required' => 1]);

        $result = $this->service->apply($batchCrafting->refresh(), $this->character, $weakItem, BatchCraftingDisposition::KEEP_BEST_DESTROY_REST, 5);

        $this->assertSame(BatchCraftingActionStatus::DESTROYED, $result->actionStatus());
    }

    public function test_apply_displaces_the_existing_best_when_a_stronger_item_is_crafted(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_SELL_REST->value,
            'progress' => ['craft_mode' => 'experience', 'cycle_position' => 0, 'crafting_xp_gained' => 0],
        ]);
        $weakItem = $this->createItem(['name' => 'Weak Item', 'skill_level_required' => 1, 'cost' => 100]);
        $this->service->apply($batchCrafting, $this->character, $weakItem, BatchCraftingDisposition::KEEP_BEST_SELL_REST, 5);
        $strongItem = $this->createItem(['name' => 'Strong Item', 'skill_level_required' => 50]);

        $result = $this->service->apply($batchCrafting->refresh(), $this->character, $strongItem, BatchCraftingDisposition::KEEP_BEST_SELL_REST, 10);

        $this->assertSame(BatchCraftingActionStatus::KEPT, $result->actionStatus());
        $this->assertGreaterThanOrEqual(0, $result->goldGained());
        $this->assertSame(1, $result->additionalSoldCount());
        $this->assertSame(0, $result->additionalDestroyedCount());
        $batchCraftingSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($this->character);
        $this->assertSame(1, $batchCraftingSet->slots()->where('item_id', $strongItem->id)->count());
        $this->assertSame(0, $batchCraftingSet->slots()->where('item_id', $weakItem->id)->count());
        $this->assertSame(50, $batchCrafting->refresh()->progress['kept_best']['weapon']['quality']);
    }

    public function test_apply_destroys_the_displaced_best_when_a_stronger_item_is_crafted_under_destroy_rest(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_DESTROY_REST->value,
            'progress' => ['craft_mode' => 'experience', 'cycle_position' => 0, 'crafting_xp_gained' => 0],
        ]);
        $weakItem = $this->createItem(['name' => 'Weak Item', 'skill_level_required' => 1]);
        $this->service->apply($batchCrafting, $this->character, $weakItem, BatchCraftingDisposition::KEEP_BEST_DESTROY_REST, 5);
        $strongItem = $this->createItem(['name' => 'Strong Item', 'skill_level_required' => 50]);

        $result = $this->service->apply($batchCrafting->refresh(), $this->character, $strongItem, BatchCraftingDisposition::KEEP_BEST_DESTROY_REST, 10);

        $this->assertSame(BatchCraftingActionStatus::KEPT, $result->actionStatus());
        $this->assertSame(0, $result->goldGained());
        $this->assertSame(0, $result->additionalSoldCount());
        $this->assertSame(1, $result->additionalDestroyedCount());
        $batchCraftingSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($this->character);
        $this->assertSame(1, $batchCraftingSet->slots()->where('item_id', $strongItem->id)->count());
        $this->assertSame(0, $batchCraftingSet->slots()->where('item_id', $weakItem->id)->count());
    }

    public function test_apply_displaces_the_best_without_disposal_gold_when_the_previous_item_no_longer_exists(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_SELL_REST->value,
            'progress' => ['craft_mode' => 'experience', 'cycle_position' => 0, 'crafting_xp_gained' => 0, 'kept_best' => ['weapon' => ['item_id' => 999999, 'set_slot_id' => 999999, 'quality' => 1]]],
        ]);
        $strongItem = $this->createItem(['name' => 'Strong Item', 'skill_level_required' => 50]);

        $result = $this->service->apply($batchCrafting, $this->character, $strongItem, BatchCraftingDisposition::KEEP_BEST_SELL_REST, 10);

        $this->assertSame(BatchCraftingActionStatus::KEPT, $result->actionStatus());
        $this->assertSame(0, $result->goldGained());
        $this->assertSame(0, $result->additionalSoldCount());
        $this->assertSame(0, $result->additionalDestroyedCount());
        $batchCraftingSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($this->character);
        $this->assertSame(1, $batchCraftingSet->slots()->where('item_id', $strongItem->id)->count());
    }

    public function test_apply_cannot_displace_another_characters_set_slot_through_stale_bookkeeping(): void
    {
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $otherItem = $this->createItem(['name' => 'Other Characters Item', 'skill_level_required' => 1]);
        $otherPlacement = resolve(BatchCraftingSetService::class)->createItemInBatchCraftingSet($otherCharacter, $otherItem);

        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_SELL_REST->value,
            'progress' => ['craft_mode' => 'experience', 'cycle_position' => 0, 'crafting_xp_gained' => 0, 'kept_best' => ['weapon' => ['item_id' => $otherItem->id, 'set_slot_id' => $otherPlacement['set_slot']->id, 'quality' => 1]]],
        ]);
        $strongItem = $this->createItem(['name' => 'Strong Item', 'skill_level_required' => 50]);

        $result = $this->service->apply($batchCrafting, $this->character, $strongItem, BatchCraftingDisposition::KEEP_BEST_SELL_REST, 10);

        $this->assertSame(BatchCraftingActionStatus::KEPT, $result->actionStatus());
        $this->assertSame(0, $result->additionalSoldCount());
        $this->assertSame(0, $result->additionalDestroyedCount());
        $otherCharactersSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($otherCharacter);
        $this->assertSame(1, $otherCharactersSet->slots()->where('item_id', $otherItem->id)->count());
    }

    public function test_apply_keys_the_retained_best_by_item_type_so_a_different_item_type_does_not_compete(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_SELL_REST->value,
            'progress' => ['craft_mode' => 'experience', 'cycle_position' => 0, 'crafting_xp_gained' => 0],
        ]);
        $helmet = $this->createItem(['name' => 'Strong Helmet', 'type' => 'helmet', 'crafting_type' => 'armour', 'skill_level_required' => 50]);
        $this->service->apply($batchCrafting, $this->character, $helmet, BatchCraftingDisposition::KEEP_BEST_SELL_REST, 10);
        $body = $this->createItem(['name' => 'Weak Body', 'type' => 'body', 'crafting_type' => 'armour', 'skill_level_required' => 1]);

        $result = $this->service->apply($batchCrafting->refresh(), $this->character, $body, BatchCraftingDisposition::KEEP_BEST_SELL_REST, 5);

        $this->assertSame(BatchCraftingActionStatus::KEPT, $result->actionStatus());
        $progress = $batchCrafting->refresh()->progress;
        $this->assertSame(50, $progress['kept_best']['helmet']['quality']);
        $this->assertSame(1, $progress['kept_best']['body']['quality']);
        $batchCraftingSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($this->character);
        $this->assertSame(1, $batchCraftingSet->slots()->where('item_id', $helmet->id)->count());
        $this->assertSame(1, $batchCraftingSet->slots()->where('item_id', $body->id)->count());
    }

    public function test_apply_replaces_the_existing_best_when_a_new_item_of_equal_quality_is_crafted(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_SELL_REST->value,
            'progress' => ['craft_mode' => 'experience', 'cycle_position' => 0, 'crafting_xp_gained' => 0],
        ]);
        $firstItem = $this->createItem(['name' => 'First Equal Item', 'skill_level_required' => 25]);
        $this->service->apply($batchCrafting, $this->character, $firstItem, BatchCraftingDisposition::KEEP_BEST_SELL_REST, 10);
        $secondItem = $this->createItem(['name' => 'Second Equal Item', 'skill_level_required' => 25]);

        $result = $this->service->apply($batchCrafting->refresh(), $this->character, $secondItem, BatchCraftingDisposition::KEEP_BEST_SELL_REST, 10);

        $this->assertSame(BatchCraftingActionStatus::KEPT, $result->actionStatus());
        $this->assertSame(1, $result->additionalSoldCount());
        $batchCraftingSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($this->character);
        $this->assertSame(1, $batchCraftingSet->slots()->where('item_id', $secondItem->id)->count());
        $this->assertSame(0, $batchCraftingSet->slots()->where('item_id', $firstItem->id)->count());
        $this->assertSame(25, $batchCrafting->refresh()->progress['kept_best']['weapon']['quality']);
    }

    public function test_apply_keeps_the_same_retained_item_without_disposal_when_it_is_crafted_again(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_SELL_REST->value,
            'progress' => ['craft_mode' => 'experience', 'cycle_position' => 0, 'crafting_xp_gained' => 0],
        ]);
        $item = $this->createItem(['name' => 'Repeated Item', 'skill_level_required' => 25]);
        $this->service->apply($batchCrafting, $this->character, $item, BatchCraftingDisposition::KEEP_BEST_SELL_REST, 10);

        $result = $this->service->apply($batchCrafting->refresh(), $this->character, $item, BatchCraftingDisposition::KEEP_BEST_SELL_REST, 10);

        $this->assertSame(BatchCraftingActionStatus::KEPT, $result->actionStatus());
        $this->assertSame(0, $result->additionalSoldCount());
        $this->assertSame(0, $result->additionalDestroyedCount());
        $this->assertSame(0, $result->goldGained());
        $batchCraftingSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($this->character);
        $this->assertSame(1, $batchCraftingSet->slots()->where('item_id', $item->id)->count());
    }

    public function test_apply_displaces_the_existing_best_in_place_when_the_crafted_items_set_is_already_full(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_SELL_REST->value,
            'progress' => ['craft_mode' => 'experience', 'cycle_position' => 0, 'crafting_xp_gained' => 0],
        ]);
        $weakItem = $this->createItem(['name' => 'Weak Item', 'skill_level_required' => 1, 'cost' => 100]);
        $this->service->apply($batchCrafting, $this->character, $weakItem, BatchCraftingDisposition::KEEP_BEST_SELL_REST, 5);
        $batchCraftingSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($this->character);
        $batchCraftingSet->update(['max_slots' => $batchCraftingSet->slots()->count()]);
        $strongItem = $this->createItem(['name' => 'Strong Item', 'skill_level_required' => 50]);

        $this->assertFalse(resolve(BatchCraftingSetService::class)->canAccept($this->character, 1));

        $result = $this->service->apply($batchCrafting->refresh(), $this->character, $strongItem, BatchCraftingDisposition::KEEP_BEST_SELL_REST, 10);

        $this->assertSame(BatchCraftingActionStatus::KEPT, $result->actionStatus());
        $this->assertSame(1, $result->additionalSoldCount());
        $this->assertSame(1, $batchCraftingSet->slots()->where('item_id', $strongItem->id)->count());
        $this->assertSame(0, $batchCraftingSet->slots()->where('item_id', $weakItem->id)->count());
        $this->assertSame(50, $batchCrafting->refresh()->progress['kept_best']['weapon']['quality']);
    }

    public function test_apply_returns_failed_and_ended_when_the_crafted_items_set_cannot_accept_the_item(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP_BEST_SELL_REST->value,
            'progress' => ['craft_mode' => 'experience', 'cycle_position' => 0, 'crafting_xp_gained' => 0],
        ]);
        $batchCraftingSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($this->character);
        $batchCraftingSet->update(['max_slots' => 0]);
        $item = $this->createItem(['name' => 'No Room Item', 'skill_level_required' => 5]);

        $result = $this->service->apply($batchCrafting, $this->character, $item, BatchCraftingDisposition::KEEP_BEST_SELL_REST, 10);

        $this->assertSame(BatchCraftingActionStatus::FAILED, $result->actionStatus());
        $this->assertNotNull($result->endReason());
    }
}
