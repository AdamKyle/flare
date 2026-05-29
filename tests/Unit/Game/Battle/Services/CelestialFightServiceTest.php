<?php

namespace Tests\Unit\Game\Battle\Services;

use App\Flare\Models\CelestialFight;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterInCelestialFight;
use App\Flare\Models\GameMap;
use App\Flare\Models\Monster;
use App\Flare\ServerFight\MonsterPlayerFight;
use App\Flare\Values\AttackTypeValue;
use App\Flare\Values\MapNameValue;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Battle\Services\CelestialFightService;
use App\Game\Battle\Values\CelestialConjureType;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use App\Game\Maps\Values\MapTileValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\Monster\MonsterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCelestials;
use Tests\Traits\CreateGameMap;

class CelestialFightServiceTest extends TestCase
{
    use CreateCelestials;
    use CreateGameMap;
    use MockeryPHPUnitIntegration;
    use RefreshDatabase;

    private ?CharacterFactory $characterFactory = null;

    private ?Character $character = null;

    private ?GameMap $gameMap = null;

    private ?Monster $monster = null;

    public function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        config(['broadcasting.default' => 'log']);

        $this->gameMap = $this->createGameMap([
            'name' => MapNameValue::SHADOW_PLANE,
            'path' => 'shadow-plane',
            'default' => false,
            'enemy_stat_bonus' => 9.9999,
        ]);

        $this->characterFactory = $this->createCharacterFactoryWithWeaponDamage(1000);
        $this->character = $this->characterFactory->getCharacter()->refresh();

