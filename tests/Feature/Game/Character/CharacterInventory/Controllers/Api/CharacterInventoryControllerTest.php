<?php

namespace Tests\Feature\Game\Character\CharacterInventory\Controllers\Api;

use App\Flare\Models\InventorySet;
use App\Flare\Models\ItemSkill;
use App\Flare\Models\SetSlot;
use App\Flare\Values\WeaponTypes;
use App\Game\Character\CharacterInventory\Values\ItemType;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class CharacterInventoryControllerTest extends TestCase
{
    use CreateGem, CreateItem, CreateItemAffix, RefreshDatabase;

    private ?CharacterFactory $character = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function testGetCharacterInventoryApiRequest()
    {
        $character = $this->character->inventoryManagement()->giveItem($this->createItem())->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/inventory');

        $jsonData = json_decode($response->getContent(), true);

        $this->assertNotEmpty($jsonData['inventory']);
    }

    public function testGetCharacterInventoryApiRequestReturnsHealingForEquippedHealingSpell()
    {
        $item = $this->createItem([
            'name' => 'sample',
            'type' => ItemType::SPELL_HEALING->value,
            'base_healing' => 100,
        ]);

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($item, true, 'spell-one')
            ->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/inventory');

        $jsonData = json_decode($response->getContent(), true);

        $this->assertGreaterThan(0, $jsonData['equipped'][0]['healing']);
    }

    public function testGetCharacterInventoryApiRequestReturnsExpectedShapeWithFullCraftedItemsSet()
    {
        $item = $this->createItem();

        $character = $this->character->getCharacter();

        $inventorySet = InventorySet::factory()->create([
            'character_id' => $character->id,
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => 1,
            'is_equipped' => false,
            'can_be_equipped' => false,
        ]);

        SetSlot::factory()->create([
            'inventory_set_id' => $inventorySet->id,
            'item_id' => $item->id,
        ]);

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/inventory');

        $jsonData = json_decode($response->getContent(), true);

        $response->assertStatus(200);
        $this->assertIsArray($jsonData['inventory']);
        $this->assertIsArray($jsonData['usable_items']);
        $this->assertIsArray($jsonData['usable_sets']);
        $this->assertIsArray($jsonData['savable_sets']);
        $this->assertIsArray($jsonData['equipped']);
        $this->assertIsArray($jsonData['quest_items']);
        $this->assertIsArray($jsonData['sets']);
        $this->assertTrue($jsonData['sets'][InventorySet::BATCH_CRAFTING_SET_NAME]['is_batch_crafting_set']);
        $this->assertEquals(1, $jsonData['sets'][InventorySet::BATCH_CRAFTING_SET_NAME]['current_slots']);
        $this->assertEquals(0, $jsonData['sets'][InventorySet::BATCH_CRAFTING_SET_NAME]['remaining_slots']);
        $this->assertEquals(1, $jsonData['sets'][InventorySet::BATCH_CRAFTING_SET_NAME]['max_slots']);
    }

    public function testFailToGetApiItemDetails()
    {
        $character = $this->character->inventoryManagement()->giveItem($this->createItem())->getCharacter();

        $item = $this->createItem();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/inventory/item/'.$item->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('You cannot do that.', $jsonData['message']);
    }

    public function testGetItemDetails()
    {
        $item = $this->createItem();

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/inventory/item/'.$item->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals($item->name, $jsonData['name']);
    }

    public function testGetItemDetailsIncludesRootItemSkillWithChildren(): void
    {
        $rootSkill = ItemSkill::create(['name' => 'Root Artifact Skill', 'description' => 'Root', 'max_level' => 10, 'total_kills_needed' => 10]);
        $childSkill = ItemSkill::create(['name' => 'Child Artifact Skill', 'description' => 'Child', 'max_level' => 10, 'total_kills_needed' => 10, 'parent_id' => $rootSkill->id, 'parent_level_needed' => 1]);
        $item = $this->createItem(['type' => 'artifact', 'item_skill_id' => $rootSkill->id]);
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/inventory/item/'.$item->id);
        $jsonData = json_decode($response->getContent(), true);

        $this->assertSame($rootSkill->id, $jsonData['item_skills'][0]['id']);
        $this->assertSame($childSkill->id, $jsonData['item_skills'][0]['children'][0]['id']);
    }

    public function testGetItemDetailsIncludesItemSkillProgressionRelation(): void
    {
        $rootSkill = ItemSkill::create(['name' => 'Progression Artifact Skill', 'description' => 'Root', 'max_level' => 10, 'total_kills_needed' => 10]);
        $item = $this->createItem(['type' => 'artifact', 'item_skill_id' => $rootSkill->id]);
        $progression = $item->itemSkillProgressions()->create(['item_skill_id' => $rootSkill->id, 'current_level' => 1, 'current_kill' => 0, 'is_training' => false]);
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/inventory/item/'.$item->id);
        $jsonData = json_decode($response->getContent(), true);

        $this->assertSame($progression->id, $jsonData['item_skill_progressions'][0]['id']);
        $this->assertSame($rootSkill->id, $jsonData['item_skill_progressions'][0]['item_skill']['id']);
    }

    public function testGetNonArtifactItemDetailsReturnsEmptySkillArrays(): void
    {
        $item = $this->createItem(['type' => 'weapon', 'item_skill_id' => null]);
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/inventory/item/'.$item->id);
        $jsonData = json_decode($response->getContent(), true);

        $this->assertSame([], $jsonData['item_skills']);
        $this->assertSame([], $jsonData['item_skill_progressions']);
    }

    public function testDestroyItem()
    {
        $item = $this->createItem();

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory/destroy', [
                'slot_id' => $character->inventory->slots->first()->id,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('Destroyed '.$item->affix_name.'.', $jsonData['message']);
    }

    public function testDestroyAllItems()
    {
        $item = $this->createItem();

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory/destroy-all');

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('Destroyed all items.', $jsonData['message']);
    }

    public function testDisenchantAllItems()
    {
        $item = $this->createItem([
            'item_suffix_id' => $this->createItemAffix([
                'type' => 'suffix',
            ]),
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory/disenchant-all');

        $jsonData = json_decode($response->getContent(), true);

        $this->assertTrue(str_contains($jsonData['message'], 'Disenchanted all items and gained'));

        $character = $character->refresh();

        $this->assertEmpty($character->inventory->slots);
    }

    public function testMoveItemToSet()
    {
        $item = $this->createItem();
        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacterFactory()->inventorySetManagement()->createInventorySets(2, true)->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory/move-to-set', [
                'move_to_set' => $character->inventorySets->first()->id,
                'slot_id' => $character->inventory->slots->first()->id,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals($item->affix_name.' Has been moved to: '.$character->inventorySets->first()->name, $jsonData['message']);

        $character = $character->refresh();

        $this->assertEmpty($character->inventory->slots);
    }

    public function testRenameSet()
    {
        $character = $this->character->inventorySetManagement()->createInventorySets()->getCharacter();

        $set = $character->inventorySets()->first();

        $set->update(['name' => 'sample']);

        $character = $character->refresh();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory-set/rename-set', [
                'set_id' => $character->inventorySets->first()->id,
                'set_name' => 'Apples',
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('Renamed set to: Apples', $jsonData['message']);
    }

    public function testSaveEquippedAsSet()
    {
        $character = $this->character->equipStartingEquipment()->inventorySetManagement()->createInventorySets(1, true)->getCharacter();

        $set = $character->inventorySets->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory/save-equipped-as-set', [
                'move_to_set' => $character->inventorySets->first()->id,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals($set->refresh()->name.' is now equipped (equipment has been moved to the set).', $jsonData['message']);
    }

    public function testRemoveItemFromSet()
    {
        $itemToRemove = $this->createItem();
        $character = $this->character
            ->inventoryManagement()
            ->getCharacterFactory()
            ->inventorySetManagement()
            ->createInventorySets(10, true)
            ->putItemInSet($itemToRemove, 0)
            ->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory-set/remove', [
                'inventory_set_id' => $character->inventorySets->first()->id,
                'slot_id' => $character->inventorySets->first()->slots->first()->id,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $character = $character->refresh();

        $setName = $character->inventorySets->first()->name;

        $this->assertEquals('Removed '.$itemToRemove->affix_name.' from '.$setName.' and placed back into your inventory.', $jsonData['message']);
    }

    public function testEmptySet()
    {
        $character = $this->character
            ->inventorySetManagement()
            ->createInventorySets(1, true)
            ->putItemInSet($this->createItem(), 0)
            ->putItemInSet($this->createItem(), 0)
            ->getCharacter();

        $set = $character->inventorySets->first();

        $character = $character->refresh();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory-set/'.$set->id.'/remove-all', [
                'inventory_set_id' => $character->inventorySets->first()->id,
                'slot_id' => $character->inventorySets->first()->slots->first()->id,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('Removed '. 2 .' of '. 2 .' items from '.$set->name.'. If all items were not moved over, it is because your inventory became full.', $jsonData['message']);
    }

    public function testCannotEquipItem()
    {
        $character = $this->character->inventoryManagement()
            ->giveItemMultipleTimes($this->createItem([
                'type' => WeaponTypes::WEAPON,
                'name' => 'To Replace',
            ]))
            ->getCharacterFactory()
            ->inventorySetManagement()
            ->createInventorySets()
            ->createInventorySets()
            ->putItemInSet($this->createItem([
                'type' => WeaponTypes::WEAPON,
                'name' => 'Equipped',
            ]), 0, 'left-hand', true)
            ->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory/equip-item', [
                'position' => 'left-hand',
                'equip_type' => $character->inventory->slots->first()->item->type,
                'slot_id' => 88477,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('The item you are trying to equip as a replacement, does not exist.', $jsonData['message']);
    }

    public function testEquipItem()
    {
        $character = $this->character->inventoryManagement()
            ->giveItemMultipleTimes($this->createItem([
                'type' => WeaponTypes::WEAPON,
                'name' => 'To Replace',
            ]))
            ->getCharacterFactory()
            ->inventorySetManagement()
            ->createInventorySets()
            ->createInventorySets()
            ->putItemInSet($this->createItem([
                'type' => WeaponTypes::WEAPON,
                'name' => 'Equipped',
            ]), 0, 'left-hand', true)
            ->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory/equip-item', [
                'position' => 'left-hand',
                'equip_type' => $character->inventory->slots->first()->item->type,
                'slot_id' => $character->inventory->slots->first()->id,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('Equipped item.', $jsonData['message']);
    }

    public function testUnequipSet()
    {

        $character = $this->character->inventorySetManagement()
            ->createInventorySets(1, true)
            ->putItemInSet($this->createItem(), 0, 'left-hand', true)
            ->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory/unequip', [
                'inventory_set_equipped' => true,
                'item_to_remove' => $character->inventorySets->first()->slots->first()->id,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('Unequipped '.$character->inventorySets->first()->name.'.', $jsonData['message']);
    }

    public function testUnequipItem()
    {

        $character = $this->character->equipStartingEquipment()
            ->getCharacter();

        $slot = $character->inventory->slots()->where('equipped', true)->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory/unequip', [
                'inventory_set_equipped' => false,
                'item_to_remove' => $slot->id,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('Unequipped item: '.$slot->item->affix_name, $jsonData['message']);
    }

    public function testWhenUnequipAllUnequipTheSet()
    {
        $character = $this->character->inventorySetManagement()
            ->createInventorySets(1, true)
            ->putItemInSet($this->createItem(), 0, 'left-hand', true)
            ->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory/unequip-all', [
                'is_set_equipped' => true,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('Unequipped '.$character->inventorySets->first()->name.'.', $jsonData['message']);
    }

    public function testUnequipAllNonSetItems()
    {
        $character = $this->character->equipStartingEquipment()
            ->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory/unequip-all', [
                'is_set_equipped' => false,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('All items have been unequipped.', $jsonData['message']);
    }

    public function testEquipAndItemSet()
    {
        $character = $this->character->inventorySetManagement()
            ->createInventorySets(1, true)
            ->putItemInSet($this->createItem(), 0)
            ->getCharacter();

        $set = $character->inventorySets()->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory-set/equip/'.$set->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals($set->name.' is now equipped', $jsonData['message']);
    }

    public function testUseManyItems()
    {

        Queue::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 30,
            'type' => 'alchemy',
            'affects_skill_type' => SkillTypeValue::TRAINING,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $alchemySlot = $character->alchemyBag->slots()->create([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 1,
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory/use-many-items', [
                'items_to_use' => [
                    $alchemySlot->id,
                ],
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('Used selected items.', $jsonData['message']);
        $this->assertEquals(0, $character->alchemyBag->slots()->where('id', $alchemySlot->id)->count());
    }

    public function testUseSingleAlchemyItem()
    {
        Queue::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 30,
            'type' => 'alchemy',
            'affects_skill_type' => SkillTypeValue::TRAINING,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $character->alchemyBag->slots()->create([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 1,
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory/use-item/'.$item->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('Used selected item.', $jsonData['message']);
        $this->assertEquals(0, $character->alchemyBag->slots()->where('item_id', $item->id)->count());
    }

    public function testDestroyAlchemyItem()
    {
        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 30,
            'type' => 'alchemy',
            'affects_skill_type' => SkillTypeValue::TRAINING,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();
        $alchemySlot = $character->alchemyBag->slots()->create([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 2,
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory/destroy-alchemy-item', [
                'slot_id' => $alchemySlot->id,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('Destroyed Alchemy Item: '.$item->name.'.', $jsonData['message']);
        $this->assertEquals(0, $character->alchemyBag->slots()->where('id', $alchemySlot->id)->count());
    }

    public function testDestroyAlchemyItemRejectsAnotherCharactersSlot(): void
    {
        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 30,
            'type' => 'alchemy',
            'affects_skill_type' => SkillTypeValue::TRAINING,
        ]);
        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();
        $alchemySlot = $otherCharacter->alchemyBag->slots()->create([
            'character_id' => $otherCharacter->id,
            'item_id' => $item->id,
            'amount' => 2,
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory/destroy-alchemy-item', [
                'slot_id' => $alchemySlot->id,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('No alchemy item found to destroy.', $jsonData['message']);
        $this->assertEquals(2, $alchemySlot->refresh()->amount);
    }

    public function testDestroyAllAlchemyItems()
    {
        $alchemyItem = $this->createItem([
            'usable' => true,
            'lasts_for' => 30,
            'type' => 'alchemy',
            'affects_skill_type' => SkillTypeValue::TRAINING,
        ]);
        $normalItem = $this->createItem();
        $gem = $this->createGem();

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($normalItem)
            ->getCharacter();
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();
        $character->alchemyBag->slots()->create([
            'character_id' => $character->id,
            'item_id' => $alchemyItem->id,
            'amount' => 2,
        ]);
        $otherSlot = $otherCharacter->alchemyBag->slots()->create([
            'character_id' => $otherCharacter->id,
            'item_id' => $alchemyItem->id,
            'amount' => 3,
        ]);
        $gemSlot = $character->gemBag->gemSlots()->create([
            'gem_bag_id' => $character->gemBag->id,
            'gem_id' => $gem->id,
            'amount' => 4,
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory/destroy-all-alchemy-items');

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Destroyed All Alchemy Items.', $jsonData['message']);
        $this->assertEquals(0, $character->alchemyBag->slots()->count());
        $this->assertEquals(3, $otherSlot->refresh()->amount);
        $this->assertEquals(1, $character->inventory->slots()->where('item_id', $normalItem->id)->count());
        $this->assertEquals(4, $gemSlot->refresh()->amount);
    }

    public function testSellAllFromSetSellsEligibleCraftedItemsSetSlots(): void
    {
        $weapon = $this->createItem(['type' => 'weapon', 'cost' => 100]);
        $trinket = $this->createItem(['type' => 'trinket']);

        $character = $this->character->getCharacter();

        $set = $character->inventorySets()->create([
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);

        $set->slots()->create(['item_id' => $weapon->id]);
        $trinketSlot = $set->slots()->create(['item_id' => $trinket->id]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory-set/sell-all', [
                'set_id' => $set->id,
            ]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(1, $set->refresh()->slots()->count());
        $this->assertTrue($set->slots()->where('id', $trinketSlot->id)->exists());
    }

    public function testSellAllFromSetRejectsNormalSet(): void
    {
        $item = $this->createItem();

        $character = $this->character
            ->inventorySetManagement()
            ->createInventorySets(1, true)
            ->putItemInSet($item, 0)
            ->getCharacter();

        $set = $character->inventorySets->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory-set/sell-all', [
                'set_id' => $set->id,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('Cannot do that.', $jsonData['message']);
        $this->assertEquals(1, $set->refresh()->slots()->count());
    }

    public function testDestroyAllFromSetDestroysCraftedItemsSetSlots(): void
    {
        $item = $this->createItem(['type' => 'weapon']);

        $character = $this->character->getCharacter();

        $set = $character->inventorySets()->create([
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);

        $set->slots()->create(['item_id' => $item->id]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory-set/destroy-all', [
                'set_id' => $set->id,
            ]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(0, $set->refresh()->slots()->count());
    }

    public function testDisenchantAllFromSetReturns200ForCraftedItemsSet(): void
    {
        $prefix = $this->createItemAffix(['name' => 'Controller Disenchant All Prefix', 'type' => 'prefix']);
        $item = $this->createItem(['type' => 'weapon', 'item_prefix_id' => $prefix->id]);

        $character = $this->character->getCharacter();

        $set = $character->inventorySets()->create([
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);

        $enchantedSlot = $set->slots()->create(['item_id' => $item->id]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory-set/disenchant-all', [
                'set_id' => $set->id,
            ]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFalse($set->slots()->where('id', $enchantedSlot->id)->exists());
    }

    public function testDisenchantAllFromSetReturns422ForNormalSet(): void
    {
        $item = $this->createItem();

        $character = $this->character
            ->inventorySetManagement()
            ->createInventorySets(1, true)
            ->putItemInSet($item, 0)
            ->getCharacter();

        $set = $character->inventorySets->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory-set/disenchant-all', [
                'set_id' => $set->id,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('Cannot do that.', $jsonData['message']);
        $this->assertEquals(1, $set->refresh()->slots()->count());
    }

    public function testDisenchantAllFromSetReturns422ForAnotherCharactersSet(): void
    {
        $prefix = $this->createItemAffix(['name' => 'Controller Other Character Prefix', 'type' => 'prefix']);
        $item = $this->createItem(['item_prefix_id' => $prefix->id]);

        $character = $this->character->getCharacter();
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $otherSet = $otherCharacter->inventorySets()->create([
            'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
            'is_equipped' => false,
            'can_be_equipped' => false,
            'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
            'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
        ]);
        $otherSlot = $otherSet->slots()->create(['item_id' => $item->id]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/inventory-set/disenchant-all', [
                'set_id' => $otherSet->id,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('Cannot do that.', $jsonData['message']);
        $this->assertTrue($otherSet->slots()->where('id', $otherSlot->id)->exists());
    }
}
