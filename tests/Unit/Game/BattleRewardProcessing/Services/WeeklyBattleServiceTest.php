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

    protected function setUp(): void
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

    protected function tearDown(): void
    {

        parent::tearDown();

        $this->weeklyBattleService = null;

        $this->characterFactory = null;
    }

    public function test_create_record_for_character_death()
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

    public function test_does_not_create_record_for_character_death_when_monster_location_type_is_invalid()
    {

        $character = $this->characterFactory->getCharacter();

        $monster = $this->createMonster([
            'only_for_location_type' => null,
        ]);

        $this->weeklyBattleService->handleCharacterDeath($character, $monster);

        $weeklyBattleFight = $character->refresh()->weeklyBattleFights->first();

        $this->assertNull($weeklyBattleFight);
    }

    public function test_update_record_for_character_death()
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

    public function test_does_not_update_record_for_character_death_when_monster_location_type_is_invalid()
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

    public function test_mark_monster_as_killed()
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

    public function test_create_record_monster_was_killed()
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

    public function test_do_not_create_record_monster_was_killed_for_invalid_location_type()
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

    public function test_can_fight_monster()
    {
        $character = $this->characterFactory->getCharacter();
        $monster = $this->createMonster();

        $this->assertTrue($this->weeklyBattleService->canFightMonster($character, $monster));
    }

    public function test_cannot_fight_monster()
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