        $this->monster = (new MonsterFactory())
            ->buildMonster()
            ->updateMonster([
                'game_map_id' => $this->gameMap->id,
                'name' => 'Maze Warden',
                'is_celestial_entity' => true,
                'health_range' => '56000-56000',
                'attack_range' => '0-0',
                'str' => 1,
                'dur' => 1,
                'dex' => 1,
                'chr' => 1,
                'int' => 1,
                'agi' => 1,
                'focus' => 1,
                'ac' => 0,
                'max_affix_damage' => 1,
                'accuracy' => 0.0,
                'casting_accuracy' => 0.0,
                'dodge' => 0.0,
                'criticality' => 0.0,
                'ambush_chance' => 0.0,
                'ambush_resistance' => 1.0,
                'counter_chance' => 0.0,
                'counter_resistance' => 0.0,
                'entrancing_chance' => 0.0,
                'devouring_light_chance' => 0.0,
                'devouring_darkness_chance' => 0.0,
            ])
            ->getMonster();
    }

    public function tearDown(): void
    {
        $this->characterFactory = null;
        $this->character = null;
        $this->gameMap = null;
        $this->monster = null;

        parent::tearDown();
    }

    public function testItDoesNotAllowCelestialCurrentHealthToExceedCelestialMaxHealthAfterAttack(): void
    {
        $celestialFight = $this->createCelestialFightForTest();
        $characterInCelestialFight = $this->createCharacterInCelestialFightForCharacter($this->character, $celestialFight);

        Cache::forget('celestials');
        Cache::forget('monster-fight-' . $this->character->id);

        Event::fake();

        $response = resolve(CelestialFightService::class)->fight(
            $this->character,
            $celestialFight,
            $characterInCelestialFight,
            AttackTypeValue::ATTACK,
        );

        $this->assertSame(200, $response['status']);
        $this->assertLessThanOrEqual(
            $celestialFight->max_health,
            $response['health']['current_monster_health']
        );
    }

    public function testCharacterDoesNotKillCelestialButAnotherCharacterDoesKillCelestial(): void
    {
        $celestialFight = $this->createCelestialFightForTest();
        $characterInCelestialFight = $this->createCharacterInCelestialFightForCharacter($this->character, $celestialFight);

        Cache::forget('celestials');
        Cache::forget('monster-fight-' . $this->character->id);

        Event::fake();

        $firstResponse = resolve(CelestialFightService::class)->fight(
            $this->character,
            $celestialFight,
            $characterInCelestialFight,
            AttackTypeValue::ATTACK,
        );

        $celestialFight = $celestialFight->refresh();

        $this->assertSame(200, $firstResponse['status']);
        $this->assertSame($celestialFight->max_health, $firstResponse['health']['current_monster_health']);
        $this->assertNotNull(CelestialFight::find($celestialFight->id));

        $secondCharacterFactory = $this->createCharacterFactoryWithWeaponDamage(
            1000000,
            $celestialFight->x_position,
            $celestialFight->y_position
        );

        $secondCharacter = $secondCharacterFactory->getCharacter()->refresh();
        $secondCharacterInCelestialFight = $this->createCharacterInCelestialFightForCharacter($secondCharacter, $celestialFight);

        Cache::forget('monster-fight-' . $secondCharacter->id);

        $celestialFightService = $this->mockCelestialFightServiceForMonsterDeath();

        $secondResponse = $celestialFightService->fight(
            $secondCharacter,
            $celestialFight,
            $secondCharacterInCelestialFight,
            AttackTypeValue::ATTACK,
        );

        $this->assertSame(200, $secondResponse['status']);
        $this->assertSame(0, $secondResponse['health']['current_monster_health']);
    }

    public function testCharacterKillsCelestial(): void
    {
        $this->characterFactory->attackDataManagement()
            ->setUpDeterministicAttackData()
            ->setWeaponDamage(1000000);

        $this->character = $this->characterFactory->getCharacter()->refresh();

        $celestialFight = $this->createCelestialFightForTest();
        $characterInCelestialFight = $this->createCharacterInCelestialFightForCharacter($this->character, $celestialFight);

        Cache::forget('celestials');
        Cache::forget('monster-fight-' . $this->character->id);

        Event::fake();

        $celestialFightService = $this->mockCelestialFightServiceForMonsterDeath();

        $response = $celestialFightService->fight(
            $this->character,
            $celestialFight,
            $characterInCelestialFight,
            AttackTypeValue::ATTACK,
        );

        $this->assertSame(200, $response['status']);
        $this->assertSame(0, $response['health']['current_monster_health']);
    }

    private function createCharacterFactoryWithWeaponDamage(int $weaponDamage, int $xPosition = 16, int $yPosition = 16): CharacterFactory
    {
        $characterFactory = (new CharacterFactory())
            ->createBaseCharacter()
            ->givePlayerLocation($xPosition, $yPosition, $this->gameMap)
            ->updateSkill('Accuracy', ['skill_bonus' => 1.0])
            ->updateSkill('Criticality', ['skill_bonus' => 0.0])
            ->cacheCharacterSheet([
                'health' => 1000000000,
                'ac' => 1000000000,
                'skills' => [
                    'accuracy' => 1.0,
                    'criticality' => 0.0,
                ],
            ]);

        $characterFactory->attackDataManagement()
            ->setUpDeterministicAttackData()
            ->setWeaponDamage($weaponDamage);

        return $characterFactory;
    }

    private function createCelestialFightForTest(): CelestialFight
    {
        return $this->createCelestialFight([
            'monster_id' => $this->monster->id,
            'character_id' => null,
            'conjured_at' => now(),
            'x_position' => 16,
            'y_position' => 16,
            'damaged_kingdom' => false,
            'stole_treasury' => false,
            'weakened_morale' => false,
            'current_health' => 56000,
            'max_health' => 56000,
            'type' => CelestialConjureType::PUBLIC,
        ]);
    }

    private function createCharacterInCelestialFightForCharacter(Character $character, CelestialFight $celestialFight): CharacterInCelestialFight
    {
        return $this->createCharacterInCelestialFight([
            'character_id' => $character->id,
            'celestial_fight_id' => $celestialFight->id,
            'character_max_health' => 1000000000,
            'character_current_health' => 1000000000,
        ]);
    }

    private function mockCelestialFightServiceForMonsterDeath(): CelestialFightService
    {
        return Mockery::mock(CelestialFightService::class, [
            resolve(BattleEventHandler::class),
            resolve(CharacterCacheData::class),
            resolve(MonsterPlayerFight::class),
            resolve(MapTileValue::class),
        ])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('handleMonsterDeath')
            ->once()
            ->andReturnNull()
            ->getMock();
    }
}