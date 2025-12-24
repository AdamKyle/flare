<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Services;

use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Values\LocationType;
use App\Game\BattleRewardProcessing\Services\WeeklyBattleService;
use Facades\App\Flare\Calculators\DropCheckCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateWeeklyMonsterFight;

class WeeklyBattleServiceTest extends TestCase
{
    use CreateItem, CreateMonster, CreateWeeklyMonsterFight, RefreshDatabase;

    private ?WeeklyBattleService $weeklyBattleService;

    private ?CharacterFactory $characterFactory;

    public function setUp(): void
    {
        parent::setUp();

        $this->weeklyBattleService = resolve(WeeklyBattleService::class);

        $this->characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        $this->createItem([
            'type' => 'weapon',
            'specialty_type' => ItemSpecialtyType::HELL_FORGED,
        ]);

        $this->createItem([
            'type' => 'weapon',
            'specialty_type' => ItemSpecialtyType::TWISTED_EARTH,
        ]);

        $this->createItem([
            'type' => 'weapon',
            'specialty_type' => ItemSpecialtyType::DELUSIONAL_SILVER,
        ]);

        $this->createItem([
            'type' => 'weapon',
            'specialty_type' => ItemSpecialtyType::FAITHLESS_PLATE,
        ]);

        $this->createItem([
            'type' => 'weapon',
            'specialty_type' => null,
        ]);
    }

    public function tearDown(): void
    {

        parent::tearDown();

        $this->weeklyBattleService = null;

        $this->characterFactory = null;
    }

    public function testCreateRecordForCharacterDeath()
    {

        $character = $this->characterFactory->getCharacter();

        $monster = $this->createMonster([
            'only_for_location_type' => LocationType::ALCHEMY_CHURCH,
        ]);

        $this->weeklyBattleService->handleCharacterDeath($character, $monster);

        $weeklyBattleFight = $character->refresh()->weeklyBattleFights->first();

        $this->assertNotNull($weeklyBattleFight);

        $this->assertEquals(1, $weeklyBattleFight->character_deaths);
    }

    public function testDoesNotCreateRecordForCharacterDeathWhenMonsterLocationTypeIsInvalid()
    {

        $character = $this->characterFactory->getCharacter();

        $monster = $this->createMonster([
            'only_for_location_type' => null,
        ]);

        $this->weeklyBattleService->handleCharacterDeath($character, $monster);

        $weeklyBattleFight = $character->refresh()->weeklyBattleFights->first();

        $this->assertNull($weeklyBattleFight);
    }

    public function testUpdateRecordForCharacterDeath()
    {

        $character = $this->characterFactory->getCharacter();

        $monster = $this->createMonster([
            'only_for_location_type' => LocationType::ALCHEMY_CHURCH,
        ]);

        $weeklyFight = $this->createWeeklyMonsterFight([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'character_deaths' => 10,
        ]);

        $this->weeklyBattleService->handleCharacterDeath($character, $monster);

        $weeklyBattleFight = $weeklyFight->refresh();

        $this->assertEquals(11, $weeklyBattleFight->character_deaths);
    }

    public function testDoesNotUpdateRecordForCharacterDeathWhenMonsterLocationTypeIsInvalid()
    {

        $character = $this->characterFactory->getCharacter();

        $monster = $this->createMonster([
            'only_for_location_type' => null,
        ]);

        $weeklyFight = $this->createWeeklyMonsterFight([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'character_deaths' => 10,
        ]);

        $this->weeklyBattleService->handleCharacterDeath($character, $monster);

        $weeklyBattleFight = $weeklyFight->refresh();

        $this->assertEquals(10, $weeklyBattleFight->character_deaths);
    }

    public function testMarkMonsterAsKilled()
    {

        $character = $this->characterFactory->getCharacter();



        $monster = $this->createMonster([
            'only_for_location_type' => LocationType::ALCHEMY_CHURCH,
        ]);

        $weeklyFight = $this->createWeeklyMonsterFight([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'character_deaths' => 10,
        ]);

        $this->weeklyBattleService->handleMonsterDeath($character, $monster);

        $weeklyBattleFight = $weeklyFight->refresh();

        $this->assertTrue($weeklyBattleFight->monster_was_killed);
    }

    public function testCallLocationSpecialityHandler()
    {

        $character = $this->characterFactory->getCharacter();

        $monster = $this->createMonster([
            'only_for_location_type' => LocationType::TWSITED_MAIDENS_DUNGEONS,
        ]);

        $weeklyFight = $this->createWeeklyMonsterFight([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'character_deaths' => 10,
        ]);

        $this->weeklyBattleService->handleMonsterDeath($character, $monster);

        $weeklyBattleFight = $weeklyFight->refresh();

        $this->assertTrue($weeklyBattleFight->monster_was_killed);
    }

    public function testCreateRecordMonsterWasKilled()
    {

        DropCheckCalculator::partialMock()->shouldReceive('fetchDifficultItemChance')->andReturn(true);

        $character = $this->characterFactory->getCharacter();

        $this->createItem([
            'type' => 'weapon',
            'specialty_type' => ItemSpecialtyType::DELUSIONAL_SILVER,
        ]);

        $monster = $this->createMonster([
            'only_for_location_type' => LocationType::ALCHEMY_CHURCH,
        ]);

        $this->weeklyBattleService->handleMonsterDeath($character, $monster);

        $character = $character->refresh();

        $weeklyBattleFight = $character->weeklyBattleFights->first();

        $this->assertNotNull($weeklyBattleFight);

        $this->assertTrue($weeklyBattleFight->monster_was_killed);

        $this->assertNotNull($character->inventory->slots->filter(function ($slot) {
            return $slot->item->is_cosmic;
        })->first());
    }

    public function testDoNotCreateRecordMonsterWasKilledForInvalidLocationType()
    {

        $character = $this->characterFactory->getCharacter();

        $monster = $this->createMonster([
            'only_for_location_type' => null,
        ]);

        $this->weeklyBattleService->handleMonsterDeath($character, $monster);

        $character = $character->refresh();

        $weeklyBattleFight = $character->weeklyBattleFights->first();

        $this->assertNull($weeklyBattleFight);
    }

    public function testCanFightMonster()
    {
        $character = $this->characterFactory->getCharacter();
        $monster = $this->createMonster();

        $this->assertTrue($this->weeklyBattleService->canFightMonster($character, $monster));
    }

    public function testCannotFightMonster()
    {
        $character = $this->characterFactory->getCharacter();
        $monster = $this->createMonster([
            'only_for_location_type' => LocationType::ALCHEMY_CHURCH,
        ]);

        $this->createWeeklyMonsterFight([
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'character_deaths' => 10,
            'monster_was_killed' => true,
        ]);

        $this->assertFalse($this->weeklyBattleService->canFightMonster($character, $monster));
    }
}
