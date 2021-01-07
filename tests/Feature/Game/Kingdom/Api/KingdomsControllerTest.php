<?php

namespace Tests\Feature\Game\Kingdom\Api;

use DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateBuilding;
use Tests\Traits\CreateGameBuilding;
use Tests\Traits\CreateKingdom;
use Tests\Traits\CreateLocation;

class KingdomsControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateKingdom,
        CreateGameBuilding,
        CreateBuilding,
        CreateLocation;

    private $character;
    
    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testGetLocationDataWithNoKingdom() {
        $response = $this->actingAs($this->character->getUser(), 'api')->json('GET', route('kingdoms.location'), [
            'x_position' => 16,
            'y_position' => 16,
        ])->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertTrue(empty($content));
    }

    public function testFailToGetLocationDataMissingPositions() {
        $response = $this->actingAs($this->character->getUser(), 'api')->json('GET', route('kingdoms.location'))->response;

        $content = json_decode($response->content());

        $this->assertEquals(422, $response->status());
        $this->assertEquals($content->message, 'The given data was invalid.');
    }

    public function testFailToGetLocationDataCharacterIsDead() {
        $user = $this->character->updateCharacter(['is_dead' => true])->getUser();

        $response = $this->actingAs($user, 'api')->json('GET', route('kingdoms.location'))->response;

        $content = json_decode($response->content());

        $this->assertEquals(422, $response->status());
        $this->assertEquals($content->error, "You are dead and must revive before trying to do that. Dead people can't do things.");
    }

    public function testGetLocationDataWithKingdom() {
        $this->createKingdom([
            'character_id' => 1,
            'game_map_id'  => 1,
        ]);

        $response = $this->actingAs($this->character->getUser(), 'api')->json('GET', route('kingdoms.location'), [
            'x_position' => 16,
            'y_position' => 16,
        ])->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertTrue(!empty($content));
    }

    public function testSettleKingdom() {
        $this->createGameBuilding();

        $response = $this->actingAs($this->character->getUser(), 'api')->json('POST', route('kingdoms.settle', [
            'character' => 1
        ]), [
            'x_position' => 16,
            'y_position' => 16,
            'name'       => 'Apple Sauce',
            'color'      => [193, 66, 66, 1],
        ])->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertTrue(!empty($content));
        
        $this->assertTrue(
            $this->character->getCharacter()->kingdoms->first()->buildings->isNotEmpty()
        );
    }

    public function testFailToSettleKingdomMissingData() {
        $response = $this->actingAs($this->character->getUser(), 'api')->json('POST', route('kingdoms.settle', [
            'character' => 1
        ]))->response;

        $content = json_decode($response->content());

        $this->assertEquals(422, $response->status());
        $this->assertEquals('The given data was invalid.', $content->message);
        $this->assertTrue(!empty($content->errors->name));
        $this->assertTrue(!empty($content->errors->color));
        $this->assertTrue(!empty($content->errors->x_position));
        $this->assertTrue(!empty($content->errors->y_position));
    }

    public function testFailToSettleKingdomKingdomAlreadyExists() {
        $this->createKingdom([
            'character_id' => 1,
            'game_map_id'  => 1,
        ]);

        $response = $this->actingAs($this->character->getUser(), 'api')->json('POST', route('kingdoms.settle', [
            'character' => 1
        ]), [
            'x_position' => 16,
            'y_position' => 16,
            'name'       => 'Apple Sauce',
            'color'      => [193, 66, 66, 1],
        ])->response;

        $content = json_decode($response->content());

        $this->assertEquals(422, $response->status());
        $this->assertEquals('Cannot settle here.', $content->message);
    }

    public function testFailToSettleKingdomForLocation() {

        $this->createLocation([
            'name'                 => 'Kingdom',
            'game_map_id'          => 1,
            'quest_reward_item_id' => null,
            'description'          => 'null',
            'is_port'              => false,
            'x'                    => 16,
            'y'                    => 16,
        ]);

        $response = $this->actingAs($this->character->getUser(), 'api')->json('POST', route('kingdoms.settle', [
            'character' => 1
        ]), [
            'x_position' => 16,
            'y_position' => 16,
            'name'       => 'Apple Sauce',
            'color'      => [193, 66, 66, 1],
        ])->response;

        $content = json_decode($response->content());

        $this->assertEquals(422, $response->status());
        $this->assertEquals('Cannot settle here.', $content->message);
    }

    public function testUpgradeBuildingWhileOnline() {
        $this->createKingdom([
            'character_id' => 1,
            'game_map_id'  => 1,
        ]);

        $gameBuilding = $this->createGameBuilding();

        DB::table('sessions')->insert([[
            'id'           => '1',
            'user_id'      => $this->character->getUser()->id,
            'ip_address'   => '1',
            'user_agent'   => '1',
            'payload'      => '1',
            'last_activity'=> 1602801731,
        ]]);

        $this->createBuilding([
            'game_building_id'   => $gameBuilding->id,
            'kingdoms_id'        => 1,
            'level'              => 1,
            'current_defence'    => $gameBuilding->base_defence,
            'current_durability' => $gameBuilding->base_durability,
            'max_defence'        => $gameBuilding->base_defence,
            'max_durability'     => $gameBuilding->base_durability,
        ]);

        $response = $this->actingAs($this->character->getUser(), 'api')->json('POST', route('kingdoms.building.upgrade', [
            'character' => 1,
            'building'  => 1,
        ]))->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertTrue(!empty($content));
    }

    public function testUpgradeBuildingWithEmail() {
        $this->createKingdom([
            'character_id' => 1,
            'game_map_id'  => 1,
        ]);

        $gameBuilding = $this->createGameBuilding();

        $this->createBuilding([
            'game_building_id'   => $gameBuilding->id,
            'kingdoms_id'        => 1,
            'level'              => 1,
            'current_defence'    => $gameBuilding->base_defence,
            'current_durability' => $gameBuilding->base_durability,
            'max_defence'        => $gameBuilding->base_defence,
            'max_durability'     => $gameBuilding->base_durability,
        ]);

        $response = $this->actingAs($this->character->getUser(), 'api')->json('POST', route('kingdoms.building.upgrade', [
            'character' => 1,
            'building'  => 1,
        ]))->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertTrue(!empty($content));
    }

    public function testUpgradeBuildingThatIsResource() {
        $this->createKingdom([
            'character_id' => 1,
            'game_map_id'  => 1,
        ]);

        $gameBuilding = $this->createGameBuilding([
            'is_resource_building' => true,
        ]);

        $this->createBuilding([
            'game_building_id'   => $gameBuilding->id,
            'kingdoms_id'        => 1,
            'level'              => 1,
            'current_defence'    => $gameBuilding->base_defence,
            'current_durability' => $gameBuilding->base_durability,
            'max_defence'        => $gameBuilding->base_defence,
            'max_durability'     => $gameBuilding->base_durability,
        ]);

        $response = $this->actingAs($this->character->getUser(), 'api')->json('POST', route('kingdoms.building.upgrade', [
            'character' => 1,
            'building'  => 1,
        ]))->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertTrue(!empty($content));
    }

    public function testFailToUpgradeNotEnoughResources() {
        $this->createKingdom([
            'character_id'       => 1,
            'game_map_id'        => 1,
            'current_stone'      => 0,
            'current_wood'       => 0,
            'current_clay'       => 0,
            'current_iron'       => 0,
            'current_population' => 0,
        ]);

        $gameBuilding = $this->createGameBuilding([
            'is_resource_building' => true,
        ]);

        $this->createBuilding([
            'game_building_id'   => $gameBuilding->id,
            'kingdoms_id'        => 1,
            'level'              => 1,
            'current_defence'    => $gameBuilding->base_defence,
            'current_durability' => $gameBuilding->base_durability,
            'max_defence'        => $gameBuilding->base_defence,
            'max_durability'     => $gameBuilding->base_durability,
        ]);

        $response = $this->actingAs($this->character->getUser(), 'api')->json('POST', route('kingdoms.building.upgrade', [
            'character' => 1,
            'building'  => 1,
        ]))->response;

        $content = json_decode($response->content());

        $this->assertEquals(422, $response->status());
        $this->assertEquals("You don't have the resources.", $content->message);
    }
}
