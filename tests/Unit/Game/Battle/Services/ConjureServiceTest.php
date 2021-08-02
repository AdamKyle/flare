<?php

namespace Tests\Unit\Game\Battle\Services;

use App\Flare\Models\CelestialFight;
use App\Flare\Models\Kingdom;
use App\Flare\Transformers\CharacterSheetTransformer;
use App\Flare\Transformers\KingdomTransformer;
use App\Flare\Values\NpcTypes;
use App\Game\Battle\Services\ConjureService;
use App\Game\Battle\Values\CelestialConjureType;
use App\Game\Messages\Builders\NpcServerMessageBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use League\Fractal\Manager;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateCelestials;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateNpc;

class ConjureServiceTest extends TestCase
{
    use RefreshDatabase;

    use RefreshDatabase, CreateMonster, CreateNpc, CreateCelestials;

    private $character = null;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->updateCharacter([
            'gold' => 999999999,
            'gold_dust' => 999999999,
        ]);
    }

    public function testCanConjurePrivateCelestial() {
        $conjureService = resolve(ConjureService::class);

        $this->createNpc([
            'type' => NpcTypes::SUMMONER
        ]);

        $monster = $this->createMonster([
            'is_celestial_entity' => true,
            'gold_cost'           => 1000,
            'gold_dust_cost'      => 1000,
        ]);

        $character = $this->character->getCharacter();

        $conjureService->conjure($monster, $character, 'private');

        $this->assertTrue(CelestialFight::where('type', CelestialConjureType::PRIVATE)->get()->isNotEmpty());
    }

    public function testCanConjureCelestialAndItDoesDoesDamageToKingdom() {
        $this->createNpc([
            'type' => NpcTypes::SUMMONER
        ]);

        $monster = $this->createMonster([
            'is_celestial_entity' => true,
            'gold_cost'           => 1000,
            'gold_dust_cost'      => 1000,
        ]);


        $character = $this->character->kingdomManagement()->assignKingdom([
            'x_position' => 16,
            'y_position' => 16,
            'current_morale' => 100,
        ])->assignBuilding()->assignUnits()->getCharacter();

        $conjure = \Mockery::mock(ConjureService::class, [
            resolve(Manager::class),
            resolve(KingdomTransformer::class),
            resolve(CharacterSheetTransformer::class),
            resolve(NpcServerMessageBuilder::class),
        ])->makePartial();

        $this->app->instance(ConjureService::class, $conjure);

        $conjure->shouldReceive('canDamageKingdom')->once()->andReturn(true);
        $conjure->shouldReceive('getXPosition')->once()->andReturn(16);
        $conjure->shouldReceive('getYPosition')->once()->andReturn(16);

        $conjureService = resolve(ConjureService::class);

        $conjureService->conjure($monster, $character, 'private');

        $this->assertTrue(CelestialFight::where('type', CelestialConjureType::PRIVATE)->get()->isNotEmpty());

        $this->assertTrue($character->refresh()->kingdoms->first()->current_morale < 100);
    }

    public function testCanConjureCelestialAndItDoesDoesDamageToKingdomButValuesDontFallBelowZero() {
        $this->createNpc([
            'type' => NpcTypes::SUMMONER
        ]);

        $monster = $this->createMonster([
            'is_celestial_entity' => true,
            'gold_cost'           => 1000,
            'gold_dust_cost'      => 1000,
        ]);


        $character = $this->character->kingdomManagement()->assignKingdom([
            'x_position' => 16,
            'y_position' => 16,
            'current_morale' => 0.01,
        ])->assignBuilding([], [
            'current_durability' => 1
        ])->assignUnits([], 1)->getCharacter();

        $conjure = \Mockery::mock(ConjureService::class, [
            resolve(Manager::class),
            resolve(KingdomTransformer::class),
            resolve(CharacterSheetTransformer::class),
            resolve(NpcServerMessageBuilder::class),
        ])->makePartial();

        $this->app->instance(ConjureService::class, $conjure);

        $conjure->shouldReceive('canDamageKingdom')->once()->andReturn(true);
        $conjure->shouldReceive('getXPosition')->once()->andReturn(16);
        $conjure->shouldReceive('getYPosition')->once()->andReturn(16);

        $conjureService = resolve(ConjureService::class);

        $conjureService->conjure($monster, $character, 'private');

        $this->assertTrue(CelestialFight::where('type', CelestialConjureType::PRIVATE)->get()->isNotEmpty());

        $this->assertTrue($character->refresh()->kingdoms->first()->current_morale === 0.0);
    }

    public function testCanConjureCelestialAndItDoesDoesDamageToKingdomButValuesDontFallBelowZeroForNpc() {
        $this->createNpc([
            'type' => NpcTypes::SUMMONER
        ]);

        $this->createNpc([
            'name'      => 'KingdomHolder',
            'real_name' => 'Kingdom Holder',
            'type'      => NpcTypes::KINGDOM_HOLDER
        ]);

        $monster = $this->createMonster([
            'is_celestial_entity' => true,
            'gold_cost'           => 1000,
            'gold_dust_cost'      => 1000,
        ]);

        $character = $this->character->kingdomManagement()->assignKingdom([
            'x_position' => 16,
            'y_position' => 16,
            'current_morale' => 0.01,
        ])->assignBuilding([], [
            'current_durability' => 1
        ])->assignUnits([], 1)->getCharacter();

        $character->kingdoms()->first()->update([
            'character_id' => null,
            'npc_owned'    => true,
        ]);

        $character = $character->refresh();

        $conjure = \Mockery::mock(ConjureService::class, [
            resolve(Manager::class),
            resolve(KingdomTransformer::class),
            resolve(CharacterSheetTransformer::class),
            resolve(NpcServerMessageBuilder::class),
        ])->makePartial();

        $this->app->instance(ConjureService::class, $conjure);

        $conjure->shouldReceive('canDamageKingdom')->once()->andReturn(true);
        $conjure->shouldReceive('getXPosition')->once()->andReturn(16);
        $conjure->shouldReceive('getYPosition')->once()->andReturn(16);

        $conjureService = resolve(ConjureService::class);

        $conjureService->conjure($monster, $character, 'private');

        $this->assertTrue(CelestialFight::where('type', CelestialConjureType::PRIVATE)->get()->isNotEmpty());

        $this->assertTrue(Kingdom::first()->current_morale === 0.0);
    }

    public function testCanPossiblyDamageKingdom() {
        $this->createNpc([
            'type' => NpcTypes::SUMMONER
        ]);

        $monster = $this->createMonster([
            'is_celestial_entity' => true,
            'gold_cost'           => 1000,
            'gold_dust_cost'      => 1000,
        ]);

        $character = $this->character->kingdomManagement()->assignKingdom([
            'x_position' => 16,
            'y_position' => 16,
            'current_morale' => 0.01,
        ])->assignBuilding([], [
            'current_durability' => 1
        ])->assignUnits([], 1)->getCharacter();

        $conjure = \Mockery::mock(ConjureService::class, [
            resolve(Manager::class),
            resolve(KingdomTransformer::class),
            resolve(CharacterSheetTransformer::class),
            resolve(NpcServerMessageBuilder::class),
        ])->makePartial();

        $this->app->instance(ConjureService::class, $conjure);

        $conjure->shouldReceive('getXPosition')->once()->andReturn(16);
        $conjure->shouldReceive('getYPosition')->once()->andReturn(16);

        $conjureService = resolve(ConjureService::class);

        $conjureService->conjure($monster, $character, 'public');

        $this->assertTrue(CelestialFight::where('type', CelestialConjureType::PUBLIC)->get()->isNotEmpty());
    }

    public function testMovementConjuresCelestialEntity() {
        $conjureService = resolve(ConjureService::class);

        $this->createNpc([
            'type' => NpcTypes::SUMMONER
        ]);

        $this->createMonster([
            'is_celestial_entity' => true,
            'gold_cost'           => 1000,
            'gold_dust_cost'      => 1000,
        ]);

        $character = $this->character->getCharacter();

        $conjureService->movementConjure($character);

        $this->assertTrue(CelestialFight::where('type', CelestialConjureType::PUBLIC)->get()->isNotEmpty());
    }

    public function testMovementConjuresCelestialEntityThatDamagesKingdom() {
        $this->createNpc([
            'type' => NpcTypes::SUMMONER
        ]);

        $this->createMonster([
            'is_celestial_entity' => true,
            'gold_cost'           => 1000,
            'gold_dust_cost'      => 1000,
        ]);

        $character = $this->character->kingdomManagement()->assignKingdom([
            'x_position' => 16,
            'y_position' => 16,
            'current_morale' => 100,
        ])->assignBuilding()->assignUnits()->getCharacter();

        $conjure = \Mockery::mock(ConjureService::class, [
            resolve(Manager::class),
            resolve(KingdomTransformer::class),
            resolve(CharacterSheetTransformer::class),
            resolve(NpcServerMessageBuilder::class),
        ])->makePartial();

        $this->app->instance(ConjureService::class, $conjure);

        $conjure->shouldReceive('canDamageKingdom')->once()->andReturn(true);
        $conjure->shouldReceive('getXPosition')->once()->andReturn(16);
        $conjure->shouldReceive('getYPosition')->once()->andReturn(16);

        $conjureService = resolve(ConjureService::class);

        $conjureService->movementConjure($character);

        $this->assertTrue(CelestialFight::where('type', CelestialConjureType::PUBLIC)->get()->isNotEmpty());
        $this->assertTrue($character->refresh()->kingdoms->first()->current_morale < 100);
    }

    public function testMovementDoesNotConjuresCelestialEntity() {
        $this->createNpc([
            'type' => NpcTypes::SUMMONER
        ]);

        $monster = $this->createMonster([
            'is_celestial_entity' => true,
            'gold_cost'           => 1000,
            'gold_dust_cost'      => 1000,
        ]);

        $this->createCelestialFight([
            'monster_id'      => $monster->id,
            'character_id'    => null,
            'conjured_at'     => now(),
            'x_position'      => 126,
            'y_position'      => 176,
            'current_health'  => 100,
            'max_health'      => 100,
            'damaged_kingdom' => false,
            'stole_treasury'  => false,
            'weakened_morale' => false,
            'type'            => CelestialConjureType::PUBLIC,
        ]);

        $conjureService = resolve(ConjureService::class);

        $character = $this->character->getCharacter();

        $conjureService->movementConjure($character);

        $this->assertCount(1, CelestialFight::all());
    }
}
