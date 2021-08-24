<?php

namespace Tests\Feature\Game\Core;

use App\Flare\Models\Character;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySet;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\SetSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Setup\Character\CharacterFactory;

class CharacterInventoryControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateItem,
        CreateItemAffix,
        CreateUser,
        CreateRole;

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
            'item_to_remove'         => InventorySlot::where('equipped', true)->first()->id,
            'inventory_set_equipped' => false,
        ]);

        $character = Character::first();

        $character->inventory->slots->each(function($slot) {
            $this->assertFalse($slot->equipped);
        });
    }

    public function testCanUnequipSet() {
        $item = $this->createItem([
            'name'                => 'Sample Item',
            'type'                => 'weapon',
            'base_damage'         => 200,
            'cost'                => 100,
            'crafting_type'       => 'weapon',
            'description'         => 'sample',
            'can_resurrect'       => false,
            'resurrection_chance' => 0.0
        ]);

        $character = $this->character->inventorySetManagement()->createInventorySets(10)->putItemInSet($item, 0, 'left-hand', true);

        $this->actingAs($character->getCharacter()->user)->post(route('game.inventory.unequip', ['character' => $character->getCharacter()->id]), [
            'inventory_set_equipped' => true,
        ]);

        $character = $this->character->getCharacter();

        $character->inventory->slots->each(function($slot) {
            $this->assertFalse($slot->equipped);
        });

        $character->inventorySets->filter(function($set) {
            $this->assertTrue($set->slots()->where('equipped', true)->get()->isEmpty());
            $this->assertFalse($set->is_equipped);
        });
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
            ->equipItem('hands', 'Armour')
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

    public function testCanUnequipAllWithSet() {
        $item = $this->createItem([
            'name'                => 'Sample Item',
            'type'                => 'weapon',
            'base_damage'         => 200,
            'cost'                => 100,
            'crafting_type'       => 'weapon',
            'description'         => 'sample',
            'can_resurrect'       => false,
            'resurrection_chance' => 0.0
        ]);

        $character = $this->character->inventorySetManagement()->createInventorySets(10)->putItemInSet($item, 0, 'left-hand', true);
        $user      = $character->getCharacter()->user;

        $response = $this->actingAs($user)->post(route(
            'game.unequip.all', ['character' => $this->character->getCharacter()->id]
        ), [
            'is_set_equipped' => true,
        ])->response;

        $response->assertSessionHas('success', 'All items have been removed.');

        $character = $character->getCharacter();

        $equipped = $character->inventory->slots->filter(function($slot) {
            return $slot->equipped;
        })->all();

        $this->assertTrue(empty($equipped));

        $character = $this->character->getCharacter();

        $character->inventory->slots->each(function($slot) {
            $this->assertFalse($slot->equipped);
        });

        $character->inventorySets->filter(function($set) {
            $this->assertTrue($set->slots()->where('equipped', true)->get()->isEmpty());
            $this->assertFalse($set->is_equipped);
        });
    }

    public function testCannotUnEquipItem() {
        $user = $this->character->getUser();

        $response = $this->actingAs($user)->post(route('game.inventory.unequip', ['character' => $this->character->getCharacter()->id]) , [
            'item_to_remove' => rand(900,9560),
            'inventory_set_equipped' => false,
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
            'slot_id'    => InventorySlot::first()->id,
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
            'slot_id'    => InventorySlot::first()->id,
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

    public function testCompareItems() {
        $item = $this->createItem([
            'name'                => 'Sample Item',
            'type'                => 'weapon',
            'base_damage'         => 200,
            'cost'                => 100,
            'crafting_type'       => 'weapon',
            'description'         => 'sample',
            'can_resurrect'       => false,
            'resurrection_chance' => 0.0
        ]);

        $slotId = $this->character->inventoryManagement()->giveItem($item)->getSlotId(0);
        $user   = $this->character->getUser();

        $this->actingAs($user)
            ->visitRoute('game.character.sheet')
            ->visitRoute('game.inventory.compare', [
                'item_to_equip_type' => 'weapon',
                'slot_id'            => $slotId,
                'character'          => $this->character->getCharacter()->id
            ])->see('Equipped');
    }

    public function testSeeCompareItemsWithNoCache() {
        $user = $this->character->inventoryManagement()
            ->giveitem($this->createItem([
                'name' => 'Spear',
                'base_damage' => 6,
                'type' => 'weapon',
                'crafting_type' => 'weapon',
            ]))
            ->getCharacterFactory()
            ->getUser();

        $this->actingAs($user)->visitRoute('game.character.sheet')->visitRoute('game.inventory.compare-items', [
            'user' => $user
        ])->see('Item comparison expired.');
    }

    public function testCannotSeeComparePage() {
        $item = $this->createItem([
            'name'             => 'Armour',
            'base_damage'      => 6,
            'base_ac'          => 6,
            'type'             => 'gloves',
            'default_position' => 'hands',
            'crafting_type'    => 'armour',
        ]);

        $user = $this->character->inventoryManagement()
            ->giveitem($item)
            ->getCharacterFactory()
            ->getUser();

        $this->actingAs($user)->visitRoute('game.character.sheet')->visitRoute('game.inventory.compare', [
            'item_to_equip_type' => 'apple-sauce',
            'slot_id'            => InventorySlot::where('item_id', $item->id)->first()->id,
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
            'slot_id'            => InventorySlot::first()->id,
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

    public function testCanDestroyItem() {

        $user = $this->character->inventoryManagement()->unequipAll()->getCharacterFactory()->getUser();


        $response = $this->actingAs($user)->post(route('game.destroy.item', ['character' => $this->character->getCharacter()->id]), [
            'slot_id' => InventorySlot::first()->id
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
            'slot_id' => InventorySlot::first()->id,
        ])->response;

        $response->assertSessionHas('error', 'Cannot destroy equipped item.');
    }

    public function testCanMoveItemToSet() {
        $item = $this->createItem([
            'name'                => 'Sample Item',
            'type'                => 'weapon',
            'base_damage'         => 200,
            'cost'                => 100,
            'crafting_type'       => 'weapon',
            'description'         => 'sample',
            'can_resurrect'       => false,
            'resurrection_chance' => 0.0
        ]);

        $slotId = $this->character->inventoryManagement()->giveItem($item)->getSlotId(0);
        $character = $this->character->inventorySetManagement()->createInventorySets(10)->getCharacter();

        $response = $this->actingAs($character->user)->post(route('game.inventory.move.to.set', ['character' => $character->id]), [
            'slot_id' => $slotId,
            'move_to_set' => $character->inventorySets()->first()->id,
        ])->response;

        $response->assertSessionHas('success', $item->name . ' Has been moved to: Set 1');

        $character = $character->refresh();

        $this->assertTrue($character->inventory->slots()->where('item_id', $item->id)->get()->isEmpty());
        $this->assertFalse($character->inventorySets()->join('set_slots', function($join) {
            $join->on('set_slots.inventory_set_id', 'inventory_sets.id');
        })->where('set_slots.item_id', $item->id)->get()->isEmpty());
    }

    public function testCannotMoveItemToSet() {
        $character = $this->character->inventorySetManagement()->createInventorySets(10)->getCharacter();

        $response = $this->actingAs($character->user)->post(route('game.inventory.move.to.set', ['character' => $character->id]), [
            'slot_id' => 879,
            'move_to_set' => $character->inventorySets()->first()->id,
        ])->response;

        $response->assertSessionHas('error', 'Either the slot or the inventory set does not exist.');

        $character = $character->refresh();

        $this->assertTrue($character->inventorySets()->join('set_slots', function($join) {
            $join->on('set_slots.inventory_set_id', 'inventory_sets.id');
        })->whereNotNull('set_slots.item_id')->get()->isEmpty());
    }

    public function testCanEquipSet() {
        $item = $this->createItem([
            'name'                => 'Sample Item',
            'type'                => 'weapon',
            'base_damage'         => 200,
            'cost'                => 100,
            'crafting_type'       => 'weapon',
            'description'         => 'sample',
            'can_resurrect'       => false,
            'resurrection_chance' => 0.0
        ]);

        $character = $this->character->inventorySetManagement()->createInventorySets(10)->putItemInSet($item, 0)->getCharacter();

        $response = $this->actingAs($character->user)->post(route('game.equip.set', [
            'character' => $character->id,
            'inventorySet' => $character->inventorySets()->first()->id,
        ]))->response;

        $response->assertSessionHas('success', 'Set 1 is now equipped');

        $character = $character->refresh();

        $this->assertTrue($character->inventorySets()->where('is_equipped', true)->get()->isNotEmpty());
    }

    public function testCannotEquipSet() {
        $item = $this->createItem([
            'name'                => 'Sample Item',
            'type'                => 'weapon',
            'base_damage'         => 200,
            'cost'                => 100,
            'crafting_type'       => 'weapon',
            'description'         => 'sample',
            'can_resurrect'       => false,
            'resurrection_chance' => 0.0
        ]);

        $character = $this->character->inventorySetManagement()->createInventorySets(10)->putItemInSet($item, 0)->updateSet(0, [
            'can_be_equipped' => false,
        ])->getCharacter();

        $response = $this->actingAs($character->user)->post(route('game.equip.set', [
            'character' => $character->id,
            'inventorySet' => $character->inventorySets()->first()->id,
        ]))->response;

        $response->assertSessionHas('error', 'Set cannot be equipped.');

        $character = $character->refresh();

        $this->assertFalse($character->inventorySets()->where('is_equipped', true)->get()->isNotEmpty());
    }

    public function testCanSaveEquipmentAsSet() {
        $this->character->inventorySetManagement()->createInventorySets(10);

        $character = $this->character->getCharacter();

        $response = $this->actingAs($character->user)->post(route('game.inventory.save.as.set', [
            'character' => $character->id,
        ]), [
            'move_to_set' => $this->character->inventorySetManagement()->getInventorySetId(0)
        ])->response;

        $response->assertSessionHas('success', 'Set 1 is now equipped (equipment has been moved to the set)');

        $character = $character->refresh();

        $this->assertTrue($character->inventory->slots->filter(function($slot) {
            return $slot->equipped;
        })->isEmpty());

        $this->assertCount(1, $character->inventorySets()->where('is_equipped', true)->get());
    }

    public function testCanRemoveFromSet() {
        $item = $this->createItem([
            'name'                => 'Sample Item',
            'type'                => 'weapon',
            'base_damage'         => 200,
            'cost'                => 100,
            'crafting_type'       => 'weapon',
            'description'         => 'sample',
            'can_resurrect'       => false,
            'resurrection_chance' => 0.0
        ]);

        $character = $this->character->inventorySetManagement()->createInventorySets(10)->putItemInSet($item, 0)->getCharacter();

        $response = $this->actingAs($character->user)->post(route('game.remove.from.set', [
            'character' => $character->id,
        ]), [
            'inventory_set_id' => $this->character->inventorySetManagement()->getInventorySetId(0),
            'slot_id' => SetSlot::whereNotNull('item_id')->first()->id,
        ])->response;

        $response->assertSessionHas('success', $item->name . ' Has been removed from Set 1 and placed back into your inventory.');

        $character = $character->refresh();

        $this->assertTrue($character->inventorySets()->join('set_slots', function($join) {
            $join->on('set_slots.inventory_set_id', 'inventory_sets.id');
        })->whereNotNull('set_slots.item_id')->get()->isEmpty());

        $this->assertTrue($character->inventory->slots->filter(function($slot) use($item) {
            return $slot->item_id === $item->id;
        })->isNotEmpty());
    }

    public function testCannotRemoveFromSetSlotDoesNotExist() {
        $item = $this->createItem([
            'name'                => 'Sample Item',
            'type'                => 'weapon',
            'base_damage'         => 200,
            'cost'                => 100,
            'crafting_type'       => 'weapon',
            'description'         => 'sample',
            'can_resurrect'       => false,
            'resurrection_chance' => 0.0
        ]);

        $character = $this->character->inventorySetManagement()->createInventorySets(10)->putItemInSet($item, 0)->getCharacter();

        $response = $this->actingAs($character->user)->post(route('game.remove.from.set', [
            'character' => $character->id,
        ]), [
            'inventory_set_id' => $this->character->inventorySetManagement()->getInventorySetId(0),
            'slot_id' => 9879
        ])->response;

        $response->assertSessionHas('error', 'Either the slot or the inventory set does not exist.');
    }

    public function testCannotRemoveFromSetIsEquipped() {
        $item = $this->createItem([
            'name'                => 'Sample Item',
            'type'                => 'weapon',
            'base_damage'         => 200,
            'cost'                => 100,
            'crafting_type'       => 'weapon',
            'description'         => 'sample',
            'can_resurrect'       => false,
            'resurrection_chance' => 0.0
        ]);

        $character = $this->character->inventorySetManagement()->createInventorySets(10)->putItemInSet($item, 0, 'left-hand', true)->getCharacter();

        $response = $this->actingAs($character->user)->post(route('game.remove.from.set', [
            'character' => $character->id,
        ]), [
            'inventory_set_id' => $this->character->inventorySetManagement()->getInventorySetId(0),
            'slot_id' => SetSlot::whereNotNull('item_id')->first()->id,
        ])->response;

        $response->assertSessionHas('error', 'You cannot move an equipped item into your inventory from this set. Unequip it first.');
    }

    public function testCannotRemoveFromNoInventorySpace() {
        $item = $this->createItem([
            'name'                => 'Sample Item',
            'type'                => 'weapon',
            'base_damage'         => 200,
            'cost'                => 100,
            'crafting_type'       => 'weapon',
            'description'         => 'sample',
            'can_resurrect'       => false,
            'resurrection_chance' => 0.0
        ]);

        $character = $this->character->updateCharacter([
            'inventory_max' => 0
        ])->inventorySetManagement()->createInventorySets(10)->putItemInSet($item, 0, 'left-hand', true)->getCharacter();

        $response = $this->actingAs($character->user)->post(route('game.remove.from.set', [
            'character' => $character->id,
        ]), [
            'inventory_set_id' => $this->character->inventorySetManagement()->getInventorySetId(0),
            'slot_id' => SetSlot::whereNotNull('item_id')->first()->id,
        ])->response;

        $response->assertSessionHas('error', 'You cannot move an equipped item into your inventory from this set. Unequip it first.');
    }

    public function testCanEmptySet() {
        $item = $this->createItem([
            'name'                => 'Sample Item',
            'type'                => 'weapon',
            'base_damage'         => 200,
            'cost'                => 100,
            'crafting_type'       => 'weapon',
            'description'         => 'sample',
            'can_resurrect'       => false,
            'resurrection_chance' => 0.0
        ]);

        $character = $this->character->inventorySetManagement()->createInventorySets(10)->putItemInSet($item, 0)->getCharacter();

        $response = $this->actingAs($character->user)->post(route('game.inventory.empty.set', [
            'character' => $character->id,
            'inventorySet' => $this->character->inventorySetManagement()->getInventorySetId(0),
        ]))->response;

        $response->assertSessionHas('success', 'Removed 1 of 1 items from Set 1');

        $character = $character->refresh();

        $this->assertTrue($character->inventorySets()->join('set_slots', function($join) {
            $join->on('set_slots.inventory_set_id', 'inventory_sets.id');
        })->whereNotNull('set_slots.item_id')->get()->isEmpty());

        $this->assertTrue($character->inventory->slots->filter(function($slot) use($item) {
            return $slot->item_id === $item->id;
        })->isNotEmpty());
    }

}
