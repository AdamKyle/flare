<?php

namespace Tests\Unit\Game\Character\CharacterInventory\Services;

use App\Game\Character\CharacterInventory\Services\CharacterInventoryActionService;
use App\Game\Core\Currency\Values\CurrencyCacheType;
use App\Game\Core\Items\Values\ItemType;
use App\Game\Skills\Services\DisenchantService;
use App\Game\Skills\Services\UpdateCharacterSkillsService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\Character\CharacterInventoryServiceFactory;
use Tests\TestCase;
use Tests\Traits\CreateAlchemyBagSlot;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateInventorySets;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Traits\CreateItemSkill;
use Tests\Traits\CreateItemSkillProgression;

class CharacterInventoryActionServiceTest extends TestCase
{
    use CreateAlchemyBagSlot, CreateGem, CreateInventorySets, CreateItem, CreateItemAffix, CreateItemSkill, CreateItemSkillProgression, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?CharacterInventoryActionService $characterInventoryActionService;

    private ?MockInterface $updateCharacterSkillsService = null;

    private ?MockInterface $disenchantService = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $this->updateCharacterSkillsService = Mockery::mock(UpdateCharacterSkillsService::class);
        $this->disenchantService = Mockery::mock(DisenchantService::class);
        $this->characterInventoryActionService = (new CharacterInventoryServiceFactory)->buildActionService(
            $this->updateCharacterSkillsService,
            $this->disenchantService,
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Mockery::close();

    }

    public function test_disenchant_all_items_in_inventory()
    {
        $character = $this->character->inventoryManagement()->giveItemMultipleTimes($this->createItem(['item_prefix_id' => $this->createItemAffix(['type' => 'prefix'])->id]), 75)->getCharacter();
        $character->skills->where('baseSkill.type', SkillTypeValue::DISENCHANTING->value)->first()->update(['xp_max' => 1]);
        $character = $character->refresh();
        $character->skills->where('baseSkill.type', SkillTypeValue::ENCHANTING->value)->first()->update(['xp_max' => 1]);
        $character = $character->refresh();
        $this->updateCharacterSkillsService->shouldReceive('updateCharacterCraftingSkills')->once();

        $result = $this->characterInventoryActionService->disenchantAll($character->inventory->slots, $character);

        $this->assertEquals(200, $result['status']);
        $this->assertTrue(str_contains($result['message'], 'Skill Levels in Disenchanting.'));
        $this->assertTrue(str_contains($result['message'], 'Skill Levels in Enchanting.'));
    }

    public function test_cannot_delete_item_that_doesnt_exist()
    {
        $character = $this->character->getCharacter();

        $result = $this->characterInventoryActionService->deleteItem($character, 56788);

        $this->assertSame([422, 'No matching item to destroy.'], [$result['status'], $result['message']]);
    }

    public function test_cannot_delete_item_that_is_equipped()
    {
        $character = $this->character->inventoryManagement()->giveItem($this->createItem(), true, 'left_hand')->getCharacter();
        $equippedSlot = $character->inventory->slots()->where('equipped', true)->first();

        $result = $this->characterInventoryActionService->deleteItem($character, $equippedSlot->item_id);

        $this->assertSame([422, 'No matching item to destroy.'], [$result['status'], $result['message']]);
    }

    public function test_can_delete_item_from_inventory()
    {
        $item = $this->createItem();
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $result = $this->characterInventoryActionService->deleteItem($character, $item->id);

        $this->assertSame([200, 'Destroyed item: '.$item->affix_name.'.'], [$result['status'], $result['message']]);
    }

    public function test_can_delete_artifact_with_item_skill_progression_from_inventory()
    {
        $item = $this->createItem(['type' => 'artifact']);
        $itemSkill = $this->createItemSkill([
            'name' => 'parent',
            'description' => 'sample',
            'base_damage_mod' => 0.10,
            'max_level' => 10,
            'total_kills_needed' => 100,
        ]);
        $this->createItemSkillProgression([
            'item_id' => $item->id,
            'item_skill_id' => $itemSkill->id,
        ]);
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $result = $this->characterInventoryActionService->deleteItem($character, $item->id);

        $this->assertSame([422, 'No matching item to destroy.'], [$result['status'], $result['message']]);
    }

