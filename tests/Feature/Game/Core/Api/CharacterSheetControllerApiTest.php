<?php

namespace Tests\Feature\Game\Core\Api;

use App\Flare\Models\ItemAffix;
use Database\Seeders\GameSkillsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateItem;
use Tests\Setup\CharacterSetup;

class CharacterSheetControllerApiTest extends TestCase {

    use RefreshDatabase,
        CreateUser,
        CreateItem;

    private $character;

    public function setUp(): void {
        parent::setUp();

        $this->seed(GameSkillsSeeder::class);

        $user  = $this->createUser();

        $item = $this->createItem([
            'name' => 'Rusty Dagger',
            'type' => 'weapon',
            'base_damage' => 3,
        ]);

        $this->character = (new CharacterSetup)->setupCharacter($user)
                                               ->giveItem($item)
                                               ->equipRightHand()
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
        $this->assertFalse(empty($content->sheet->skills));
        $this->assertEquals($this->character->name, $content->sheet->name);
    }

    public function testGetCharacterInfoWithBothLeftAndRightWeapon() {
        $response = $this->actingAs($this->character->user, 'api')
                         ->json('GET', '/api/character-sheet/' . $this->character->id)
                         ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertFalse(empty($content->sheet->skills));
        $this->assertEquals($this->character->name, $content->sheet->name);
    }

    public function testGetCharacterInfoWithRightHand() {

        $response = $this->actingAs($this->character->user, 'api')
                         ->json('GET', '/api/character-sheet/' . $this->character->id)
                         ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertFalse(empty($content->sheet->skills));
        $this->assertEquals($this->character->name, $content->sheet->name);
    }

    public function testGetCharacterInfoWithNoWeapon() {
        $this->character->inventory->slots->each(function($slot){
            $slot->update([
                'position' => null,
                'equipped' => false,
            ]);
        });

        $response = $this->actingAs($this->character->user, 'api')
                         ->json('GET', '/api/character-sheet/' . $this->character->id)
                         ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertFalse(empty($content->sheet->skills));
    }

    public function testGetCharacterInfoWithModdedStat() {

        $item = $this->createItem([
            'name' => 'sword',
            'type' => 'weapon',
            'str_mod' => 0.1,
            'base_damage' => 6,
            'item_prefix_id' => ItemAffix::create([
                'name'                 => 'Sample 2',
                'base_damage_mod'      => '0.10',
                'type'                 => 'prefix',
                'description'          => 'Sample',
                'base_healing_mod'     => '0.10',
                'str_mod'              => '0.10',
                'cost'                 => 100,
            ])->id,
        ]);

        $this->character->inventory->slots()->create([
            'inventory_id' => $this->character->inventory->id,
            'item_id'      => $item->id,
            'equipped'     => true,
            'position'     => 'left-hand',
        ]);

        $this->character->refresh();

        $response = $this->actingAs($this->character->user, 'api')
                         ->json('GET', '/api/character-sheet/' . $this->character->id)
                         ->response;

        $content = json_decode($response->content());

        $this->assertFalse($content->sheet->str_modded === $this->character->str);
    }

    public function testForceNameChange() {
        $response = $this->actingAs($this->character->user, 'api')
                         ->json('POST', '/api/character-sheet/'.$this->character->id.'/name-change', [
                             'name' => 'Apples'
                         ])
                         ->response;

        json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertEquals('Apples', $this->character->refresh()->name);
    }
}
