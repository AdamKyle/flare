<?php

namespace Tests\Feature\Game\Core;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Setup\Character\CharacterFactory;

class CharacterInventoryControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateItem,
        CreateItemAffix,
        CreateUser;

    private $character;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()
                                                 ->equipStartingEquipment();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testCanUnEquipItem() {

        $user = $this->character->getUser();

        $this->actingAs($user)->post(route('game.inventory.unequip', ['character' => $this->character->getCharacter()->id]), [
            'item_to_remove' => 1
        ]);

        $character = $this->character->getCharacter();

        $character->inventory->slots->each(function($slot) {
            $this->assertFalse($slot->equipped);
        });
    }

    public function testCannotUnEquipItem() {
        $user = $this->character->getUser();

        $response = $this->actingAs($user)->post(route('game.inventory.unequip', ['character' => $this->character->getCharacter()->id]) , [
            'item_to_remove' => 2
        ])->response;

        $response->assertSessionHas('error', 'No item found to be equipped.');

        $character = $this->character->getCharacter();

        $character->inventory->slots->each(function($slot) {
            $this->assertTrue($slot->equipped);
        });
    }

    public function testCanEquipItem() {

        $user = $this->character->inventoryManagement()->unequipAll()->getCharacterFactory()->getUser();


        $this->actingAs($user)->post(route('game.equip.item', ['character' => $this->character->getCharacter()->id]), [
            'position'   => 'left-hand',
            'slot_id'    => '1',
            'equip_type' => 'weapon',
        ])->response;

        $character = $this->character->getCharacter();

        $character->inventory->slots->each(function($slot) {
            $this->assertTrue($slot->equipped);
        });
    }

    public function testCannotEquipItemWhenCharacterDead() {

        $user = $this->character->updateCharacter(['is_dead' => true])
                                ->inventoryManagement()
                                ->unequipAll()
                                ->getCharacterFactory()
                                ->getUser();

        $response = $this->actingAs($user)->visitRoute('game')->post(route('game.equip.item', ['character' => $this->character->getCharacter()->id]), [
            'position'   => 'left-hand',
            'slot_id'    => '1',
            'equip_type' => 'weapon',
        ])->response;

        $response->assertSessionHas('error', "You are dead and must revive before trying to do that. Dead people can't do things.");
    }

    public function testCannotEquipItemYouDontHave() {
        $user = $this->character->inventoryManagement()->unequipAll()->getCharacterFactory()->getUser();

        $response = $this->actingAs($user)->post(route('game.equip.item', ['character' => $this->character->getCharacter()->id]), [
            'position'   => 'left-hand',
            'slot_id'    => '7',
            'equip_type' => 'weapon',
        ])->response;

        $response->assertSessionHas('error', 'Could not equip item because you either do not have it, or it is equipped already.');

        $character = $this->character->getCharacter();

        $character->inventory->slots->each(function($slot) {
            $this->assertFalse($slot->equipped);
        });
    }

    public function testPutDifferentItemIntoSameSlot() {
        $item = $this->createItem([
            'name' => 'Spear',
            'base_damage' => 6,
            'type' => 'weapon',
        ]);

        $user = $this->character->inventoryManagement()
                                ->giveItem($this->createItem([
                                    'name' => 'Spear',
                                    'base_damage' => 6,
                                    'type' => 'weapon',
                                ]))
                                ->getCharacterFactory()
                                ->getUser();

        $this->actingAs($user)->post(route('game.equip.item', ['character' => $this->character->getCharacter()->id]), [
            'position'   => 'left-hand',
            'slot_id'    => '2',
            'equip_type' => 'weapon',
        ])->response;

        $character = $this->character->getCharacter();

        $slot = $character->inventory->slots->where('id', 2)->where('equipped', true)->first();

        $this->assertNotNull($slot);
        $this->assertEquals($slot->item->name, 'Spear');
        $this->assertEquals($slot->position, 'left-hand');
    }

    public function testCanDestroyItem() {

        $user = $this->character->inventoryManagement()->unequipAll()->getCharacterFactory()->getUser();


        $response = $this->actingAs($user)->post(route('game.destroy.item', ['character' => $this->character->getCharacter()->id]), [
            'slot_id' => '1'
        ])->response;

        $response->assertSessionHas('success', 'Destroyed Rusty Dagger.');

        $this->assertTrue($this->character->getCharacter()->inventory->slots->isEmpty());
    }

    public function testCannotDestroyItemYouDontHave() {
        $user = $this->character->getUser();

        $response = $this->actingAs($user)->post(route('game.destroy.item', ['character' => $this->character->getCharacter()->id]), [
            'slot_id' => '2'
        ])->response;

        $response->assertSessionHas('error', 'You don\'t own that item.');
    }

    public function testCannotDestroyItemYouHaveEquipped() {
        $user = $this->character->getUser();

        $response = $this->actingAs($user)->post(route('game.destroy.item', ['character' => $this->character->getCharacter()->id]), [
            'slot_id' => '1'
        ])->response;

        $response->assertSessionHas('error', 'Cannot destory equipped item.');
    }

    public function testSeeComparePage() {
        
        $user = $this->character->inventoryManagement()
                                ->giveitem($this->createItem([
                                    'name' => 'Spear',
                                    'base_damage' => 6,
                                    'type' => 'weapon',
                                    'crafting_type' => 'weapon',
                                ]))
                                ->getCharacterFactory()
                                ->getUser();

        $this->actingAs($user)->visitRoute('game.inventory.compare', [
            'item_to_equip_type' => 'weapon',
            'slot_id'            => '2',
            'character'          => $this->character->getCharacter()->id
        ])->see('Equipped')->see('Equipped:  <span class="normal-item">Rusty Dagger</span>');
    }

    public function testSeeComparePageForSpell() {
        
        $user = $this->character->inventoryManagement()
                                ->giveitem($this->createItem([
                                    'name' => 'spell',
                                    'base_damage' => 6,
                                    'type' => 'spell-damage',
                                    'crafting_type' => 'spell',
                                ]))
                                ->getCharacterFactory()
                                ->getUser();

        $this->actingAs($user)->visitRoute('game.inventory.compare', [
            'item_to_equip_type' => 'spell',
            'slot_id'            => '2',
            'character'          => $this->character->getCharacter()->id
        ])->see('Equipped')->see('spell');
    }

    public function testSeeComparePageForArmour() {
        $user = $this->character->inventoryManagement()
                                ->giveitem($this->createItem([
                                    'name'             => 'Armour',
                                    'base_damage'      => 6,
                                    'base_ac'          => 6,
                                    'type'             => 'gloves',
                                    'default_position' => 'hands',
                                    'crafting_type'    => 'armour',
                                ]))
                                ->getCharacterFactory()
                                ->getUser();

        $this->actingAs($user)->visitRoute('game.inventory.compare', [
            'slot_id'   => '2',
            'character' => $this->character->getCharacter()->id
        ])->see('Equipped')->see('Armour');
    }

    public function testSeeComparePageForItemWithPrefixAndSuffix() {
        $user = $this->character->inventoryManagement()
                                ->giveitem($this->createItem([
                                    'name' => 'Armour',
                                    'base_damage'      => 6,
                                    'base_ac'          => 6,
                                    'type'             => 'gloves',
                                    'default_position' => 'hands',
                                    'item_suffix_id'   => $this->createItemAffix([
                                        'name' => 'Sample',
                                        'base_healing_mod' => 0.10,
                                        'str_mod' => 0.10,
                                        'type' => 'suffix',
                                        'cost' => 500,
                                    ])->id,
                                    'item_prefix_id'   => $this->createItemAffix([
                                        'name' => 'Sample',
                                        'base_healing_mod' => 0.10,
                                        'dex_mod' => 0.10,
                                        'type' => 'prefix',
                                        'cost' => 500,
                                    ])->id,
                                ]))
                                ->getCharacterFactory()
                                ->getUser();

        $this->actingAs($user)->visitRoute('game.inventory.compare', [
            'item_to_equip_type' => 'gloves',
            'slot_id'            => '2',
            'character'          => $this->character->getCharacter()->id
        ])->see('Equipped')->see('*Sample* Armour *Sample*');
    }

    public function testCannotSeeComparePage() {
        $user = $this->character->inventoryManagement()
                                ->giveitem($this->createItem([
                                    'name'             => 'Armour',
                                    'base_damage'      => 6,
                                    'base_ac'          => 6,
                                    'type'             => 'gloves',
                                    'default_position' => 'hands',
                                    'crafting_type'    => 'armour',
                                ]))
                                ->getCharacterFactory()
                                ->getUser();

        $this->actingAs($user)->visitRoute('game.character.sheet')->visitRoute('game.inventory.compare', [
            'item_to_equip_type' => 'apple-sauce',
            'slot_id'            => '2',
            'character'          => $this->character->getCharacter()->id
        ])->see('Error. Invalid Input.');
    }

    public function testSeeComparePageWithNothingEquipped() {

        $user = $this->character->inventoryManagement()
                                ->unequipAll()
                                ->getCharacterFactory()
                                ->getUser();

        $this->actingAs($user)->visitRoute('game.inventory.compare', [
            'item_to_equip_type' => 'weapon',
            'slot_id'            => '1',
            'character'          => $this->character->getCharacter()->id
        ])->see('Equipped')->see('You have nothing equipped for this item type. Anything is better then nothing.');
    }

    public function testCannotSeeComparePageWithItemNotInYourInventory() {
        $user = $this->character->getUser();

        $this->actingAs($user)->visitRoute('game.character.sheet')->visitRoute('game.inventory.compare', [
            'item_to_equip_type' => 'weapon',
            'slot_id'            => '10',
            'character'          => $this->character->getCharacter()->id
        ])->see('Item not found in your inventory.');
    }

    public function testUnequipAll() {

        $user = $this->character->inventoryManagement()
                                ->giveitem($this->createItem([
                                    'name'             => 'Armour',
                                    'base_damage'      => 6,
                                    'base_ac'          => 6,
                                    'type'             => 'gloves',
                                    'default_position' => 'hands',
                                    'crafting_type'    => 'armour',
                                ]))
                                ->equipItem(2, 'hands')
                                ->getCharacterFactory()
                                ->getUser();

        $response = $this->actingAs($user)->post(route(
            'game.unequip.all', ['character' => $this->character->getCharacter()->id]
        ))->response;

        $response->assertSessionHas('success', 'All items have been removed.');

        $character = $this->character->getCharacter();

        $equipped = $character->inventory->slots->filter(function($slot) {
            return $slot->equipped;
        })->all();

        $this->assertTrue(empty($equipped));
    }
}
