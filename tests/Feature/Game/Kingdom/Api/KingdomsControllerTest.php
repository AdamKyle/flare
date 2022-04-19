<?php

namespace Tests\Feature\Game\Kingdom\Api;

use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Kingdoms\Jobs\RecruitUnits;
use App\Game\Kingdoms\Jobs\UpgradeBuilding;
use DB;
use Cache;
use Queue;
use Mockery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\Kingdom;
use App\Flare\Models\UnitInQueue;
use App\Flare\Models\Character;
use App\Flare\Models\GameBuilding;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameUnit;
use App\Flare\Models\KingdomBuilding;
use App\Game\Kingdoms\Mail\RecruitedUnits;
use App\Game\Kingdoms\Service\KingdomBuildingService;
use App\Game\Kingdoms\Values\KingdomMaxValue;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateKingdomBuilding;
use Tests\Traits\CreateGameBuilding;
use Tests\Traits\CreateGameUnit;
use Tests\Traits\CreateKingdom;
use Tests\Traits\CreateLocation;

class KingdomsControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateKingdom,
        CreateGameBuilding,
        CreateKingdomBuilding,
        CreateLocation,
        CreateGameUnit,
        CreateGameMap;

    private $character;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        $this->createGameMap(['name' => 'Purgatory']);
        $this->createGameMap(['name' => 'Hell']);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testGetKingdomData() {
        $this->createKingdom([
            'character_id' => Character::first()->id,
            'game_map_id'  => GameMap::first()->id,
        ]);

        $response = $this->actingAs($this->character->getUser())->json('GET', route('kingdoms.location', [
            'kingdom' => Kingdom::first()->id,
            'character' => $this->character->getCharacter(false)->id,
        ]))->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());

        $this->assertEquals('Sample', $content->name);
    }

    public function testSettleKingdom() {
        $this->createGameBuilding();

        $response = $this->actingAs($this->character->getUser())->json('POST', route('kingdoms.settle', [
            'character' => Character::first()->id
        ]), [
            'x_position'     => 496,
            'y_position'     => 496,
            'name'           => 'Apple Sauce',
            'color'          => [193, 66, 66, 1],
            'kingdom_amount' => 0
        ])->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertTrue(Cache::has('character-kingdoms-Surface-' . $this->character->getCharacter(false)->id));

        $this->assertTrue(
            $this->character->getCharacter(false)->kingdoms->first()->buildings->isNotEmpty()
        );
    }

    public function testCannotSettleKingdomWithSameName() {
        $this->createGameBuilding();

        $this->createKingdom([
            'character_id' => $this->character->getCharacter(false)->id,
            'name' => 'Apple Sauce',
            'game_map_id' => GameMap::first()->id,
        ]);

        $response = $this->actingAs($this->character->getUser())->json('POST', route('kingdoms.settle', [
            'character' => Character::first()->id
        ]), [
            'x_position'     => 16,
            'y_position'     => 16,
            'name'           => 'Apple Sauce',
            'color'          => [193, 66, 66, 1],
            'kingdom_amount' => 0
        ])->response;

        $content = json_decode($response->content());

        $this->assertEquals(422, $response->status());
        $this->assertEquals('Name is taken', $content->message);
    }

    public function testCannotAffordToSettleKingdom() {
        $this->createGameBuilding();

        $user = $this->character->kingdomManagement()->assignKingdom()->getUser();

        $response = $this->actingAs($user)->json('POST', route('kingdoms.settle', [
            'character' => Character::first()->id
        ]), [
            'x_position'     => 26,
            'y_position'     => 26,
            'name'           => 'Apple Sauce',
            'color'          => [193, 66, 66, 1],
            'kingdom_amount' => 1
        ])->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertEquals('You don\'t have the gold.', $content->message);
    }

    public function testRenameKingdom() {
        $user = $this->character->kingdomManagement()->assignKingdom()->getUser();

        $response = $this->actingAs($user)->json('POST', route('kingdom.rename', [
            'kingdom' => Kingdom::first()->id
        ]), [
            'name' => 'Test Kingdom 456'
        ])->response;

        $content = json_decode($response->content());


        $this->assertEquals(200, $response->status());
        $this->assertEmpty($content);
    }

    public function testCannotRenameKingdom() {
        $user = $this->character->kingdomManagement()->assignKingdom()->getUser();

        $response = $this->actingAs($user)->json('POST', route('kingdom.rename', [
            'kingdom' => Kingdom::first()->id
        ]))->response;

        $content = json_decode($response->content());

        $this->assertEquals(422, $response->status());
        $this->assertEquals('Name is required.', $content->errors->name[0]);
    }

    public function testSettleKingdomWithCache() {
        Cache::put('character-kingdoms-Surface-' . $this->character->getCharacter(false)->id, [['sample data']]);

        $this->createGameBuilding();

        $response = $this->actingAs($this->character->getUser())->json('POST', route('kingdoms.settle', [
            'character' => Character::first()->id
        ]), [
            'x_position'     => 16,
            'y_position'     => 16,
            'name'           => 'Apple Sauce',
            'color'          => [193, 66, 66, 1],
            'kingdom_amount' => 0,
        ])->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertTrue(empty($content));
        $this->assertEquals(2, count(Cache::get('character-kingdoms-Surface-' . $this->character->getCharacter(false)->id)));

        $this->assertTrue(
            $this->character->getCharacter(false)->kingdoms->first()->buildings->isNotEmpty()
        );
    }

    public function testFailToSettleKingdomMissingData() {
        $response = $this->actingAs($this->character->getUser())->json('POST', route('kingdoms.settle', [
            'character' => Character::first()->id
        ]))->response;

        $content = json_decode($response->content());

        $this->assertEquals(422, $response->status());
        $this->assertEquals('The given data was invalid.', $content->message);
        $this->assertTrue(!empty($content->errors->name));
        $this->assertTrue(!empty($content->errors->x_position));
        $this->assertTrue(!empty($content->errors->y_position));
    }

    public function testFailToSettleKingdomKingdomAlreadyExists() {
        $this->createKingdom([
            'character_id' => Character::first()->id,
            'game_map_id'  => GameMap::first()->id,
        ]);

        $response = $this->actingAs($this->character->getUser())->json('POST', route('kingdoms.settle', [
            'character' => Character::first()->id
        ]), [
            'x_position'     => 16,
            'y_position'     => 16,
            'name'           => 'Apple Sauce',
            'color'          => [193, 66, 66, 1],
            'kingdom_amount' => 0,
        ])->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertEquals('Cannot settle here.', $content->message);
    }

    public function testFailToSettleKingdomForLocation() {

        $this->createLocation([
            'name'                 => 'Kingdom',
            'game_map_id'          => GameMap::first()->id,
            'quest_reward_item_id' => null,
            'description'          => 'null',
            'is_port'              => false,
            'x'                    => 16,
            'y'                    => 16,
        ]);

        $response = $this->actingAs($this->character->getUser())->json('POST', route('kingdoms.settle', [
            'character' => Character::first()->id
        ]), [
            'x_position'      => 16,
            'y_position'      => 16,
            'name'            => 'Apple Sauce',
            'color'           => [193, 66, 66, 1],
            'kingdom_amount' => 0
        ])->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertEquals('Cannot settle here.', $content->message);
    }

    public function testRebuildKingdomBuildingOnline() {

        DB::table('sessions')->insert([[
            'id'           => '1',
            'user_id'      => $this->character->getUser()->id,
            'ip_address'   => '1',
            'user_agent'   => '1',
            'payload'      => '1',
            'last_activity'=> 1602801731,
        ]]);

        $this->createKingdom([
            'character_id' => Character::first()->id,
            'game_map_id'  => GameMap::first()->id,
        ]);

        $this->createGameBuilding();

        $this->createKingdomBuilding([
            'game_building_id'   => GameBuilding::first()->id,
            'kingdom_id'         => Kingdom::first()->id,
            'level'              => 1,
            'current_defence'    => 300,
            'current_durability' => 0,
            'max_defence'        => 300,
            'max_durability'     => 300,
        ]);

        $user = $this->character->getUser();

        $user->update([
            'show_building_rebuilt_messages' => true,
        ]);

        $user = $user->refresh();

        $response = $this->actingAs($user)->json('POST', route('kingdoms.building.rebuild', [
            'character'  => Character::first()->id,
            'building'   => KingdomBuilding::first()->id,
        ]))->response;

        $this->assertEquals(200, $response->status());

        $building = Kingdom::first()->buildings->first();

        $this->assertNotEquals(0, $building->current_durability);
        $this->assertEquals(300, $building->current_durability);
    }

    public function testRebuildKingdomBuildingOffLine() {

        $this->createKingdom([
            'character_id' => Character::first()->id,
            'game_map_id'  => GameMap::first()->id,
        ]);

        $this->createGameBuilding();

        $this->createKingdomBuilding([
            'game_building_id'   => GameBuilding::first()->id,
            'kingdom_id'         => Kingdom::first()->id,
            'level'              => 1,
            'current_defence'    => 300,
            'current_durability' => 0,
            'max_defence'        => 300,
            'max_durability'     => 300,
        ]);

        $response = $this->actingAs($this->character->getUser())->json('POST', route('kingdoms.building.rebuild', [
            'character'  => Character::first()->id,
            'building'   => KingdomBuilding::first()->id,
        ]))->response;

        $this->assertEquals(200, $response->status());

        $building = Kingdom::first()->buildings->first();

        $this->assertNotEquals(0, $building->current_durability);
        $this->assertEquals(300, $building->current_durability);
    }

    public function testCannotRebuildKingdomBuildingNotEnoughResources() {
        $this->createKingdom([
            'character_id'       => Character::first()->id,
            'game_map_id'        => GameMap::first()->id,
            'current_stone'      => 1,
            'current_wood'       => 1,
            'current_clay'       => 1,
            'current_iron'       => 1,
            'current_population' => 1,
        ]);

        $this->createGameBuilding();

        $this->createKingdomBuilding([
            'game_building_id'   => GameBuilding::first()->id,
            'kingdom_id'         => Kingdom::first()->id,
            'level'              => 1,
            'current_defence'    => 300,
            'current_durability' => 0,
            'max_defence'        => 300,
            'max_durability'     => 300,
        ]);

        $response = $this->actingAs($this->character->getUser())->json('POST', route('kingdoms.building.rebuild', [
            'character'  => Character::first()->id,
            'building'   => KingdomBuilding::first()->id,
        ]))->response;

        $this->assertEquals(422, $response->status());

        $content = json_decode($response->content());

        $this->assertEquals('You don\'t have the resources.', $content->message);

        $building = Kingdom::first()->buildings->first();

        $this->assertEquals(0, $building->current_durability);
    }

    public function testCanEmbezzle() {
        $this->createKingdom([
            'character_id' => Character::first()->id,
            'game_map_id'  => GameMap::first()->id,
            'treasury'     => 2000,
        ]);

        $response = $this->actingAs($this->character->getUser())->json('POST', route('kingdom.embezzle', [
            'kingdom' => Kingdom::first()->id
        ]), [
            'embezzle_amount' => 2000
        ])->response;

        $this->assertEquals(200, $response->status());

        $this->assertEquals(0, Kingdom::first()->treasury);
    }

    public function testCannotEmbezzleTooMuchGoldOnHand() {
        $this->createKingdom([
            'character_id' => Character::first()->id,
            'game_map_id'  => GameMap::first()->id,
            'treasury'     => 2000,
        ]);

        $this->character->updateCharacter([
            'gold' => MaxCurrenciesValue::MAX_GOLD - 1,
        ]);

        $response = $this->actingAs($this->character->getUser())->json('POST', route('kingdom.embezzle', [
            'kingdom' => Kingdom::first()->id
        ]), [
            'embezzle_amount' => 2000
        ])->response;


        $this->assertEquals(422, $response->status());

        $this->assertEquals(2000, Kingdom::first()->treasury);
    }

    public function testCannotEmbezzleFromAnotherKingdom() {
        $this->createKingdom([
            'character_id' => Character::first()->id,
            'game_map_id'  => GameMap::first()->id,
            'treasury'     => 2000,
        ]);

        $otherKingdom = $this->createKingdom([
            'character_id' => (new CharacterFactory())->createBaseCharacter()->getCharacter(false)->id,
            'game_map_id'  => GameMap::first()->id,
            'treasury'     => 2000,
        ]);

        $response = $this->actingAs($this->character->getUser())->json('POST', route('kingdom.embezzle', [
            'kingdom' => $otherKingdom->id
        ]), [
            'embezzle_amount' => 2000
        ])->response;

        $this->assertEquals(422, $response->status());
    }

    public function testCannotEmbezzleMissingParam() {
        $this->createKingdom([
            'character_id' => Character::first()->id,
            'game_map_id'  => GameMap::first()->id,
            'treasury'     => 2000,
        ]);

        $response = $this->actingAs($this->character->getUser())->json('POST', route('kingdom.embezzle', [
            'kingdom' => Kingdom::first()->id
        ]))->response;

        $this->assertEquals(422, $response->status());

        $content = json_decode($response->content());

        $this->assertEquals('Amount to embezzle is required.', $content->errors->embezzle_amount[0]);
    }

    public function testCannotEmbezzleHaveNoTreasury() {
        $this->createKingdom([
            'character_id' => Character::first()->id,
            'game_map_id'  => GameMap::first()->id,
            'treasury'     => 0,
        ]);

        $response = $this->actingAs($this->character->getUser())->json('POST', route('kingdom.embezzle', [
            'kingdom' => Kingdom::first()->id
        ]), [
            'embezzle_amount' => 2000
        ])->response;

        $this->assertEquals(422, $response->status());

        $content = json_decode($response->content());

        $this->assertEquals("You don't have the gold in your treasury.", $content->message);
    }

    public function testCannotEmbezzleMoraleTooLow() {
        $this->createKingdom([
            'character_id'   => Character::first()->id,
            'game_map_id'    => GameMap::first()->id,
            'current_morale' => 0.07,
            'treasury'       => 2000,
        ]);

        $response = $this->actingAs($this->character->getUser())->json('POST', route('kingdom.embezzle', [
            'kingdom' => Kingdom::first()->id
        ]), [
            'embezzle_amount' => 2000
        ])->response;

        $this->assertEquals(422, $response->status());

        $content = json_decode($response->content());

        $this->assertEquals('Morale is too low.', $content->message);
    }

    public function testUpgradeKingdomBuildingWhileOnline() {
        Queue::fake();

        $this->createKingdom([
            'character_id' => Character::first()->id,
            'game_map_id'  => GameMap::first()->id,
        ]);

        $gameBuilding = $this->createGameBuilding([
            'name'                 => 'Keep',
            'time_to_build'        => 0.0,
            'time_increase_amount' => 0.0,
        ]);

        DB::table('sessions')->insert([[
            'id'           => '1',
            'user_id'      => $this->character->getUser()->id,
            'ip_address'   => '1',
            'user_agent'   => '1',
            'payload'      => '1',
            'last_activity'=> 1602801731,
        ]]);

        $this->createKingdomBuilding([
            'game_building_id'   => $gameBuilding->id,
            'kingdom_id'         => Kingdom::first()->id,
            'level'              => 1,
            'current_defence'    => $gameBuilding->base_defence,
            'current_durability' => $gameBuilding->base_durability,
            'max_defence'        => $gameBuilding->base_defence,
            'max_durability'     => $gameBuilding->base_durability,
        ]);

        $response = $this->actingAs($this->character->getUser())->json('POST', route('kingdoms.building.upgrade', [
            'character' => Character::first()->id,
            'building'  => KingdomBuilding::first()->id,
        ]))->response;

        Queue::assertPushed(UpgradeBuilding::class);

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
    }

    public function testUpgradeKingdomBuildingWithEmail() {
        Queue::fake();

        $this->createKingdom([
            'character_id' => Character::first()->id,
            'game_map_id'  => GameMap::first()->id,
        ]);

        $gameBuilding = $this->createGameBuilding([
            'name'                 => 'Keep',
            'time_to_build'        => 0.0,
            'time_increase_amount' => 0.0,
        ]);

        $this->createKingdomBuilding([
            'game_building_id'   => $gameBuilding->id,
            'kingdom_id'         => Kingdom::first()->id,
            'level'              => 1,
            'current_defence'    => $gameBuilding->base_defence,
            'current_durability' => $gameBuilding->base_durability,
            'max_defence'        => $gameBuilding->base_defence,
            'max_durability'     => $gameBuilding->base_durability,
        ]);

        $response = $this->actingAs($this->character->getUser())->json('POST', route('kingdoms.building.upgrade', [
            'character' => Character::first()->id,
            'building'  => KingdomBuilding::first()->id,
        ]))->response;

        $content = json_decode($response->content());

        Queue::assertPushed(UpgradeBuilding::class);

        $this->assertEquals(200, $response->status());
    }

    public function testUpgradeKingdomBuildingThatIsResource() {
        Queue::fake();

        $this->createKingdom([
            'character_id' => Character::first()->id,
            'game_map_id'  => GameMap::first()->id,
        ]);

        $gameBuilding = $this->createGameBuilding([
            'name'                 => 'Keep',
            'time_to_build'        => 0.0,
            'time_increase_amount' => 0.0,
            'is_resource_building' => true,
        ]);

        $this->createKingdomBuilding([
            'game_building_id'   => $gameBuilding->id,
            'kingdom_id'         => Kingdom::first()->id,
            'level'              => 1,
            'current_defence'    => $gameBuilding->base_defence,
            'current_durability' => $gameBuilding->base_durability,
            'max_defence'        => $gameBuilding->base_defence,
            'max_durability'     => $gameBuilding->base_durability,
        ]);

        $response = $this->actingAs($this->character->getUser())->json('POST', route('kingdoms.building.upgrade', [
            'character' => Character::first()->id,
            'building'  => KingdomBuilding::first()->id,
        ]))->response;

        $content = json_decode($response->content());

        Queue::assertPushed(UpgradeBuilding::class);

        $this->assertEquals(200, $response->status());
    }

    public function testFailToUpgradeNotEnoughResources() {
        $this->createKingdom([
            'character_id'       => Character::first()->id,
            'game_map_id'        => GameMap::first()->id,
            'current_stone'      => 0,
            'current_wood'       => 0,
            'current_clay'       => 0,
            'current_iron'       => 0,
            'current_population' => 0,
        ]);

        $gameBuilding = $this->createGameBuilding([
            'name'                 => 'Keep',
            'time_to_build'        => 0.0,
            'time_increase_amount' => 0.0,
            'is_resource_building' => true,
        ]);

        $this->createKingdomBuilding([
            'game_building_id'   => $gameBuilding->id,
            'kingdom_id'         => Kingdom::first()->id,
            'level'              => 1,
            'current_defence'    => $gameBuilding->base_defence,
            'current_durability' => $gameBuilding->base_durability,
            'max_defence'        => $gameBuilding->base_defence,
            'max_durability'     => $gameBuilding->base_durability,
        ]);

        $response = $this->actingAs($this->character->getUser())->json('POST', route('kingdoms.building.upgrade', [
            'character' => Character::first()->id,
            'building'  => KingdomBuilding::first()->id,
        ]))->response;

        $content = json_decode($response->content());

        $this->assertEquals(422, $response->status());
        $this->assertEquals("You don't have the resources.", $content->message);
    }

    public function testFailToUpgradeMaxLevel() {
        $this->createKingdom([
            'character_id'       => Character::first()->id,
            'game_map_id'        => GameMap::first()->id,
        ]);

        $gameBuilding = $this->createGameBuilding([
            'name'                 => 'Keep',
            'time_to_build'        => 0.0,
            'time_increase_amount' => 0.0,
            'is_resource_building' => true,
            'max_level' => 1
        ]);

        $this->createKingdomBuilding([
            'game_building_id'   => $gameBuilding->id,
            'kingdom_id'         => Kingdom::first()->id,
            'level'              => 1,
            'current_defence'    => $gameBuilding->base_defence,
            'current_durability' => $gameBuilding->base_durability,
            'max_defence'        => $gameBuilding->base_defence,
            'max_durability'     => $gameBuilding->base_durability,
        ]);

        $response = $this->actingAs($this->character->getUser())->json('POST', route('kingdoms.building.upgrade', [
            'character' => Character::first()->id,
            'building'  => KingdomBuilding::first()->id,
        ]))->response;

        $content = json_decode($response->content());

        $this->assertEquals(422, $response->status());
        $this->assertEquals("Building is already max level.", $content->message);
    }

    public function testRecruitUnit() {
        Queue::fake();

        $kingdom = $this->createKingdom([
            'character_id'       => Character::first()->id,
            'game_map_id'        => GameMap::first()->id,
            'current_stone'      => 1000,
            'current_wood'       => 1000,
            'current_clay'       => 1000,
            'current_iron'       => 1000,
            'current_population' => 100,
        ]);

        $this->createGameUnit();

        $user = $this->character->getUser();

        $response = $this->actingAs($user)->json('POST', route('kingdoms.recruit.units', [
            'kingdom'  => Kingdom::first()->id,
            'gameUnit' => GameUnit::first()->id,
        ]), [
            'amount' => 5,
            'recruitment_type' => 'recruit-normally',
            'total_cost'       => 0
        ])->response;

        $this->assertEquals(200, $response->status());

        Queue::assertPushed(RecruitUnits::class);
    }

    public function testRecruitUnitWhenOnline() {
        Queue::fake();

        $kingdom = $this->createKingdom([
            'character_id'       => Character::first()->id,
            'game_map_id'        => GameMap::first()->id,
            'current_stone'      => 1000,
            'current_wood'       => 1000,
            'current_clay'       => 1000,
            'current_iron'       => 1000,
            'current_population' => 100,
        ]);

        $this->createGameUnit();

        $user = $this->character->getUser();

        $user->update([
            'show_unit_recruitment_messages' => true
        ]);

        $user = $user->refresh();

        DB::table('sessions')->insert([[
            'id'           => '1',
            'user_id'      => $user->id,
            'ip_address'   => '1',
            'user_agent'   => '1',
            'payload'      => '1',
            'last_activity'=> 1602801731,
        ]]);

        $response = $this->actingAs($user)->json('POST', route('kingdoms.recruit.units', [
            'kingdom'  => Kingdom::first()->id,
            'gameUnit' => GameUnit::first()->id,
        ]), [
            'amount' => 5,
            'recruitment_type' => 'recruit-normally',
            'total_cost'       => 0
        ])->response;

        $this->assertEquals(200, $response->status());

        Queue::assertPushed(RecruitUnits::class);
    }

    public function testRecruitAdditionalUnits() {
        Queue::fake();

        $kingdom = $this->createKingdom([
            'character_id'       => Character::first()->id,
            'game_map_id'        => GameMap::first()->id,
            'current_stone'      => 1000,
            'current_wood'       => 1000,
            'current_clay'       => 1000,
            'current_iron'       => 1000,
            'current_population' => 100,
        ]);

        $this->createGameUnit();

        $kingdom->units()->create([
            'game_unit_id' => GameUnit::first()->id,
            'kingdom_id'   => Kingdom::first()->id,
            'amount'       => 5,
        ]);

        $user = $this->character->getUser();

        $response = $this->actingAs($user)->json('POST', route('kingdoms.recruit.units', [
            'kingdom'  => Kingdom::first()->id,
            'gameUnit' => GameUnit::first()->id,
        ]), [
            'amount' => 5,
            'recruitment_type' => 'recruit-normally',
            'total_cost'       => 0
        ])->response;

        $this->assertEquals(200, $response->status());

        Queue::assertPushed(RecruitUnits::class);
    }

    public function testRecruitUnitsForKingdomWithUnits() {
        Queue::fake();

        $kingdom = $this->createKingdom([
            'character_id'       => Character::first()->id,
            'game_map_id'        => GameMap::first()->id,
            'current_stone'      => 1000,
            'current_wood'       => 1000,
            'current_clay'       => 1000,
            'current_iron'       => 1000,
            'current_population' => 100,
        ]);

        $this->createGameUnit();

        $kingdom->units()->create([
            'game_unit_id' => GameUnit::first()->id,
            'kingdom_id'   => Kingdom::first()->id,
            'amount'       => 5,
        ]);

        $user = $this->character->getUser();

        $response = $this->actingAs($user)->json('POST', route('kingdoms.recruit.units', [
            'kingdom'  => Kingdom::first()->id,
            'gameUnit' => $this->createGameUnit(['name' => 'Axemen']),
        ]), [
            'amount' => 5,
            'recruitment_type' => 'recruit-normally',
            'total_cost'       => 0
        ])->response;

        $this->assertEquals(200, $response->status());

        Queue::assertPushed(RecruitUnits::class);
    }

    public function testFailToRecruitUnitsWhenAmountIsZero() {
        $kingdom = $this->createKingdom([
            'character_id'       => Character::first()->id,
            'game_map_id'        => GameMap::first()->id,
            'current_stone'      => 1000,
            'current_wood'       => 1000,
            'current_clay'       => 1000,
            'current_iron'       => 1000,
            'current_population' => 100,
        ]);

        $this->createGameUnit();

        $user = $this->character->getUser();

        $response = $this->actingAs($user)->json('POST', route('kingdoms.recruit.units', [
            'kingdom'  => Kingdom::first()->id,
            'gameUnit' => GameUnit::first()->id,
        ]), [
            'amount' => 0,
            'recruitment_type' => 'recruit-normally',
            'total_cost'       => 0
        ])->response;

        $this->assertEquals(422, $response->status());

        $content = json_decode($response->content());

        $kingdom = $kingdom->refresh();

        $this->assertEquals('Too few units to recruit.', $content->message);
    }

    public function testFailToRecruitMoreThenMax() {
        $kingdom = $this->createKingdom([
            'character_id'       => Character::first()->id,
            'game_map_id'        => GameMap::first()->id,
            'current_stone'      => 1000,
            'current_wood'       => 1000,
            'current_clay'       => 1000,
            'current_iron'       => 1000,
            'current_population' => 100,
        ]);

        $this->createGameUnit();

        $user = $this->character->getUser();

        $response = $this->actingAs($user)->json('POST', route('kingdoms.recruit.units', [
            'kingdom'  => Kingdom::first()->id,
            'gameUnit' => GameUnit::first()->id,
        ]), [
            'amount' => KingdomMaxValue::MAX_UNIT + 1000,
            'recruitment_type' => 'recruit-normally',
            'total_cost'       => 0
        ])->response;

        $this->assertEquals(422, $response->status());

        $content = json_decode($response->content());

        $this->assertEquals('Too many units', $content->message);
    }

    public function testFailToRecruitAlreadyAtMax() {
        $kingdom = $this->createKingdom([
            'character_id'       => Character::first()->id,
            'game_map_id'        => GameMap::first()->id,
            'current_stone'      => 1000,
            'current_wood'       => 1000,
            'current_clay'       => 1000,
            'current_iron'       => 1000,
            'current_population' => 100,
        ]);

        $gameUnit = $this->createGameUnit();

        $kingdom->units()->create([
            'kingdom_id' => $kingdom->id,
            'game_unit_id' => $gameUnit->id,
            'amount' => KingdomMaxValue::MAX_UNIT,
        ]);

        $user = $this->character->getUser();

        $response = $this->actingAs($user)->json('POST', route('kingdoms.recruit.units', [
            'kingdom'  => Kingdom::first()->id,
            'gameUnit' => GameUnit::first()->id,
        ]), [
            'amount' => 10,
            'recruitment_type' => 'recruit-normally',
            'total_cost'       => 0
        ])->response;

        $this->assertEquals(422, $response->status());

        $content = json_decode($response->content());

        $this->assertEquals('Too many units', $content->message);
    }

    public function testNotEnoughResourcesToRecruit() {
        $kingdom = $this->createKingdom([
            'character_id'       => Character::first()->id,
            'game_map_id'        => GameMap::first()->id,
            'current_stone'      => 0,
            'current_wood'       => 0,
            'current_clay'       => 0,
            'current_iron'       => 0,
            'current_population' => 0,
        ]);

        $this->createGameUnit();

        $user = $this->character->getUser();

        $response = $this->actingAs($user)->json('POST', route('kingdoms.recruit.units', [
            'kingdom'  => Kingdom::first()->id,
            'gameUnit' => GameUnit::first()->id,
        ]), [
            'amount' => 150,
            'recruitment_type' => 'recruit-normally',
            'total_cost'       => 0
        ])->response;

        $this->assertEquals(422, $response->status());

        $content = json_decode($response->content());

        $kingdom = $kingdom->refresh();

        $this->assertEquals("You don't have the resources.", $content->message);
    }

    public function testCanCancelRecruitOrder() {
        $this->createGameUnit();

        $kingdom = $this->createKingdom([
            'character_id'       => Character::first()->id,
            'game_map_id'        => GameMap::first()->id,
            'current_stone'      => 0,
            'current_wood'       => 0,
            'current_clay'       => 0,
            'current_iron'       => 0,
            'current_population' => 0,
        ]);

        $this->createUnitQueue([
            'character_id' => Character::first()->id,
            'kingdom_id'   => $kingdom->id,
            'game_unit_id' => GameUnit::first()->id,
            'amount'       => 150,
            'completed_at' => now()->addMinutes(150)
        ]);

        $user = $this->character->getUser();

        $response = $this->actingAs($user)->json('POST', route('kingdoms.recruit.units.cancel'), [
            'queue_id' => UnitInQueue::first()->id
        ])->response;

        $this->assertEquals(200, $response->status());
        $this->assertTrue(UnitInQueue::all()->isEmpty());

        $kingdom = $kingdom->refresh();

        $this->assertTrue($kingdom->current_wood > 0);
        $this->assertTrue($kingdom->current_clay > 0);
        $this->assertTrue($kingdom->current_stone > 0);
        $this->assertTrue($kingdom->current_iron > 0);
    }

    public function testCannotCancelRecruitOrderForNonExistentQueue() {
        $user = $this->character->getUser();

        $response = $this->actingAs($user)->json('POST', route('kingdoms.recruit.units.cancel'), [
            'queue_id' => 1
        ])->response;

        $this->assertEquals(422, $response->status());

        $content = json_decode($response->content());

        $this->assertEquals('Invalid Input.', $content->message);
    }

    public function testCannotCancelRecruitOrderToLittleTimeLeft() {
        $this->createGameUnit();

        $kingdom = $this->createKingdom([
            'character_id'       => Character::first()->id,
            'game_map_id'        => GameMap::first()->id,
            'current_stone'      => 0,
            'current_wood'       => 0,
            'current_clay'       => 0,
            'current_iron'       => 0,
            'current_population' => 0,
        ]);

        $this->createUnitQueue([
            'character_id' => Character::first()->id,
            'kingdom_id'   => $kingdom->id,
            'game_unit_id' => GameUnit::first()->id,
            'amount'       => 150,
            'completed_at' => now()->subMinute(10)
        ]);

        $user = $this->character->getUser();

        $response = $this->actingAs($user)->json('POST', route('kingdoms.recruit.units.cancel'), [
            'queue_id' => UnitInQueue::first()->id
        ])->response;

        $this->assertEquals(422, $response->status());

        $content = json_decode($response->content());

        $this->assertEquals('Your units are almost done. You can\'t cancel this late in the process.', $content->message);
    }

    public function testCanCancelKingdomBuildingOrder() {
        $kingdom = $this->createKingdom([
            'character_id'       => Character::first()->id,
            'game_map_id'        => GameMap::first()->id,
            'current_stone'      => 0,
            'current_wood'       => 0,
            'current_clay'       => 0,
            'current_iron'       => 0,
            'current_population' => 0,
        ]);

        $this->createKingdomBuilding([
            'game_building_id'   => $this->createGameBuilding()->id,
            'kingdom_id'         => Kingdom::first()->id,
            'level'              => 1,
            'current_defence'    => 100,
            'current_durability' => 100,
            'max_defence'        => 100,
            'max_durability'     => 100,
        ]);

        $this->createKingdomBuildingQueue([
            'character_id' => Character::first()->id,
            'kingdom_id'   => $kingdom->id,
            'building_id'  => KingdomBuilding::first()->id,
            'to_level'     => 2,
            'completed_at' => now()->addMinutes(150)
        ]);

        $user = $this->character->getUser();

        $kingdomBuildingService = Mockery::mock(KingdomBuildingService::class)->makePartial();

        $this->app->instance(KingdomBuildingService::class, $kingdomBuildingService);

        $kingdomBuildingService->shouldReceive('cancelKingdomBuildingUpgrade')->andReturn(true);

        $response = $this->actingAs($user)->json('POST', route('kingdoms.building.queue.delete'), [
            'queue_id' => BuildingInQueue::first()->id
        ])->response;

        $this->assertEquals(200, $response->status());
    }

    public function testCannotCancelKingdomBuildingOrderForNonExistantQueue() {
        $user = $this->character->getUser();

        $response = $this->actingAs($user)->json('POST', route('kingdoms.building.queue.delete'), [
            'queue_id' => 8747654
        ])->response;

        $this->assertEquals(422, $response->status());

        $content = json_decode($response->content());

        $this->assertEquals('Invalid Input.', $content->message);
    }

    public function testCannotCancelKingdomBuildingOrderToLittleTimeLeft() {
        $kingdom = $this->createKingdom([
            'character_id'       => Character::first()->id,
            'game_map_id'        => GameMap::first()->id,
            'current_stone'      => 0,
            'current_wood'       => 0,
            'current_clay'       => 0,
            'current_iron'       => 0,
            'current_population' => 0,
        ]);

        $this->createKingdomBuilding([
            'game_building_id'   => $this->createGameBuilding()->id,
            'kingdom_id'         => Kingdom::first()->id,
            'level'              => 1,
            'current_defence'    => 100,
            'current_durability' => 100,
            'max_defence'        => 100,
            'max_durability'     => 100,
        ]);

        $this->createKingdomBuildingQueue([
            'character_id' => Character::first()->id,
            'kingdom_id'   => $kingdom->id,
            'building_id'  => KingdomBuilding::first()->id,
            'to_level'     => 2,
            'completed_at' => now()->subDays(100),
            'started_at'   => now()
        ]);

        $user = $this->character->getUser();

        $kingdomBuildingService = Mockery::mock(KingdomBuildingService::class)->makePartial();

        $this->app->instance(KingdomBuildingService::class, $kingdomBuildingService);

        $kingdomBuildingService->shouldReceive('cancelKingdomBuildingUpgrade')->andReturn(false);

        $response = $this->actingAs($user)->json('POST', route('kingdoms.building.queue.delete'), [
            'queue_id' => BuildingInQueue::first()->id,
        ])->response;

        $this->assertEquals(422, $response->status());

        $content = json_decode($response->content());

        $this->assertEquals('Your workers are almost done. You can\'t cancel this late in the process.', $content->message);
    }

    public function testCannotDepositIntoAnotherKingdom() {
        $this->createKingdom([
            'character_id' => Character::first()->id,
            'game_map_id'  => GameMap::first()->id,
            'treasury'     => 2000,
        ]);

        $otherKingdom = $this->createKingdom([
            'character_id' => (new CharacterFactory())->createBaseCharacter()->getCharacter(false)->id,
            'game_map_id'  => GameMap::first()->id,
            'treasury'     => 2000,
        ]);

        $response = $this->actingAs($this->character->getUser())->json('POST', route('kingdom.deposit', [
            'kingdom' => $otherKingdom->id
        ]), [
            'deposit_amount' => 2000
        ])->response;

        $this->assertEquals(422, $response->status());
    }
}
