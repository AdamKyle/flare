<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Services;

use App\Flare\Values\LocationType;
use App\Game\BattleRewardProcessing\Services\WeeklyBattleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateWeeklyMonsterFight;

class WeeklyBattleServiceTest extends TestCase {

    use RefreshDatabase, CreateMonster, CreateWeeklyMonsterFight;

    private ?WeeklyBattleService $weeklyBattleService;

    private ?CharacterFactory $characterFactory;

    public function setUp(): void {
        parent::setUp();

        $this->weeklyBattleService = resolve(WeeklyBattleService::class);

        $this->characterFactory    = (new CharacterFactory())->createBaseCharacter();
    }

    public function tearDown(): void {

        parent::tearDown();

        $this->weeklyBattleService = null;

        $this->characterFactory = null;
    }

    public function testCreateRecordForCharacterDeath() {

        $character = $this->characterFactory->getCharacter();

        $monster = $this->createMonster([
            'only_for_location_type' => LocationType::ALCHEMY_CHURCH,
        ]);

        $this->weeklyBattleService->handleCharacterDeath($character, $monster);

        $weeklyBattleFight = $character->refresh()->weeklyBattleFights->first();

        $this->assertNotNull($weeklyBattleFight);

        $this->assertEquals(1, $weeklyBattleFight->character_deaths);
    }

    public function testDoesNotCreateRecordForCharacterDeathWhenMonsterLocationTypeIsInvalid() {

        $character = $this->characterFactory->getCharacter();

        $monster = $this->createMonster([
            'only_for_location_type' => null,
        ]);

        $this->weeklyBattleService->handleCharacterDeath($character, $monster);

        $weeklyBattleFight = $character->refresh()->weeklyBattleFights->first();

        $this->assertNull($weeklyBattleFight);
    }

    public function testUpdateRecordForCharacterDeath() {

        $character = $this->characterFactory->getCharacter();

        $monster = $this->createMonster([
            'only_for_location_type' => LocationType::ALCHEMY_CHURCH,
        ]);

        $weeklyFight = $this->createWeeklyMonsterFight([
            'character_id' => $character->id,
            'monster_id'   => $monster->id,
            'character_deaths' => 10,
        ]);

        $this->weeklyBattleService->handleCharacterDeath($character, $monster);

        $weeklyBattleFight = $weeklyFight->refresh();

        $this->assertEquals(11, $weeklyBattleFight->character_deaths);
    }

    public function testDoesNotUpdateRecordForCharacterDeathWhenMonsterLocationTypeIsInvalid() {

        $character = $this->characterFactory->getCharacter();

        $monster = $this->createMonster([
            'only_for_location_type' => null
        ]);

        $weeklyFight = $this->createWeeklyMonsterFight([
            'character_id' => $character->id,
            'monster_id'   => $monster->id,
            'character_deaths' => 10,
        ]);

        $this->weeklyBattleService->handleCharacterDeath($character, $monster);

        $weeklyBattleFight = $weeklyFight->refresh();

        $this->assertEquals(10, $weeklyBattleFight->character_deaths);
    }

    public function testMarkMonsterAsKilled() {

        $character = $this->characterFactory->getCharacter();

        $monster = $this->createMonster([
            'only_for_location_type' => LocationType::ALCHEMY_CHURCH,
        ]);

        $weeklyFight = $this->createWeeklyMonsterFight([
            'character_id' => $character->id,
            'monster_id'   => $monster->id,
            'character_deaths' => 10,
        ]);

        $this->weeklyBattleService->handleMonsterDeath($character, $monster);

        $weeklyBattleFight = $weeklyFight->refresh();

        $this->assertTrue($weeklyBattleFight->monster_was_killed);
    }

    public function testCreateRecordMonsterWasKilled() {

        $character = $this->characterFactory->getCharacter();

        $monster = $this->createMonster([
            'only_for_location_type' => LocationType::ALCHEMY_CHURCH,
        ]);

        $this->weeklyBattleService->handleMonsterDeath($character, $monster);

        $weeklyBattleFight = $character->refresh()->weeklyBattleFights->first();

        $this->assertNotNull($weeklyBattleFight);

        $this->assertTrue($weeklyBattleFight->monster_was_killed);
    }

    public function testDoNotCreateRecordMonsterWasKilledForInvalidLocationType() {

        $character = $this->characterFactory->getCharacter();

        $monster = $this->createMonster([
            'only_for_location_type' => null,
        ]);

        $this->weeklyBattleService->handleMonsterDeath($character, $monster);

        $weeklyBattleFight = $character->refresh()->weeklyBattleFights->first();

        $this->assertNull($weeklyBattleFight);
    }

    public function testCanFightMonster() {
        $character = $this->characterFactory->getCharacter();
        $monster = $this->createMonster();

        $this->assertTrue($this->weeklyBattleService->canFightMonster($character, $monster));
    }

    public function testCannotFightMonster() {
        $character = $this->characterFactory->getCharacter();
        $monster = $this->createMonster([
            'only_for_location_type' => LocationType::ALCHEMY_CHURCH,
        ]);

        $this->createWeeklyMonsterFight([
            'character_id' => $character->id,
            'monster_id'   => $monster->id,
            'character_deaths' => 10,
            'monster_was_killed' => true,
        ]);

        $this->assertFalse($this->weeklyBattleService->canFightMonster($character, $monster));
    }
}
