<?php

namespace Tests\Feature\Game\Core;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateItem;
use App\Flare\Builders\CharacterBuilder;

class ShopControllerAP extends TestCase {

    use RefreshDatabase,
        CreateUser,
        CreateRace,
        CreateItem,
        CreateClass;

    private $character;

    public function setUp(): void {
        parent::setUp();

        $this->createItemsForShop();
        $this->createCharacter();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testShouldGetASetOfItems() {
        $response = $this->actingAs($this->character->user, 'api')
                         ->json('GET', '/api/shop')
                         ->response;

        $content = json_decode($response->content());

        $this->assertTrue(!empty($content->weapons));
        $this->assertTrue(!empty($content->armour));
        $this->assertTrue(!empty($content->rings));
        $this->assertTrue(!empty($content->spells));
        $this->assertTrue(!empty($content->artifacts));
        $this->assertEquals(1, $content->artifacts[0]->artifact_property->id);
    }

    protected function createCharacter() {
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

    protected function createItemsForShop() {
        // Creates a weapon
        $this->createItem([
            'name'        => 'Rusty bloody broken dagger',
            'type'        => 'weapon',
            'base_damage' => 3,
            'cost'        => 100,
        ]);

        // Creates armour
        $this->createItem([
            'name'        => 'Chapped, scared and ripped leather breast plate',
            'type'        => 'body',
            'base_damage' => null,
            'cost'        => 100,
        ]);

        // creates artifact with property
        $artifact = $this->createItem([
            'name'        => 'Scroll of Dexterity',
            'type'        => 'artifact',
            'base_damage' => null,
            'cost'        => 100,
        ]);

        $artifact->artifactProperty()->create(config('game.artifact_properties')[1]);

        // creates a spell
        $this->createItem([
            'name'        => 'Quick cast rapid healing spell',
            'type'        => 'spell',
            'base_damage' => null,
        ]);

        // creates a ring
        $this->createItem([
            'name'        => 'Basic ring of hatred and despair',
            'type'        => 'ring',
            'base_damage' => 3,
        ]);
    }
}
