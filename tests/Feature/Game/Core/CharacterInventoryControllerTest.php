<?php

namespace Tests\Feature\Game\Core;

use App\Flare\Models\ItemAffix;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateItem;
use Tests\Setup\CharacterSetup;

class CharacterInventoryControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateItem,
        CreateUser;

    private $character;

    public function setUp(): void {
        parent::setUp();

        $item = $this->createItem([
            'name'        => 'Rusty Dagger',
            'type'        => 'weapon',
            'base_damage' => '6',
        ]);

        $this->character = (new CharacterSetup())
                                ->setupCharacter($this->createUser())
                                ->giveItem($item)
                                ->equipLeftHand()
                                ->getCharacter();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testCanSeeCharacterInventory() {
        $this->actingAs($this->character->user)
                    ->visitRoute('game.character.inventory')
                    ->see('Equiped Items'); 
    }

    public function testCanUnEquipItem() {
        $this->actingAs($this->character->user)->post(route('game.inventory.unequip'), [
            'item_to_remove' => 1
        ]);

        $this->character->refresh();

        $this->character->inventory->slots->each(function($slot) {
            $this->assertFalse($slot->equipped);
        });
    }

    public function testCannotUnEquipItem() {
        $response = $this->actingAs($this->character->user)->post(route('game.inventory.unequip'), [
            'item_to_remove' => 2
        ])->response;

        $this->character->refresh();

        $response->assertSessionHas('error', 'No item found to be equipped.');

        $this->character->inventory->slots->each(function($slot) {
            $this->assertTrue($slot->equipped);
        });
    }

    public function testCanEquipItem() {
        $this->character->inventory->slots->each(function($slot){
            $slot->update([
                'position' => null,
                'equipped' => false,
            ]);
        });

        $this->character->refresh();

        $response = $this->actingAs($this->character->user)->post(route('game.equip.item'), [
            'position'   => 'left-hand',
            'slot_id'    => '1',
            'equip_type' => 'weapon',
        ])->response;

        $this->character->refresh();

        $this->character->inventory->slots->each(function($slot) {
            $this->assertTrue($slot->equipped);
        });
    }

    public function testCannotEquipItemWhenCharacterDead() {
        $this->character->update([
            'is_dead' => true,
        ]);

        $this->character->inventory->slots->each(function($slot){
            $slot->update([
                'position' => null,
                'equipped' => false,
            ]);
        });

        $this->character->refresh();

        $response = $this->actingAs($this->character->user)->visitRoute('game')->post(route('game.equip.item'), [
            'position'   => 'left-hand',
            'slot_id'    => '1',
            'equip_type' => 'weapon',
        ])->response;

        $response->assertSessionHas('error', "You are dead and must revive before trying to do that. Dead people can't do things.");
    }

    public function testCannotEquipItemYouDontHave() {
        $this->character->inventory->slots->each(function($slot){
            $slot->update([
                'position' => null,
                'equipped' => false,
            ]);
        });

        $this->character->refresh();

        $response = $this->actingAs($this->character->user)->post(route('game.equip.item'), [
            'position'   => 'left-hand',
            'slot_id'    => '7',
            'equip_type' => 'weapon',
        ])->response;

        $response->assertSessionHas('error', 'Could not equip item because you either do not have it, or it is equipped already.');

        $this->character->refresh();

        $this->character->inventory->slots->each(function($slot) {
            $this->assertFalse($slot->equipped);
        });
    }

    public function testPutDifferentItemIntoSameSlot() {
        $item = $this->createItem([
            'name' => 'Spear',
            'base_damage' => 6,
            'type' => 'weapon',
        ]);

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id'      => $item->id,
            'equiped'      => false,
        ]);

        $this->character->refresh();

        $response = $this->actingAs($this->character->user)->post(route('game.equip.item'), [
            'position'   => 'left-hand',
            'slot_id'    => '2',
            'equip_type' => 'weapon',
        ])->response;

        $this->character->refresh();

        $slot = $this->character->inventory->slots->where('item_id', $item->id)->where('equipped', true)->first();

        $this->assertNotNull($slot);
        $this->assertEquals($slot->item->name, 'Spear');
        $this->assertEquals($slot->position, 'left-hand');
        $this->assertTrue($slot->equipped);
    }

    public function testCanDestroyItem() {
        $this->character->inventory->slots->each(function($slot){
            $slot->update([
                'position' => null,
                'equipped' => false,
            ]);
        });

        $this->character->refresh();

        $response = $this->actingAs($this->character->user)->post(route('game.destroy.item'), [
            'slot_id' => '1'
        ])->response;

        $response->assertSessionHas('success', 'Destroyed Rusty Dagger.');

        $this->assertTrue($this->character->inventory->slots->isEmpty());
    }

    public function testCannotDestroyItemYouDontHave() {
        $response = $this->actingAs($this->character->user)->post(route('game.destroy.item'), [
            'slot_id' => '2'
        ])->response;

        $response->assertSessionHas('error', 'You don\'t own that item.');
    }

    public function testCannotDestroyItemYouHaveEquipped() {
        $response = $this->actingAs($this->character->user)->post(route('game.destroy.item'), [
            'slot_id' => '1'
        ])->response;

        $response->assertSessionHas('error', 'Cannot destory equipped item.');
    }

    public function testSeeComparePage() {
        $item = $this->createItem([
            'name' => 'Spear',
            'base_damage' => 6,
            'type' => 'weapon',
        ]);

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id'      => $item->id,
            'equiped'      => false,
        ]);

        $this->actingAs($this->character->user)->visitRoute('game.inventory.compare', [
            'item_to_equip_type' => 'weapon',
            'slot_id'            => '2',
        ])->see('Equipped')->see('Equipped: left-hand');
    }

    public function testSeeComparePageForSpell() {
        $item = $this->createItem([
            'name' => 'spell',
            'base_damage' => 6,
            'type' => 'spell-damage',
        ]);

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id'      => $item->id,
            'equiped'      => false,
        ]);

        $this->actingAs($this->character->user)->visitRoute('game.inventory.compare', [
            'item_to_equip_type' => 'spell-damage',
            'slot_id'            => '2',
        ])->see('Equipped')->see('Item Details');
    }

    public function testSeeComparePageForArmour() {
        $item = $this->createItem([
            'name' => 'Armour',
            'base_damage'      => 6,
            'base_ac'          => 6,
            'type'             => 'gloves',
            'default_position' => 'hands',
        ]);

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id'      => $item->id,
            'equiped'      => false,
        ]);

        $this->actingAs($this->character->user)->visitRoute('game.inventory.compare', [
            'item_to_equip_type' => 'gloves',
            'slot_id'            => '2',
        ])->see('Equipped')->see('Item Details');
    }

    public function testSeeComparePageForItemWithPrefixAndSuffix() {
        $item = $this->createItem([
            'name' => 'Armour',
            'base_damage'      => 6,
            'base_ac'          => 6,
            'type'             => 'gloves',
            'default_position' => 'hands',
            'item_suffix_id'   => ItemAffix::create([
                'name' => 'Sample',
                'base_healing_mod' => 0.10,
                'str_mod' => 0.10,
                'type' => 'suffix',
            ])->id,
            'item_prefix_id'   => ItemAffix::create([
                'name' => 'Sample',
                'base_healing_mod' => 0.10,
                'dex_mod' => 0.10,
                'type' => 'prefix',
            ])->id,
        ]);

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id'      => $item->id,
            'equiped'      => false,
        ]);

        $this->actingAs($this->character->user)->visitRoute('game.inventory.compare', [
            'item_to_equip_type' => 'gloves',
            'slot_id'            => '2',
        ])->see('Equipped')->see('Item Details');
    }

    public function testCannotSeeComparePage() {
        $item = $this->createItem([
            'name' => 'Spear',
            'base_damage' => 6,
            'type' => 'weapon',
        ]);

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id'      => $item->id,
            'equiped'      => false,
        ]);

        $this->actingAs($this->character->user)->visitRoute('game.character.inventory')->visitRoute('game.inventory.compare', [
            'item_to_equip_type' => 'apple-sauce',
            'slot_id'            => '2',
        ])->see('Error. Invalid Input.');
    }

    public function testSeeComparePageWithNothingEquipped() {
        $this->character->inventory->slots->each(function($slot){
            $slot->update([
                'position' => null,
                'equipped' => false,
            ]);
        });

        $this->character->refresh();

        $this->actingAs($this->character->user)->visitRoute('game.inventory.compare', [
            'item_to_equip_type' => 'weapon',
            'slot_id'            => '1',
        ])->see('Equipped')->see('You have nothing equipped for this item type. Anything is better then nothing.');
    }

    public function testCannotSeeComparePageWithItemNotInYourInventory() {
        $this->actingAs($this->character->user)->visitRoute('game.character.inventory')->visitRoute('game.inventory.compare', [
            'item_to_equip_type' => 'weapon',
            'slot_id'            => '10',
        ])->see('Item not found in your inventory.');
    }
}
