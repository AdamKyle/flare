<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Services\Capabilities;

use App\Game\Automation\BatchCrafting\Services\Capabilities\HolyOilsBatchCraftingCapabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateAlchemyBagSlot;
use Tests\Traits\CreateHolyStack;
use Tests\Traits\CreateInventorySets;
use Tests\Traits\CreateItem;

class HolyOilsBatchCraftingCapabilityServiceTest extends TestCase
{
    use CreateAlchemyBagSlot, CreateHolyStack, CreateInventorySets, CreateItem, RefreshDatabase;

    private ?HolyOilsBatchCraftingCapabilityService $service;

    private ?CharacterFactory $characterFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $this->service = resolve(HolyOilsBatchCraftingCapabilityService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->characterFactory = null;
        $this->service = null;
    }

    public function test_build_reports_false_when_no_eligible_target_or_oil_exists(): void
    {
        $result = $this->service->build($this->characterFactory->getCharacter());

        $this->assertFalse($result['can_holy_oils']);
    }

    public function test_build_reports_false_when_only_a_target_exists(): void
    {
        $targetItem = $this->createItem(['type' => 'weapon', 'holy_stacks' => 5]);
        $character = $this->characterFactory->inventoryManagement()->giveItem($targetItem)->getCharacter();

        $result = $this->service->build($character->refresh());

        $this->assertFalse($result['can_holy_oils']);
    }

    public function test_build_reports_true_when_both_a_target_and_an_oil_exist(): void
    {
        $targetItem = $this->createItem(['type' => 'weapon', 'holy_stacks' => 5]);
        $character = $this->characterFactory->inventoryManagement()->giveItem($targetItem)->getCharacter();

        $oil = $this->createItem(['type' => 'alchemy', 'holy_level' => 1, 'can_use_on_other_items' => true]);
        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $oil->id,
            'amount' => 1,
        ]);

        $result = $this->service->build($character->refresh());

        $this->assertTrue($result['can_holy_oils']);
    }

    public function test_build_reports_true_when_oil_exists_and_target_is_only_in_a_set(): void
    {
        $targetItem = $this->createItem(['type' => 'weapon', 'holy_stacks' => 5]);
        $character = $this->characterFactory->getCharacter();

        $set = $this->createInventorySet(['character_id' => $character->id, 'is_equipped' => false]);
        $this->createInventorySetSlot(['inventory_set_id' => $set->id, 'item_id' => $targetItem->id]);

        $oil = $this->createItem(['type' => 'alchemy', 'holy_level' => 1, 'can_use_on_other_items' => true]);
        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $oil->id,
            'amount' => 1,
        ]);

        $result = $this->service->build($character->refresh());

        $this->assertTrue($result['can_holy_oils']);
    }

    public function test_build_reports_false_when_oil_exists_but_only_target_is_a_trinket(): void
    {
        $trinketItem = $this->createItem(['type' => 'trinket', 'holy_stacks' => 5]);
        $character = $this->characterFactory->inventoryManagement()->giveItem($trinketItem)->getCharacter();

        $oil = $this->createItem(['type' => 'alchemy', 'holy_level' => 1, 'can_use_on_other_items' => true]);
        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $oil->id,
            'amount' => 1,
        ]);

        $result = $this->service->build($character->refresh());

        $this->assertFalse($result['can_holy_oils']);
    }

    public function test_build_reports_false_when_oil_exists_but_only_target_is_an_artifact(): void
    {
        $artifactItem = $this->createItem(['type' => 'artifact', 'holy_stacks' => 5]);
        $character = $this->characterFactory->inventoryManagement()->giveItem($artifactItem)->getCharacter();

        $oil = $this->createItem(['type' => 'alchemy', 'holy_level' => 1, 'can_use_on_other_items' => true]);
        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $oil->id,
            'amount' => 1,
        ]);

        $result = $this->service->build($character->refresh());

        $this->assertFalse($result['can_holy_oils']);
    }

    public function test_build_reports_false_when_the_only_target_is_already_saturated(): void
    {
        $targetItem = $this->createItem(['type' => 'weapon', 'holy_stacks' => 1]);

        $this->createHolyStack([
            'item_id' => $targetItem->id,
            'devouring_darkness_bonus' => 0.10,
            'stat_increase_bonus' => 0.10,
        ]);

        $targetItem = $targetItem->refresh();
        $character = $this->characterFactory->inventoryManagement()->giveItem($targetItem)->getCharacter();

        $oil = $this->createItem(['type' => 'alchemy', 'holy_level' => 1, 'can_use_on_other_items' => true]);
        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $oil->id,
            'amount' => 1,
        ]);

        $result = $this->service->build($character->refresh());

        $this->assertFalse($result['can_holy_oils']);
    }
}
