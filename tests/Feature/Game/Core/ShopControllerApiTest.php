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
use App\Flare\Events\UpdateCharacterInventoryEvent;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Events\UpdateCharacterSheetEvent;

class ShopControllerAPiTest extends TestCase {

    use RefreshDatabase,
        CreateUser,
        CreateRace,
        CreateItem,
        CreateClass;

    private $character;

    private $item;

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
                         ->json('GET', '/api/shop/' . $this->character->id)
                         ->response;

        $content = json_decode($response->content());

        $this->assertTrue(!empty($content->weapons));
        $this->assertTrue(!empty($content->armour));
        $this->assertTrue(!empty($content->rings));
        $this->assertTrue(!empty($content->spells));
        $this->assertTrue(!empty($content->artifacts));
        $this->assertTrue(!empty($content->inventory));
        $this->assertEquals(1, $content->artifacts[0]->artifact_property->id);

        foreach($content->inventory as $slot) {
            $this->assertTrue($slot->item->type !== 'quest');
        }
    }

    public function testShouldBeAbleToBuyItem() {
        Event::fake([
            UpdateCharacterInventoryEvent::class,
            UpdateCharacterSheetEvent::class,
            UpdateTopBarEvent::class,
        ]);

        $response = $this->actingAs($this->character->user, 'api')
                         ->json('POST', '/api/shop/buy/' . $this->character->id, [
                             'item_id' => $this->item->id,
                         ])
                         ->response;

        $content = json_decode($response->content());

        $this->assertEquals('Purchased ' . $this->item->name . '.', $content->message);
        $this->assertNotNull($this->character->inventory->slots->where('item_id', $this->item->id)->first());
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

        $this->item = $this->createItem([
            'name' => 'Broken Sword',
            'type' => 'weapon',
            'base_damage' => 3,
            'cost' => 40,
        ]);

        $questItem = $this->createItem([
            'name' => 'Gods Seel',
            'type' => 'quest',
            'base_damage' => null,
        ]);

        $this->character = resolve(CharacterBuilder::class)
                                ->setRace($race)
                                ->setClass($class)
                                ->createCharacter($user, 'Sample')
                                ->assignSkills()
                                ->character();

        $this->character->inventory->slots()->insert([
            [
                'inventory_id' => $this->character->inventory->id,
                'item_id'      => $this->item->id
            ],
            [
                'inventory_id' => $this->character->inventory->id,
                'item_id'      => $questItem->id
            ]
        ]);
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
