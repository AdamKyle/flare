<?php

namespace Tests\Feature\Game\Core;

use Illuminate\Foundation\Testing\RefreshDatabase;
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
                         ])
                         ->response;

       $content = json_decode($response->content());


       $this->assertEquals("Equipped: Rusty Dagger to: Right Hand", $content->message);
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
                         ])
                         ->response;

       $content = json_decode($response->content());

       $this->assertEquals("Cannot equip Rusty Dagger to the same hand.", $content->message);
    }

    public function testMoveItemFromOneHandToTheOther() {
        $response = $this->actingAs($this->character->user, 'api')
                         ->json('POST', '/api/equip-item/' . $this->character->id, [
                             'item_id' => 1,
                             'type'    => 'right-hand',
                         ])
                         ->response;

       $content = json_decode($response->content());

       $this->assertEquals("Switched: Rusty Dagger to: Right Hand.", $content->message);
       $this->assertNull($this->character->equippedItems->where('type', '=', 'left-hand')->first());
       $this->assertNotNull($this->character->equippedItems->where('type', '=', 'right-hand')->first());
    }
}
