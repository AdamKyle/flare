<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\Character;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingActionStatus;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingEndReason;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Services\TrinketryKeepBestService;
use App\Game\Character\CharacterInventory\Services\BatchCraftingSetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateItem;

class TrinketryKeepBestServiceTest extends TestCase
{
    use CreateBatchCrafting, CreateItem, RefreshDatabase;

    private ?Character $character;

    private ?TrinketryKeepBestService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $this->service = resolve(TrinketryKeepBestService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->service = null;
    }

    public function test_apply_keeps_the_first_crafted_trinket_as_the_retained_best(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['trinketry_xp_gained' => 0, 'current_item_id' => null, 'current_item_name' => null],
        ]);
        $item = $this->createItem(['type' => 'trinket', 'skill_level_required' => 5]);

        $result = $this->service->apply($batchCrafting, $this->character, $item);

        $this->assertSame(BatchCraftingActionStatus::KEPT, $result->actionStatus());
        $this->assertSame(5, $batchCrafting->refresh()->progress['trinketry_kept_best']['quality']);
        $batchCraftingSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($this->character);
        $this->assertSame(1, $batchCraftingSet->slots()->where('item_id', $item->id)->count());
    }

    public function test_apply_replaces_the_retained_best_when_a_stronger_trinket_is_crafted(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['trinketry_xp_gained' => 0, 'current_item_id' => null, 'current_item_name' => null],
        ]);
        $weakItem = $this->createItem(['type' => 'trinket', 'skill_level_required' => 1]);
        $this->service->apply($batchCrafting, $this->character, $weakItem);

        $strongItem = $this->createItem(['type' => 'trinket', 'skill_level_required' => 50]);

        $result = $this->service->apply($batchCrafting->refresh(), $this->character, $strongItem);

        $this->assertSame(BatchCraftingActionStatus::KEPT, $result->actionStatus());
        $this->assertSame(50, $batchCrafting->refresh()->progress['trinketry_kept_best']['quality']);
        $batchCraftingSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($this->character);
        $this->assertSame(1, $batchCraftingSet->slots()->where('item_id', $strongItem->id)->count());
        $this->assertSame(0, $batchCraftingSet->slots()->where('item_id', $weakItem->id)->count());
    }

    public function test_apply_destroys_a_weaker_newly_crafted_trinket_and_keeps_the_existing_best(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['trinketry_xp_gained' => 0, 'current_item_id' => null, 'current_item_name' => null],
        ]);
        $strongItem = $this->createItem(['type' => 'trinket', 'skill_level_required' => 50]);
        $this->service->apply($batchCrafting, $this->character, $strongItem);

        $weakItem = $this->createItem(['type' => 'trinket', 'skill_level_required' => 1]);

        $result = $this->service->apply($batchCrafting->refresh(), $this->character, $weakItem);

        $this->assertSame(BatchCraftingActionStatus::DESTROYED, $result->actionStatus());
        $this->assertSame(50, $batchCrafting->refresh()->progress['trinketry_kept_best']['quality']);
        $batchCraftingSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($this->character);
        $this->assertSame(1, $batchCraftingSet->slots()->where('item_id', $strongItem->id)->count());
    }

    public function test_apply_replaces_the_retained_best_when_a_new_trinket_of_equal_quality_is_crafted(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['trinketry_xp_gained' => 0, 'current_item_id' => null, 'current_item_name' => null],
        ]);
        $firstItem = $this->createItem(['type' => 'trinket', 'skill_level_required' => 25]);
        $this->service->apply($batchCrafting, $this->character, $firstItem);

        $secondItem = $this->createItem(['type' => 'trinket', 'skill_level_required' => 25]);

        $result = $this->service->apply($batchCrafting->refresh(), $this->character, $secondItem);

        $this->assertSame(BatchCraftingActionStatus::KEPT, $result->actionStatus());
        $this->assertSame(25, $batchCrafting->refresh()->progress['trinketry_kept_best']['quality']);
        $batchCraftingSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($this->character);
        $this->assertSame(1, $batchCraftingSet->slots()->where('item_id', $secondItem->id)->count());
        $this->assertSame(0, $batchCraftingSet->slots()->where('item_id', $firstItem->id)->count());
    }

    public function test_apply_keeps_the_same_retained_trinket_without_disposal_when_it_is_crafted_again(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['trinketry_xp_gained' => 0, 'current_item_id' => null, 'current_item_name' => null],
        ]);
        $item = $this->createItem(['type' => 'trinket', 'skill_level_required' => 25]);
        $this->service->apply($batchCrafting, $this->character, $item);

        $result = $this->service->apply($batchCrafting->refresh(), $this->character, $item);

        $this->assertSame(BatchCraftingActionStatus::KEPT, $result->actionStatus());
        $batchCraftingSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($this->character);
        $this->assertSame(1, $batchCraftingSet->slots()->where('item_id', $item->id)->count());
    }

    public function test_apply_returns_failed_and_ended_when_the_crafted_items_set_cannot_accept_the_trinket(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::TRINKETRY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['trinketry_xp_gained' => 0, 'current_item_id' => null, 'current_item_name' => null],
        ]);
        $batchCraftingSet = resolve(BatchCraftingSetService::class)->getOrCreateForCharacter($this->character);
        $batchCraftingSet->update(['max_slots' => 0]);
        $item = $this->createItem(['type' => 'trinket', 'skill_level_required' => 25]);

        $result = $this->service->apply($batchCrafting, $this->character, $item);

        $this->assertSame(BatchCraftingActionStatus::FAILED, $result->actionStatus());
        $this->assertSame(BatchCraftingEndReason::FAILED, $result->endReason());
    }
}
