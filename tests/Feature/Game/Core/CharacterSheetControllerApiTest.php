<?php

namespace Tests\Feature\Game\Core;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateItem;
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

    public function testGetCharacterInfo() {
        $response = $this->actingAs($this->character->user, 'api')
                         ->json('GET', '/api/character-sheet/' . $this->character->id)
                         ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertFalse(empty($content->sheet->data->skills));
        $this->assertEquals($this->character->name, $content->sheet->data->name);
    }
}
