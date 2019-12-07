<?php

namespace Tests\Feature\Game\Core;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateItem;
use App\Flare\Builders\CharacterBuilder;
use App\Flare\Models\Item;

class CharacterInventoryControllerApiTest extends TestCase {

    use RefreshDatabase,
        CreateUser,
        CreateRace,
        CreateItem,
        CreateClass;

    private $character;

    public function setUp(): void {
        parent::setUp();

        $user  = $this->createUser();
        $race  = $this->createRace([
            'name' => 'Dwarf'
        ]);
        $class = $this->createClass([
            'name'        => 'Fighter',
            'damage_stat' => 'str',
        ]);

        $this->createItem([
            'name' => 'Rusty Dagger',
            'type' => 'weapon',
            'base_damage' => 3,
        ]);

        $this->character = resolve(CharacterBuilder::class)
                                ->setRace($race)
                                ->setClass($class)
                                ->createCharacter($user, 'Sample')
                                ->assignSkills()
                                ->character();

       Event::fake();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testGetCharacterInventory() {
        $response = $this->actingAs($this->character->user, 'api')
                         ->json('GET', '/api/character-inventory/' . $this->character->id)
                         ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertFalse(empty($content->inventory->data->items));
        $this->assertEquals(Item::first()->name, $content->inventory->data->items[0]->name);
    }

    public function testCanEquipItem() {
        $item = $this->createItem([
            'name' => 'Rusty Dagger',
            'type' => 'weapon',
            'base_damage' => 3,
        ]);

        $this->character->inventory->slots()->create([
            'iventory_id' => $this->character->inventory->id,
            'item_id'     => $item->id,
        ]);

        $response = $this->actingAs($this->character->user, 'api')
                         ->json('POST', '/api/equip-item/' . $this->character->id, [
                             'item_id' => $item->id,
                             'type'    => 'right-hand',
                             'equip_type' => 'weapon',
                         ])
                         ->response;

       $content = json_decode($response->content());

       $this->assertEquals("Equipped: Rusty Dagger to: Right Hand", $content->message);
    }

    public function testCanEquipNewItemIntoSameSlot() {
        $item = $this->createItem([
            'name' => 'Rusty Dagger',
            'type' => 'weapon',
            'base_damage' => 3,
        ]);

        $newItem = $this->createItem([
            'name' => 'Rusty Dagger 2',
            'type' => 'weapon',
            'base_damage' => 3,
        ]);

        $this->character->inventory->slots()->insert([
            [
                'inventory_id' => $this->character->inventory->id,
                'item_id'     => $item->id,
            ],
            [
                'inventory_id' => $this->character->inventory->id,
                'item_id'     => $newItem->id,
            ],
        ]);

        $this->character->equippedItems()->create([
            'iventory_id' => $this->character->inventory->id,
            'item_id'     => $item->id,
            'type'        => 'left-hand'
        ]);

        $response = $this->actingAs($this->character->user, 'api')
                         ->json('POST', '/api/equip-item/' . $this->character->id, [
                             'item_id' => $newItem->id,
                             'type'    => 'left-hand',
                             'equip_type' => 'weapon',
                         ])
                         ->response;

       $content = json_decode($response->content());

       $this->assertEquals("Equipped: Rusty Dagger 2 to: Left Hand", $content->message);
    }

    public function testCanEquipNewItemIntoSameSlotWithSuffix() {
        $item = $this->createItem([
            'name' => 'Rusty Dagger',
            'type' => 'weapon',
            'base_damage' => 3,
        ]);

        $newItem = $this->createItem([
            'name' => 'Rusty Dagger 2 *Krawls Claw*',
            'type' => 'weapon',
            'base_damage' => 3,
        ]);

        $newItem->itemAffixes()->create(config('game.item_affixes')[0]);

        $this->character->inventory->slots()->insert([
            [
                'inventory_id' => $this->character->inventory->id,
                'item_id'     => $item->id,
            ],
            [
                'inventory_id' => $this->character->inventory->id,
                'item_id'     => $newItem->id,
            ],
        ]);

        $this->character->equippedItems()->create([
            'iventory_id' => $this->character->inventory->id,
            'item_id'     => $item->id,
            'type'        => 'left-hand'
        ]);

        $response = $this->actingAs($this->character->user, 'api')
                         ->json('POST', '/api/equip-item/' . $this->character->id, [
                             'item_id' => $newItem->id,
                             'type'    => 'left-hand',
                             'equip_type' => 'weapon',
                         ])
                         ->response;

       $content = json_decode($response->content());

       $this->assertEquals("Equipped: Rusty Dagger 2 *Krawls Claw* to: Left Hand", $content->message);
    }

    public function testCannotEquipSameItemMissingType() {
        $response = $this->actingAs($this->character->user, 'api')
                         ->json('POST', '/api/equip-item/' . $this->character->id, [
                             'item_id' => 1,
                         ])
                         ->response;

       $content = json_decode($response->content());

       $this->assertEquals("The type field is required.", $content->errors->type[0]);
    }

    public function testCannotEquipItemThatDoesntExistInInventory() {
        $item = $this->createItem([
            'name' => 'Rusty Dagger',
            'type' => 'weapon',
            'base_damage' => 3,
        ]);

        $response = $this->actingAs($this->character->user, 'api')
                         ->json('POST', '/api/equip-item/' . $this->character->id, [
                             'item_id' => $item->id,
                             'type'    => 'right-hand',
                             'equip_type' => 'weapon',
                         ])
                         ->response;

       $content = json_decode($response->content());

       $this->assertEquals("Cannot equip Rusty Dagger. You do not currently have this in yor inventory.", $content->message);
    }

    public function testCannotEquipSameItemToSameHand() {
        $response = $this->actingAs($this->character->user, 'api')
                         ->json('POST', '/api/equip-item/' . $this->character->id, [
                             'item_id' => 1,
                             'type'    => 'left-hand',
                             'equip_type' => 'weapon',
                         ])
                         ->response;

       $content = json_decode($response->content());

       $this->assertEquals("Cannot equip Rusty Dagger to the same hand.", $content->message);
    }

    public function testMoveItemFromOneHandToTheOther() {
        $response = $this->actingAs($this->character->user, 'api')
                         ->json('POST', '/api/equip-item/' . $this->character->id, [
                             'item_id'    => 1,
                             'type'       => 'right-hand',
                             'equip_type' => 'weapon',
                         ])
                         ->response;

       $content = json_decode($response->content());

       $this->assertEquals("Switched: Rusty Dagger to: Right Hand.", $content->message);
       $this->assertNull($this->character->equippedItems->where('type', '=', 'left-hand')->first());
       $this->assertNotNull($this->character->equippedItems->where('type', '=', 'right-hand')->first());
    }

    public function testCannotEquipItemWhenTypeDoesntMatch() {
        $item = $this->createItem([
            'name' => 'Something else',
            'type' => 'armor',
        ]);

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id'      => $item->id,
        ]);

