<?php

namespace Tests\Feature\Game\Core;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateItem;
use Tests\Setup\CharacterSetup;
use App\Flare\Builders\CharacterBuilder;

class CharacterSheetControllerApiTest extends TestCase {

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

        $item = $this->createItem([
            'name' => 'Rusty Dagger',
            'type' => 'weapon',
            'base_damage' => 3,
        ]);

        $this->character = (new CharacterSetup)->setupCharacter([], $user)
                                               ->equipRightHand($item)
                                               ->setSkill('Looting', [])
                                               ->getCharacter();

        $this->character->inventory->slots()->insert([
           [
               'inventory_id' => $this->character->inventory->id,
               'item_id'      => $item->id
           ],
        ]);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testGetCharacterInfo() {
        $response = $this->actingAs($this->character->user, 'api')
                         ->json('GET', '/api/character-sheet/' . $this->character->id)
                         ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertFalse(empty($content->sheet->data->skills));
        $this->assertEquals($this->character->name, $content->sheet->data->name);
    }

    public function testGetCharacterInfoWithBothLeftAndRightWeapon() {
        $this->character->equippedItems()->insert([
            [
                'character_id' => $this->character->id,
                'item_id'      => $this->character->inventory->slots()->first()->item_id,
                'position'     => 'right-hand',
            ]
        ]);

        $response = $this->actingAs($this->character->user, 'api')
                         ->json('GET', '/api/character-sheet/' . $this->character->id)
                         ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertFalse(empty($content->sheet->data->skills));
        $this->assertEquals($this->character->name, $content->sheet->data->name);
    }

    public function testGetCharacterInfoWithRightHand() {
        $this->character->equippedItems()->first()->update([
            'position' => 'right-hand'
        ]);

        $response = $this->actingAs($this->character->user, 'api')
                         ->json('GET', '/api/character-sheet/' . $this->character->id)
                         ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertFalse(empty($content->sheet->data->skills));
        $this->assertEquals($this->character->name, $content->sheet->data->name);
    }

    public function testGetCharacterInfoWithNoWeapon() {
        $this->character->equippedItems()->first()->delete();

        $response = $this->actingAs($this->character->user, 'api')
                         ->json('GET', '/api/character-sheet/' . $this->character->id)
                         ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertFalse(empty($content->sheet->data->skills));
    }
}
