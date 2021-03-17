<?php

namespace Tests\Feature\Game\Kingdom\Api;

use DB;
use Mail;
use App\Admin\Mail\GenericMail;
use App\Flare\Models\KingdomBuildingInQueue;
use App\Flare\Models\UnitInQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateKingdomBuilding;
use Tests\Traits\CreateGameKingdomBuilding;
use Tests\Traits\CreateGameUnit;
use Tests\Traits\CreateKingdom;
use Tests\Traits\CreateLocation;

class KingdomsControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateKingdom,
        CreateGameKingdomBuilding,
        CreateKingdomBuilding,
        CreateLocation,
        CreateGameUnit;

    private $character;
    
    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testSettleKingdom() {
        $this->createGameKingdomBuilding();

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

    public function testUpgradeKingdomBuildingWhileOnline() {
        $this->createKingdom([
            'character_id' => 1,
            'game_map_id'  => 1,
        ]);

        $gameKingdomBuilding = $this->createGameKingdomBuilding();

        DB::table('sessions')->insert([[
            'id'           => '1',
            'user_id'      => $this->character->getUser()->id,
            'ip_address'   => '1',
            'user_agent'   => '1',
            'payload'      => '1',
            'last_activity'=> 1602801731,
        ]]);

        $this->createKingdomBuilding([
            'game_building_id'   => $gameKingdomBuilding->id,
            'kingdom_id'        => 1,
            'level'              => 1,
            'current_defence'    => $gameKingdomBuilding->base_defence,
            'current_durability' => $gameKingdomBuilding->base_durability,
            'max_defence'        => $gameKingdomBuilding->base_defence,
            'max_durability'     => $gameKingdomBuilding->base_durability,
        ]);

        $response = $this->actingAs($this->character->getUser(), 'api')->json('POST', route('kingdoms.building.upgrade', [
            'character' => 1,
            'building'  => 1,
        ]))->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertTrue(!empty($content));
    }

    public function testUpgradeKingdomBuildingWithEmail() {
        $this->createKingdom([
            'character_id' => 1,
            'game_map_id'  => 1,
        ]);

        $gameKingdomBuilding = $this->createGameKingdomBuilding();

        $this->createKingdomBuilding([
            'game_building_id'   => $gameKingdomBuilding->id,
            'kingdom_id'        => 1,
            'level'              => 1,
            'current_defence'    => $gameKingdomBuilding->base_defence,
            'current_durability' => $gameKingdomBuilding->base_durability,
            'max_defence'        => $gameKingdomBuilding->base_defence,
            'max_durability'     => $gameKingdomBuilding->base_durability,
        ]);

        $response = $this->actingAs($this->character->getUser(), 'api')->json('POST', route('kingdoms.building.upgrade', [
            'character' => 1,
            'building'  => 1,
        ]))->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertTrue(!empty($content));
    }

    public function testUpgradeKingdomBuildingThatIsResource() {
        $this->createKingdom([
            'character_id' => 1,
            'game_map_id'  => 1,
        ]);

        $gameKingdomBuilding = $this->createGameKingdomBuilding([
            'is_resource_building' => true,
        ]);

        $this->createKingdomBuilding([
            'game_building_id'   => $gameKingdomBuilding->id,
            'kingdom_id'        => 1,
            'level'              => 1,
            'current_defence'    => $gameKingdomBuilding->base_defence,
            'current_durability' => $gameKingdomBuilding->base_durability,
            'max_defence'        => $gameKingdomBuilding->base_defence,
            'max_durability'     => $gameKingdomBuilding->base_durability,
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

        $gameKingdomBuilding = $this->createGameKingdomBuilding([
            'is_resource_building' => true,
        ]);

        $this->createKingdomBuilding([
            'game_building_id'   => $gameKingdomBuilding->id,
            'kingdom_id'        => 1,
            'level'              => 1,
            'current_defence'    => $gameKingdomBuilding->base_defence,
            'current_durability' => $gameKingdomBuilding->base_durability,
            'max_defence'        => $gameKingdomBuilding->base_defence,
            'max_durability'     => $gameKingdomBuilding->base_durability,
        ]);

        $response = $this->actingAs($this->character->getUser(), 'api')->json('POST', route('kingdoms.building.upgrade', [
            'character' => 1,
            'building'  => 1,
        ]))->response;

        $content = json_decode($response->content());

        $this->assertEquals(422, $response->status());
        $this->assertEquals("You don't have the resources.", $content->message);
    }

    public function testRecruitUnit() {
        $kingdom = $this->createKingdom([
            'character_id'       => 1,
            'game_map_id'        => 1,
            'current_stone'      => 1000,
            'current_wood'       => 1000,
            'current_clay'       => 1000,
            'current_iron'       => 1000,
            'current_population' => 100,
        ]);

        $this->createGameUnit();

        $user = $this->character->getUser();

        $response = $this->actingAs($user, 'api')->json('POST', route('kingdoms.recruit.units', [
            'kingdom'  => 1,
            'gameUnit' => 1,
        ]), [
            'amount' => 5
        ])->response;

        $this->assertEquals(200, $response->status());

        $kingdom = $kingdom->refresh();

        $this->assertTrue($kingdom->units->isNotEmpty());
        $this->assertEquals(5, $kingdom->units->first()->amount);
    }

    public function testRecruitUnitWhenOnline() {
        $kingdom = $this->createKingdom([
            'character_id'       => 1,
            'game_map_id'        => 1,
            'current_stone'      => 1000,
            'current_wood'       => 1000,
            'current_clay'       => 1000,
            'current_iron'       => 1000,
            'current_population' => 100,
        ]);

        $this->createGameUnit();

        $user = $this->character->getUser();

        DB::table('sessions')->insert([[
            'id'           => '1',
            'user_id'      => $user->id,
            'ip_address'   => '1',
            'user_agent'   => '1',
            'payload'      => '1',
            'last_activity'=> 1602801731,
        ]]);

        $response = $this->actingAs($user, 'api')->json('POST', route('kingdoms.recruit.units', [
            'kingdom'  => 1,
            'gameUnit' => 1,
        ]), [
            'amount' => 5
        ])->response;

        $this->assertEquals(200, $response->status());

        $kingdom = $kingdom->refresh();

        $this->assertTrue($kingdom->units->isNotEmpty());
        $this->assertEquals(5, $kingdom->units->first()->amount);
    }

    public function testRecruitUnitWhenNotOnline() {
        Mail::fake();

        $kingdom = $this->createKingdom([
            'character_id'       => 1,
            'game_map_id'        => 1,
            'current_stone'      => 1000,
            'current_wood'       => 1000,
            'current_clay'       => 1000,
            'current_iron'       => 1000,
            'current_population' => 100,
        ]);

        $this->createGameUnit();

        $user = $this->character->getUser();

        $response = $this->actingAs($user, 'api')->json('POST', route('kingdoms.recruit.units', [
            'kingdom'  => 1,
            'gameUnit' => 1,
        ]), [
            'amount' => 5
        ])->response;

        $this->assertEquals(200, $response->status());

        $kingdom = $kingdom->refresh();

        $this->assertTrue($kingdom->units->isNotEmpty());
        $this->assertEquals(5, $kingdom->units->first()->amount);

        Mail::assertSent(GenericMail::class, 1);
    }
    public function testRecruitAdditionalUnits() {
        $kingdom = $this->createKingdom([
            'character_id'       => 1,
            'game_map_id'        => 1,
            'current_stone'      => 1000,
            'current_wood'       => 1000,
            'current_clay'       => 1000,
            'current_iron'       => 1000,
            'current_population' => 100,
        ]);

        $this->createGameUnit();

        $kingdom->units()->create([
            'game_unit_id' => 1,
            'kingdom_id'   => 1,
            'amount'       => 5,
        ]);

        $user = $this->character->getUser();

        $response = $this->actingAs($user, 'api')->json('POST', route('kingdoms.recruit.units', [
            'kingdom'  => 1,
            'gameUnit' => 1,
        ]), [
            'amount' => 5
        ])->response;

        $this->assertEquals(200, $response->status());

        $kingdom = $kingdom->refresh();

        $this->assertTrue($kingdom->units->isNotEmpty());
        $this->assertEquals(10, $kingdom->units->first()->amount);
    }

    public function testRecruitUnitsForKingdomWithUnits() {
        $kingdom = $this->createKingdom([
            'character_id'       => 1,
            'game_map_id'        => 1,
            'current_stone'      => 1000,
            'current_wood'       => 1000,
            'current_clay'       => 1000,
            'current_iron'       => 1000,
            'current_population' => 100,
        ]);

        $this->createGameUnit();

        $kingdom->units()->create([
            'game_unit_id' => 1,
            'kingdom_id'   => 1,
            'amount'       => 5,
        ]);

        $user = $this->character->getUser();

        $response = $this->actingAs($user, 'api')->json('POST', route('kingdoms.recruit.units', [
            'kingdom'  => 1,
            'gameUnit' => $this->createGameUnit(['name' => 'Axemen']),
        ]), [
            'amount' => 5
        ])->response;

        $this->assertEquals(200, $response->status());

        $kingdom = $kingdom->refresh();

        $this->assertTrue($kingdom->units->isNotEmpty());
        $this->assertEquals(5, $kingdom->units->where('game_unit_id', 2)->first()->amount);
    }

    public function testFailToRecruitUnitsWhenAmountIsZero() {
        $kingdom = $this->createKingdom([
            'character_id'       => 1,
            'game_map_id'        => 1,
            'current_stone'      => 1000,
            'current_wood'       => 1000,
            'current_clay'       => 1000,
            'current_iron'       => 1000,
            'current_population' => 100,
        ]);

        $this->createGameUnit();

        $user = $this->character->getUser();

        $response = $this->actingAs($user, 'api')->json('POST', route('kingdoms.recruit.units', [
            'kingdom'  => 1,
            'gameUnit' => 1,
        ]), [
            'amount' => 0
        ])->response;

        $this->assertEquals(422, $response->status());

        $content = json_decode($response->content());

        $kingdom = $kingdom->refresh();

        $this->assertEquals('Too few units to recuit.', $content->message);
    }

    public function testNotEnoughResourcesToRecruit() {
        $kingdom = $this->createKingdom([
            'character_id'       => 1,
            'game_map_id'        => 1,
            'current_stone'      => 0,
            'current_wood'       => 0,
            'current_clay'       => 0,
            'current_iron'       => 0,
            'current_population' => 0,
        ]);

        $this->createGameUnit();

        $user = $this->character->getUser();

        $response = $this->actingAs($user, 'api')->json('POST', route('kingdoms.recruit.units', [
            'kingdom'  => 1,
            'gameUnit' => 1,
        ]), [
            'amount' => 150
        ])->response;

        $this->assertEquals(422, $response->status());

        $content = json_decode($response->content());

        $kingdom = $kingdom->refresh();

        $this->assertEquals("You don't have the resources.", $content->message);
    }

    public function testCanCancelRecruitOrder() {
        $this->createGameUnit();

        $kingdom = $this->createKingdom([
            'character_id'       => 1,
            'game_map_id'        => 1,
            'current_stone'      => 0,
            'current_wood'       => 0,
            'current_clay'       => 0,
            'current_iron'       => 0,
            'current_population' => 0,
        ]);

        $this->createUnitQueue([
            'character_id' => 1,
            'kingdom_id'   => $kingdom->id,
            'game_unit_id' => 1,
            'amount'       => 150,
            'completed_at' => now()->addMinutes(150)
        ]);

        $user = $this->character->getUser();

        $response = $this->actingAs($user, 'api')->json('POST', route('kingdoms.recruit.units.cancel'), [
            'queue_id' => 1
        ])->response;

        $this->assertEquals(200, $response->status());
        $this->assertTrue(UnitInQueue::all()->isEmpty());

        $kingdom = $kingdom->refresh();

        $this->assertTrue($kingdom->current_wood > 0);
        $this->assertTrue($kingdom->current_clay > 0);
        $this->assertTrue($kingdom->current_stone > 0);
        $this->assertTrue($kingdom->current_iron > 0);
    }

    public function testCannotCancelRecruitOrderForNonExistantQueue() {
        $user = $this->character->getUser();

        $response = $this->actingAs($user, 'api')->json('POST', route('kingdoms.recruit.units.cancel'), [
            'queue_id' => 1
        ])->response;

        $this->assertEquals(422, $response->status());

        $content = json_decode($response->content());

        $this->assertEquals('Invalid Input.', $content->message);
    }

    public function testCannotCancelRecruitOrderToLittleTimeLeft() {
        $this->createGameUnit();

        $kingdom = $this->createKingdom([
            'character_id'       => 1,
            'game_map_id'        => 1,
            'current_stone'      => 0,
            'current_wood'       => 0,
            'current_clay'       => 0,
            'current_iron'       => 0,
            'current_population' => 0,
        ]);

        $this->createUnitQueue([
            'character_id' => 1,
            'kingdom_id'   => $kingdom->id,
            'game_unit_id' => 1,
            'amount'       => 150,
            'completed_at' => now()->subMinute(10)
        ]);

        $user = $this->character->getUser();

        $response = $this->actingAs($user, 'api')->json('POST', route('kingdoms.recruit.units.cancel'), [
            'queue_id' => 1
        ])->response;

        $this->assertEquals(422, $response->status());

        $content = json_decode($response->content());

        $this->assertEquals('Your units are almost done. You can\'t cancel this late in the process.', $content->message);
    }

    public function testCanCancelKingdomBuildingOrder() {
        $kingdom = $this->createKingdom([
            'character_id'       => 1,
            'game_map_id'        => 1,
            'current_stone'      => 0,
            'current_wood'       => 0,
            'current_clay'       => 0,
            'current_iron'       => 0,
            'current_population' => 0,
        ]);

        $this->createKingdomBuilding([
            'game_building_id'   => $this->createGameKingdomBuilding()->id,
            'kingdom_id'        => 1,
            'level'              => 1,
            'current_defence'    => 100,
            'current_durability' => 100,
            'max_defence'        => 100,
            'max_durability'     => 100,
        ]);

        $this->createKingdomBuildingQueue([
            'character_id' => 1,
            'kingdom_id'   => $kingdom->id,
            'building_id'  => 1,
            'to_level'     => 2,
            'completed_at' => now()->addMinutes(150)
        ]);

        $user = $this->character->getUser();

        $response = $this->actingAs($user, 'api')->json('POST', route('kingdoms.building.queue.delete'), [
            'queue_id' => 1
        ])->response;

        $this->assertEquals(200, $response->status());
        $this->assertTrue(KingdomBuildingInQueue::all()->isEmpty());

        $kingdom = $kingdom->refresh();

        $this->assertTrue($kingdom->current_wood > 0);
        $this->assertTrue($kingdom->current_clay > 0);
        $this->assertTrue($kingdom->current_stone > 0);
        $this->assertTrue($kingdom->current_iron > 0);
    }

    public function testCannotCancelKingdomBuildingOrderForNonExistantQueue() {
        $user = $this->character->getUser();

        $response = $this->actingAs($user, 'api')->json('POST', route('kingdoms.building.queue.delete'), [
            'queue_id' => 1
        ])->response;

        $this->assertEquals(422, $response->status());

        $content = json_decode($response->content());

        $this->assertEquals('Invalid Input.', $content->message);
    }

    public function testCannotCancelKingdomBuildingOrderToLittleTimeLeft() {
        $kingdom = $this->createKingdom([
            'character_id'       => 1,
            'game_map_id'        => 1,
            'current_stone'      => 0,
            'current_wood'       => 0,
            'current_clay'       => 0,
            'current_iron'       => 0,
            'current_population' => 0,
        ]);

        $this->createKingdomBuilding([
            'game_building_id'   => $this->createGameKingdomBuilding()->id,
            'kingdom_id'        => 1,
            'level'              => 1,
            'current_defence'    => 100,
            'current_durability' => 100,
            'max_defence'        => 100,
            'max_durability'     => 100,
        ]);

        $this->createKingdomBuildingQueue([
            'character_id' => 1,
            'kingdom_id'   => $kingdom->id,
            'building_id'  => 1,
            'to_level'     => 2,
            'completed_at' => now()->subMinutes(10),
            'started_at'   => now()
        ]);

        $user = $this->character->getUser();

        $response = $this->actingAs($user, 'api')->json('POST', route('kingdoms.building.queue.delete'), [
            'queue_id' => 1
        ])->response;

        $this->assertEquals(422, $response->status());

        $content = json_decode($response->content());

        $this->assertEquals('Your workers are almost done. You can\'t cancel this late in the process.', $content->message);
    }
}
