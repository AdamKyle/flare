<?php

namespace Tests\Feature\Game\Kingdom\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\Kingdom;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class KingdomAttackControllerTest extends TestCase
{
    use RefreshDatabase;

    private $character;
    
    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()
                                                 ->givePlayerLocation()
                                                 ->kingdomManagement()
                                                 ->assignKingdom()
                                                 ->assignBuilding()
                                                 ->assignUnits();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testSelectKingdomsToAttack() {
        $user = $this->character->getUser();

        $response = $this->actingAs($user, 'api')->json('POST', route('kingdom.attack.selection', [
            'character' => $this->character->getCharacter()->id
        ]),[
            'selected_kingdoms' => [1]
        ])->response;

        $content = json_decode($response->content());
        
        $this->assertEquals(200, $response->status());

        $this->assertNotEmpty($content);
        $this->assertEquals('Sample', $content[0]->kingdom_name);
        $this->assertNotEmpty($content[0]->units);
        $this->assertEquals('Sample Unit', $content[0]->units[0]->name);
    }

    public function testFailToSelectKingdomsToAttackWhenYouDontOwnTheKingdom() {
        $user = $this->character->getUser();

        $response = $this->actingAs($user, 'api')->json('POST', route('kingdom.attack.selection', [
            'character' => $this->character->getCharacter()->id
        ]),[
            'selected_kingdoms' => [$this->createEnemyKingdom()->id]
        ])->response;

        $content = json_decode($response->content());

        $this->assertEquals(422, $response->status());

        $this->assertEquals('You do not own this kingdom.', $content->message);
    }

    public function testMissingParamsForSelectingKingdomsToAttack() {
        $user = $this->character->getUser();

        $response = $this->actingAs($user, 'api')->json('POST', route('kingdom.attack.selection', [
            'character' => $this->character->getCharacter()->id
        ]))->response;

        $content = json_decode($response->content());
        
        $this->assertEquals(422, $response->status());
        
        $this->assertEquals('Selected kingdoms is required.', $content->errors->selected_kingdoms[0]);
    }

    public function testParamsMustBeArrayForSelectingKingdomsToAttack() {
        $user = $this->character->getUser();

        $response = $this->actingAs($user, 'api')->json('POST', route('kingdom.attack.selection', [
            'character' => $this->character->getCharacter()->id
        ]), [
            'selected_kingdoms' => 1,
        ])->response;

        $content = json_decode($response->content());
        
        $this->assertEquals(422, $response->status());
        
        $this->assertEquals('Selected kingdoms must be an array.', $content->errors->selected_kingdoms[0]);
    }

    public function testAttackDefendingKingdom() {
        $user = $this->character->getUser();

        $response = $this->actingAs($user, 'api')->json('POST', route('kingdom.atack', [
            'character' => $this->character->getCharacter()->id
        ]), [
            'defender_id' => $this->createEnemyKingdom()->id,
            'units_to_send' => [
                'Sample' => [
                    'Sample Unit' => [
                        'amount_to_send' => 500,
                        'max_amount'     => 500,
                        'total_time'     => 1,
                    ]
                ]
            ]
        ])->response;

        $content = json_decode($response->content());
        
        $this->assertEquals(200, $response->status());

        $this->assertEmpty($content);
    }

    public function testAttackDefendingKingdomWithSiegeUnits() {
        $user = $this->character->getUser();

        $this->character->assignUnits([
            'name'         => 'Siege Unit',
            'siege_weapon' => true,
        ], 500);

        $response = $this->actingAs($user, 'api')->json('POST', route('kingdom.atack', [
            'character' => $this->character->getCharacter()->id
        ]), [
            'defender_id' => $this->createEnemyKingdom()->id,
            'units_to_send' => [
                'Sample' => [
                    'Siege Unit' => [
                        'amount_to_send' => 500,
                        'max_amount'     => 500,
                        'total_time'     => 1,
                    ]
                ]
            ]
        ])->response;

        $content = json_decode($response->content());
        
        $this->assertEquals(200, $response->status());

        $this->assertEmpty($content);
    }

    public function testFailToAttackDefendingKingdomThatDoesNotExist() {
        $user = $this->character->getUser();

        $response = $this->actingAs($user, 'api')->json('POST', route('kingdom.atack', [
            'character' => $this->character->getCharacter()->id
        ]), [
            'defender_id' => 27,
            'units_to_send' => [
                'Sample' => [
                    'Sample Unit' => [
                        'amount_to_send' => 500,
                        'max_amount'     => 500,
                        'total_time'     => 1,
                    ]
                ]
            ]
        ])->response;

        $content = json_decode($response->content());
        
        $this->assertEquals(422, $response->status());

        $this->assertEquals('Defender kingdom does not exist for: 27', $content->message);
    }