    public function test_delete_all_items_in_inventory_with_out_destroying_usable_or_quest_items_all_artifacts()
    {
        $artifact = $this->createItem(['type' => 'artifact']);
        $itemSkill = $this->createItemSkill([
            'name' => 'parent',
            'description' => 'sample',
            'base_damage_mod' => 0.10,
            'max_level' => 10,
            'total_kills_needed' => 100,
        ]);
        $this->createItemSkillProgression([
            'item_id' => $artifact->id,
            'item_skill_id' => $itemSkill->id,
        ]);
        $regularItem = $this->createItem();
        $questItem = $this->createItem(['type' => 'quest']);
        $alchemy = $this->createItem(['type' => 'alchemy']);
        $character = $this->character->inventoryManagement()
            ->giveItem($artifact)
            ->giveItem($regularItem)
            ->giveItem($questItem)
            ->giveItem($alchemy)
            ->getCharacter();

        $result = $this->characterInventoryActionService->destroyAllItems($character);
        $character = $character->refresh();

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('Destroyed all items.', $result['message']);
        $this->assertCount(1, $character->inventory->slots->where('item.type', 'alchemy'));
    }

    public function test_disenchant_all_items_has_nothing_to_disenchant()
    {
        $character = $this->character->getCharacter();

        $result = $this->characterInventoryActionService->disenchantAllInventoryItems($character);

        $this->assertSame([200, 'You have nothing to disenchant.'], [$result['status'], $result['message']]);
    }

    public function test_disenchant_all_items()
    {
        $character = $this->character->inventoryManagement()->giveItem($this->createItem(['item_suffix_id' => $this->createItemAffix(['type' => 'suffix'])]))->getCharacter();
        $this->updateCharacterSkillsService->shouldReceive('updateCharacterCraftingSkills')->once();

        $result = $this->characterInventoryActionService->disenchantAllInventoryItems($character);

        $this->assertEquals(200, $result['status']);
        $character = $character->refresh();

        $this->assertEmpty($character->inventory->slots);
    }

    public function test_cannot_destroy_alchemy_item_you_do_not_have()
    {
        $character = $this->character->getCharacter();

        $result = $this->characterInventoryActionService->destroyAlchemyItem($character, 1);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('No alchemy item found to destroy.', $result['message']);
    }

    public function test_cannot_destroy_alchemy_item_when_character_has_no_alchemy_bag()
    {
        $character = $this->character->getCharacter();
        $character->alchemyBag()->delete();

        $result = $this->characterInventoryActionService->destroyAlchemyItem($character->refresh(), 1);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('No alchemy item found to destroy.', $result['message']);
    }

