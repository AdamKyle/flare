<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\Character;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingActionStatus;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Services\AlchemyExperienceKeepBestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateAlchemyBagSlot;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateItem;

class AlchemyExperienceKeepBestServiceTest extends TestCase
{
    use CreateAlchemyBagSlot, CreateBatchCrafting, CreateItem, RefreshDatabase;

    private ?Character $character;

    private ?AlchemyExperienceKeepBestService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $this->service = resolve(AlchemyExperienceKeepBestService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->service = null;
    }

    public function test_apply_keeps_the_first_transmuted_item_as_the_retained_best(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_mode' => 'experience', 'alchemy_xp_gained' => 0, 'current_item_id' => null, 'current_item_name' => null],
        ]);
        $item = $this->createItem(['type' => 'alchemy', 'crafting_type' => 'alchemy', 'skill_level_required' => 5]);
        $slotId = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $this->character->alchemyBag->id,
            'character_id' => $this->character->id,
            'item_id' => $item->id,
            'amount' => 1,
        ])->id;

        $result = $this->service->apply($batchCrafting, $this->character, $item, $slotId);

        $this->assertSame(BatchCraftingActionStatus::KEPT, $result->actionStatus());
        $this->assertSame(5, $batchCrafting->refresh()->progress['alchemy_kept_best']['quality']);
        $this->assertSame(1, $this->character->alchemyBag->slots()->where('id', $slotId)->count());
    }

    public function test_apply_replaces_the_retained_best_when_a_stronger_item_is_transmuted(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_mode' => 'experience', 'alchemy_xp_gained' => 0, 'current_item_id' => null, 'current_item_name' => null],
        ]);
        $weakItem = $this->createItem(['type' => 'alchemy', 'crafting_type' => 'alchemy', 'skill_level_required' => 1]);
        $weakSlotId = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $this->character->alchemyBag->id,
            'character_id' => $this->character->id,
            'item_id' => $weakItem->id,
            'amount' => 1,
        ])->id;
        $this->service->apply($batchCrafting, $this->character, $weakItem, $weakSlotId);

        $strongItem = $this->createItem(['type' => 'alchemy', 'crafting_type' => 'alchemy', 'skill_level_required' => 50]);
        $strongSlotId = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $this->character->alchemyBag->id,
            'character_id' => $this->character->id,
            'item_id' => $strongItem->id,
            'amount' => 1,
        ])->id;

        $result = $this->service->apply($batchCrafting->refresh(), $this->character, $strongItem, $strongSlotId);

        $this->assertSame(BatchCraftingActionStatus::KEPT, $result->actionStatus());
        $this->assertSame(50, $batchCrafting->refresh()->progress['alchemy_kept_best']['quality']);
        $this->assertSame(0, $this->character->alchemyBag->slots()->where('id', $weakSlotId)->count());
        $this->assertSame(1, $this->character->alchemyBag->slots()->where('id', $strongSlotId)->count());
    }

    public function test_apply_destroys_a_weaker_newly_transmuted_item_and_keeps_the_existing_best(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_mode' => 'experience', 'alchemy_xp_gained' => 0, 'current_item_id' => null, 'current_item_name' => null],
        ]);
        $strongItem = $this->createItem(['type' => 'alchemy', 'crafting_type' => 'alchemy', 'skill_level_required' => 50]);
        $strongSlotId = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $this->character->alchemyBag->id,
            'character_id' => $this->character->id,
            'item_id' => $strongItem->id,
            'amount' => 1,
        ])->id;
        $this->service->apply($batchCrafting, $this->character, $strongItem, $strongSlotId);

        $weakItem = $this->createItem(['type' => 'alchemy', 'crafting_type' => 'alchemy', 'skill_level_required' => 1]);
        $weakSlotId = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $this->character->alchemyBag->id,
            'character_id' => $this->character->id,
            'item_id' => $weakItem->id,
            'amount' => 1,
        ])->id;

        $result = $this->service->apply($batchCrafting->refresh(), $this->character, $weakItem, $weakSlotId);

        $this->assertSame(BatchCraftingActionStatus::DESTROYED, $result->actionStatus());
        $this->assertSame(50, $batchCrafting->refresh()->progress['alchemy_kept_best']['quality']);
        $this->assertSame(0, $this->character->alchemyBag->slots()->where('id', $weakSlotId)->count());
        $this->assertSame(1, $this->character->alchemyBag->slots()->where('id', $strongSlotId)->count());
    }

    public function test_apply_replaces_the_retained_best_when_a_new_item_of_equal_quality_is_transmuted(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_mode' => 'experience', 'alchemy_xp_gained' => 0, 'current_item_id' => null, 'current_item_name' => null],
        ]);
        $firstItem = $this->createItem(['type' => 'alchemy', 'crafting_type' => 'alchemy', 'skill_level_required' => 25]);
        $firstSlotId = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $this->character->alchemyBag->id,
            'character_id' => $this->character->id,
            'item_id' => $firstItem->id,
            'amount' => 1,
        ])->id;
        $this->service->apply($batchCrafting, $this->character, $firstItem, $firstSlotId);

        $secondItem = $this->createItem(['type' => 'alchemy', 'crafting_type' => 'alchemy', 'skill_level_required' => 25]);
        $secondSlotId = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $this->character->alchemyBag->id,
            'character_id' => $this->character->id,
            'item_id' => $secondItem->id,
            'amount' => 1,
        ])->id;

        $result = $this->service->apply($batchCrafting->refresh(), $this->character, $secondItem, $secondSlotId);

        $this->assertSame(BatchCraftingActionStatus::KEPT, $result->actionStatus());
        $this->assertSame(25, $batchCrafting->refresh()->progress['alchemy_kept_best']['quality']);
        $this->assertSame(1, $this->character->alchemyBag->slots()->where('id', $secondSlotId)->count());
        $this->assertSame(0, $this->character->alchemyBag->slots()->where('id', $firstSlotId)->count());
    }

    public function test_apply_keeps_the_retained_item_in_the_alchemy_bag_without_disposal_when_it_is_the_same_slot(): void
    {
        $batchCrafting = $this->createBatchCrafting([
            'character_id' => $this->character->id,
            'user_id' => $this->character->user_id,
            'batch_type' => BatchCraftingType::ALCHEMY->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'progress' => ['alchemy_mode' => 'experience', 'alchemy_xp_gained' => 0, 'current_item_id' => null, 'current_item_name' => null],
        ]);
        $item = $this->createItem(['type' => 'alchemy', 'crafting_type' => 'alchemy', 'skill_level_required' => 25]);
        $slotId = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $this->character->alchemyBag->id,
            'character_id' => $this->character->id,
            'item_id' => $item->id,
            'amount' => 1,
        ])->id;
        $this->service->apply($batchCrafting, $this->character, $item, $slotId);

        $result = $this->service->apply($batchCrafting->refresh(), $this->character, $item, $slotId);

        $this->assertSame(BatchCraftingActionStatus::KEPT, $result->actionStatus());
        $this->assertSame(1, $this->character->alchemyBag->slots()->where('id', $slotId)->count());
    }
}
