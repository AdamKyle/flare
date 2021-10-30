<?php

namespace Tests\Feature\Game\Core\Api;

use App\Flare\Models\ItemAffix;
use App\Flare\Values\ItemUsabilityType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterBoon;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateItem;

class CharacterSheetControllerApiTest extends TestCase {

    use RefreshDatabase,
        CreateUser,
        CreateRole,
        CreateItem,
        CreateCharacterBoon;

    private $character;

    public function setUp(): void {
        parent::setUp();

        $role = $this->createAdminRole();

        $this->createAdmin($role, []);

        $this->character = (new CharacterFactory)->createBaseCharacter()
                                                 ->givePlayerLocation()
                                                 ->equipStartingEquipment()
                                                 ->inventoryManagement()
                                                 ->giveItem($this->createItem([
                                                    'name' => 'Rusty Dagger',
                                                    'type' => 'weapon',
                                                    'base_damage' => 3,
                                                ]))
                                                ->getCharacterFactory();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testGetCharacterInfo() {
        $character = $this->character->getCharacter(false);
        $user      = $this->character->getUser();

        $response = $this->actingAs($user)
                         ->json('GET', '/api/character-sheet/' . $character->id)
                         ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertFalse(empty($content->sheet->skills));
        $this->assertEquals($this->character->getCharacter(false)->name, $content->sheet->name);
    }

    public function testGetCharacterInfoWithBothLeftAndRightWeapon() {
        $character = $this->character->getCharacter(false);
        $user      = $this->character->getUser();

        $response = $this->actingAs($user)
                         ->json('GET', '/api/character-sheet/' . $character->id)
                         ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertFalse(empty($content->sheet->skills));
        $this->assertEquals($this->character->getCharacter(false)->name, $content->sheet->name);
    }

    public function testGetCharacterInfoWithRightHand() {
        $character = $this->character->getCharacter(false);
        $user      = $this->character->getUser();

        $response = $this->actingAs($user)
                         ->json('GET', '/api/character-sheet/' . $character->id)
                         ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertFalse(empty($content->sheet->skills));
        $this->assertEquals($this->character->getCharacter(false)->name, $content->sheet->name);
    }

    public function testGetCharacterInfoWithNoWeapon() {
        $character = $this->character->inventoryManagement()->unequipAll()->getCharacterFactory()->getCharacter(false);
        $user      = $this->character->getUser();

        $response = $this->actingAs($user)
                         ->json('GET', '/api/character-sheet/' . $character->id)
                         ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertFalse(empty($content->sheet->skills));
    }

    public function testGetCharacterInfoWithModdedStat() {

        $character = $this->character->inventoryManagement()
                                     ->giveItem($this->createItem([
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
                                            'str_mod'              => '1.10',
                                            'cost'                 => 100,
                                        ])->id,
                                    ]))
                                    ->equipLeftHand('sword')
                                    ->getCharacterFactory()
                                    ->getCharacter(false);

        $user     = $this->character->getUser();

        $response = $this->actingAs($user)
                         ->json('GET', '/api/character-sheet/' . $character->id)
                         ->response;

        $content = json_decode($response->content());

        $this->assertFalse($content->sheet->str_modded === $this->character->getCharacter(false)->str);
    }

    public function testForceNameChange() {
        $character = $this->character->getCharacter(false);
        $user      = $this->character->getUser();

        $response = $this->actingAs($user)
                         ->json('POST', '/api/character-sheet/'.$character->id.'/name-change', [
                             'name' => 'Apples'
                         ])
                         ->response;

        $this->assertEquals(200, $response->status());
        $this->assertEquals('Apples', $this->character->getCharacter(false)->name);
    }

    public function testBasicLocationInformation() {
        $character = $this->character->givePlayerLocation()->getCharacter(false);

        $response = $this->actingAs($character->user)
            ->json('GET', '/api/character-location-data/' . $character->id)
            ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());

        $this->assertEquals($character->x_position, $content->x_position);
        $this->assertEquals($character->y_position, $content->y_position);
        $this->assertEquals($character->gold, $content->gold);
    }

    public function testGlobalTimeOut() {
        $character = $this->character->givePlayerLocation()->getCharacter(false);

        $response = $this->actingAs($character->user)
            ->json('POST', '/api/character-timeout')
            ->response;

        $this->assertEquals(200, $response->status());

        $this->assertNotNull($this->character->getUser()->timeout_until);
    }

    public function testGetBoons() {
        $character = $this->character->givePlayerLocation()->getCharacter(false);

        $this->createCharacterBoon([
            'character_id' => $character->id,
            'stat_bonus'   => 2.0,
            'started'      => now(),
            'complete'     => now()->addMinutes(100),
            'type'         => ItemUsabilityType::STAT_INCREASE
        ]);

        $character = $character->refresh();

        $response = $this->actingAs($character->user)
            ->json('GET', '/api/character-sheet/'.$character->id.'/active-boons')
            ->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());

        $this->assertCount(1, $content->active_boons);
    }

    public function testCannotCancelAnotherPlayersBoon() {
        $character = $this->character->givePlayerLocation()->getCharacter(false);

        $boon = $this->createCharacterBoon([
            'character_id' => $character->id,
            'stat_bonus'   => 2.0,
            'started'      => now(),
            'complete'     => now()->addMinutes(100),
            'type'         => ItemUsabilityType::STAT_INCREASE
        ]);

        $otherCharacter = (new CharacterFactory())->createBaseCharacter()->getCharacter(false);


        $this->actingAs($otherCharacter->user)
             ->json('POST', '/api/character-sheet/'.$otherCharacter->id.'/remove-boon/' . $boon->id);

        $character = $character->refresh();

        $this->assertNotEmpty($character->boons->toArray());
    }

    public function testCanCancelOwnBoon() {
        $character = $this->character->givePlayerLocation()->getCharacter(false);

        $boon = $this->createCharacterBoon([
            'character_id' => $character->id,
            'stat_bonus'   => 2.0,
            'started'      => now(),
            'complete'     => now()->addMinutes(100),
            'type'         => ItemUsabilityType::STAT_INCREASE
        ]);

        $character = $character->refresh();

        $this->actingAs($character->user)
            ->json('POST', '/api/character-sheet/'.$character->id.'/remove-boon/' . $boon->id);


        $character = $character->refresh();

        $this->assertEmpty($character->boons->toArray());
    }
}