    public function test_can_delete_alchemy_item()
    {
        $alchemyItem = $this->createItem(['type' => 'alchemy']);
        $character = $this->character->getCharacter();

        $slot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $alchemyItem->id,
            'amount' => 2,
        ]);

        $result = $this->characterInventoryActionService->destroyAlchemyItem($character, $slot->id);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('Destroyed Alchemy Item: '.$alchemyItem->name.'.', $result['message']);
        $this->assertEquals(0, $character->alchemyBag->slots()->where('id', $slot->id)->count());
    }

    public function test_can_delete_all_alchemy_item()
    {
        $firstAlchemyItem = $this->createItem(['type' => 'alchemy']);
        $secondAlchemyItem = $this->createItem(['type' => 'alchemy']);
        $character = $this->character->getCharacter();
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();
        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $firstAlchemyItem->id,
            'amount' => 2,
        ]);
        $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $secondAlchemyItem->id,
            'amount' => 3,
        ]);
        $otherSlot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $otherCharacter->alchemyBag->id,
            'character_id' => $otherCharacter->id,
            'item_id' => $firstAlchemyItem->id,
            'amount' => 4,
        ]);

        $result = $this->characterInventoryActionService->destroyAllAlchemyItems($character);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('Destroyed All Alchemy Items.', $result['message']);
        $this->assertEquals(0, $character->alchemyBag->slots()->count());
        $this->assertEquals(4, $otherSlot->refresh()->amount);
    }

    public function test_destroy_all_alchemy_items_does_not_touch_normal_or_quest_inventory(): void
    {
        $normalItem = $this->createItem(['type' => ItemType::WEAPON->value]);
        $questItem = $this->createItem(['type' => 'quest']);
        $character = $this->character->inventoryManagement()
            ->giveItem($normalItem)
            ->giveItem($questItem)
            ->getCharacter();
        $this->characterInventoryActionService->destroyAllAlchemyItems($character);

        $this->assertEquals(1, $character->inventory->slots()->where('item_id', $normalItem->id)->count());
        $this->assertEquals(1, $character->inventory->slots()->where('item_id', $questItem->id)->count());
    }

    public function test_destroy_all_alchemy_items_does_not_touch_gem_bag_slots(): void
    {
        $gem = $this->createGem();
        $character = $this->character->gemBagManagement()->assignGemStackToBag($gem->id, 2)->getCharacter();
        $gemSlot = $character->gemBag->gemSlots()->where('gem_id', $gem->id)->first();
        $this->characterInventoryActionService->destroyAllAlchemyItems($character);

        $this->assertEquals(2, $gemSlot->refresh()->amount);
    }

    public function test_compensation_cache_cannot_be_destroyed_individually(): void
    {
        $cacheItem = $this->createItem([
            'type' => 'alchemy',
            'usable' => true,
            'currency_cache_type' => CurrencyCacheType::GOLD,
            'cache_amount' => 1000,
        ]);
        $character = $this->character->getCharacter();

        $slot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $cacheItem->id,
            'amount' => 1,
        ]);

        $result = $this->characterInventoryActionService->destroyAlchemyItem($character, $slot->id);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('Compensation Caches cannot be destroyed. They can only be used.', $result['message']);
        $this->assertNotNull($slot->fresh());
        $this->assertNotNull($cacheItem->fresh());
    }

    public function test_destroy_all_alchemy_items_preserves_compensation_caches(): void
    {
        $alchemyItem = $this->createItem(['type' => 'alchemy']);
        $cacheItem = $this->createItem([
            'type' => 'alchemy',
            'usable' => true,
            'currency_cache_type' => CurrencyCacheType::SHARDS,
            'cache_amount' => 1000,
        ]);
        $character = $this->character->getCharacter();
        $ordinarySlot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $alchemyItem->id,
            'amount' => 3,
        ]);
        $cacheSlot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $cacheItem->id,
            'amount' => 1,
        ]);
        $this->characterInventoryActionService->destroyAllAlchemyItems($character);

        $this->assertNull($ordinarySlot->fresh());
        $this->assertNotNull($cacheSlot->fresh());
    }

    public function test_disenchant_all_items_in_inventory_ignores_items_that_are_not_eligible_for_disenchanting(): void
    {
        $prefix = $this->createItemAffix(['type' => 'prefix']);
        $eligibleItem = $this->createItem(['item_prefix_id' => $prefix->id]);
        $questItem = $this->createItem(['type' => 'quest', 'item_prefix_id' => $prefix->id]);
        $character = $this->character->inventoryManagement()
            ->giveItem($eligibleItem)
            ->giveItem($questItem)
            ->getCharacter();
        $this->updateCharacterSkillsService->shouldReceive('updateCharacterCraftingSkills')->once();
        $this->characterInventoryActionService->disenchantAllInventoryItems($character);
        $character = $character->refresh();

        $this->assertCount(1, $character->inventory->slots->where('item.type', 'quest'));
        $this->assertCount(0, $character->inventory->slots->where('item_id', $eligibleItem->id));
    }

    public function test_cannot_sell_item_that_does_not_exist(): void
    {
        $character = $this->character->getCharacter();

        $result = $this->characterInventoryActionService->sellItem($character, 56788);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('No item found to be sell.', $result['message']);
    }

    public function test_can_sell_item_from_inventory(): void
    {
        $item = $this->createItem(['type' => 'weapon', 'cost' => 100]);
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $slotId = $character->inventory->slots()->where('item_id', $item->id)->first()->id;

        $result = $this->characterInventoryActionService->sellItem($character, $item->id);

        $this->assertEquals(200, $result['status']);
        $this->assertTrue(str_contains($result['message'], 'Sold '.$item->affix_name.' for a total of'));
        $this->assertSame(0, $character->inventory->slots()->where('id', $slotId)->count());
    }

    public function test_cannot_disenchant_item_that_does_not_exist(): void
    {
        $character = $this->character->getCharacter();

        $result = $this->characterInventoryActionService->disenchantItem($character, 56788);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('No item found to disenchant.', $result['message']);
    }

    public function test_disenchant_item_delegates_the_owned_inventory_slot_to_the_disenchant_service(): void
    {
        $prefix = $this->createItemAffix(['type' => 'prefix']);
        $item = $this->createItem(['item_prefix_id' => $prefix->id]);
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();
        $slotId = $character->inventory->slots()->where('item_id', $item->id)->first()->id;
        $this->disenchantService->shouldReceive('setUp')
            ->once()
            ->with(Mockery::on(fn ($setUpCharacter) => $setUpCharacter->id === $character->id))
            ->andReturnSelf();
        $this->disenchantService->shouldReceive('disenchantItem')
            ->once()
            ->with(Mockery::on(fn ($slot) => $slot->id === $slotId))
            ->andReturn(['status' => 200, 'message' => 'Disenchanted item.']);

        $result = $this->characterInventoryActionService->disenchantItem($character, $item->id);

        $this->assertEquals(200, $result['status']);
    }
}