        $response = $this->actingAs($this->character->user, 'api')
                         ->json('POST', '/api/equip-item/' . $this->character->id, [
                             'item_id'    => $item->id,
                             'type'       => 'right-hand',
                             'equip_type' => 'weapon',
                         ])
                         ->response;

       $content = json_decode($response->content());

       $this->assertEquals("Cannot equip Something else as it is not of type: weapon", $content->message);
       $this->assertNotNull($this->character->equippedItems->where('type', '=', 'left-hand')->first());
       $this->assertNull($this->character->equippedItems->where('type', '=', 'right-hand')->first());
    }

    public function testCanUnEquipItem() {
        $itemName = $this->character->equippedItems->first()->item->name;

        $response = $this->actingAs($this->character->user, 'api')
                         ->json('DELETE', '/api/unequip-item/' . $this->character->id, [
                             'equipment_id' => 1,
                         ])
                         ->response;

       $content = json_decode($response->content());

       $this->assertEquals('Unequipped ' . $itemName, $content->message);

       $character = $this->character->refresh();

       $this->assertTrue($character->equippedItems->isEmpty());
    }

    public function testCannotUnEquipItem() {
        $itemName = $this->character->equippedItems->first()->item->name;

        $response = $this->actingAs($this->character->user, 'api')
                         ->json('DELETE', '/api/unequip-item/' . $this->character->id, [
                             'equipment_id' => 4,
                         ])
                         ->response;

       $content = json_decode($response->content());

       $this->assertEquals('Could not find a matching equipped item.', $content->message);

       $character = $this->character->refresh();

       $this->assertFalse($character->equippedItems->isEmpty());
    }

    public function testCanDestroyItem() {
        $itemName = $this->character->inventory->slots->first()->item->name;

        $response = $this->actingAs($this->character->user, 'api')
                         ->json('DELETE', '/api/destroy-item/' . $this->character->id, [
                             'item_id' => 1,
                         ])
                         ->response;

       $content = json_decode($response->content());

       $this->assertEquals('Destroyed ' . $itemName, $content->message);

       $character = $this->character->refresh();

       $this->assertTrue($character->inventory->slots->isEmpty());
    }

    public function testCannotDestroyItem() {
        $itemName = $this->character->equippedItems->first()->item->name;

        $response = $this->actingAs($this->character->user, 'api')
                         ->json('DELETE', '/api/destroy-item/' . $this->character->id, [
                             'item_id' => 4,
                         ])
                         ->response;

       $content = json_decode($response->content());

       $this->assertEquals('Could not find a matching item.', $content->message);

       $character = $this->character->refresh();

       $this->assertFalse($character->inventory->slots->isEmpty());
    }
}