    public function testFailToAttackDefendingKingdomWhenAttackingKingdomDoesntExist() {
        $user = $this->character->getUser();

        $response = $this->actingAs($user, 'api')->json('POST', route('kingdom.atack', [
            'character' => $this->character->getCharacter()->id
        ]), [
            'defender_id' => $this->createEnemyKingdom()->id,
            'units_to_send' => [
                'bananas' => [
                    'Sample Unit' => [
                        'amount_to_send' => 500,
                        'max_amount'     => 500,
                        'total_time'     => 1,
                    ]
                ]
            ]
        ])->response;

        $content = json_decode($response->content());
        
        $this->assertEquals(422, $response->status());

        $this->assertEquals('No such kingdom for name: bananas', $content->message);
    }

    public function testFailToAttackDefendingKingdomWhenAttackingUnitsDoesntExist() {
        $user = $this->character->getUser();

        $response = $this->actingAs($user, 'api')->json('POST', route('kingdom.atack', [
            'character' => $this->character->getCharacter()->id
        ]), [
            'defender_id' => $this->createEnemyKingdom()->id,
            'units_to_send' => [
                'Sample' => [
                    'Sample Jazz' => [
                        'amount_to_send' => 500,
                        'max_amount'     => 500,
                        'total_time'     => 1,
                    ]
                ]
            ]
        ])->response;

        $content = json_decode($response->content());
        
        $this->assertEquals(422, $response->status());

        $this->assertEquals('No unit exists for name: Sample Jazz on this kingdom: Sample', $content->message);
    }

    public function testFailToAttackDefendingKingdomWhenAttackingUnitsexeedsAmountYouHave() {
        $user = $this->character->getUser();

        $response = $this->actingAs($user, 'api')->json('POST', route('kingdom.atack', [
            'character' => $this->character->getCharacter()->id
        ]), [
            'defender_id' => $this->createEnemyKingdom()->id,
            'units_to_send' => [
                'Sample' => [
                    'Sample Unit' => [
                        'amount_to_send' => 1500,
                        'max_amount'     => 500,
                        'total_time'     => 1,
                    ]
                ]
            ]
        ])->response;

        $content = json_decode($response->content());
        
        $this->assertEquals(422, $response->status());

        $this->assertEquals('You don\'t have enough units. You have: 500 and are trying to send: 1500 for: Sample', $content->message);
    }

    public function testCannotAttackDefendingKingdomMissingDefenderId() {
        $user = $this->character->getUser();

        $response = $this->actingAs($user, 'api')->json('POST', route('kingdom.atack', [
            'character' => $this->character->getCharacter()->id
        ]))->response;

        $content = json_decode($response->content());
        
        $this->assertEquals(422, $response->status());

        $this->assertEquals('Defender id is required', $content->errors->defender_id[0]);
    }

    public function testCannotAttackDefendingKingdomMissingUnitsToSend() {
        $user = $this->character->getUser();

        $response = $this->actingAs($user, 'api')->json('POST', route('kingdom.atack', [
            'character' => $this->character->getCharacter()->id
        ]), [
            'defender_id' => $this->createEnemyKingdom()->id,
        ])->response;

        $content = json_decode($response->content());
        
        $this->assertEquals(422, $response->status());
        
        $this->assertEquals('The units to send field is required.', $content->errors->units_to_send[0]);
    }

    public function testCannotAttackDefendingKingdomUnitsToSendMustBeAnArray() {
        $user = $this->character->getUser();

        $response = $this->actingAs($user, 'api')->json('POST', route('kingdom.atack', [
            'character' => $this->character->getCharacter()->id
        ]), [
            'defender_id'   => $this->createEnemyKingdom()->id,
            'units_to_send' => 1
        ])->response;

        $content = json_decode($response->content());
        
        $this->assertEquals(422, $response->status());
        
        $this->assertEquals('The units to send must be an array.', $content->errors->units_to_send[0]);
    }

    protected function createEnemyKingdom(): Kingdom {
        return (new CharacterFactory)->createBaseCharacter()
                                     ->givePlayerLocation()
                                     ->kingdomManagement()
                                     ->assignKingdom()
                                     ->assignBuilding([
                                        'is_walls' => true
                                    ])
                                    ->assignBuilding([
                                        'is_farm' => true
                                    ])
                                    ->assignBuilding()
                                     ->assignUnits()
                                     ->getKingdom();
    }


}