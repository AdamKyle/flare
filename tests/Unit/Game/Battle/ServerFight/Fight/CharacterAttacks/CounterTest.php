<?php

namespace Tests\Unit\Game\Battle\ServerFight\Fight\CharacterAttacks;

use App\Game\Core\Chance\ChanceCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\Setup\Battle\ServerFight\CounterFactory;
use Tests\TestCase;

class CounterTest extends TestCase
{
    use RefreshDatabase;

    private CounterFactory $counterFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->counterFactory = new CounterFactory();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->counterFactory);
    }

    public function test_monster_counter_is_skipped_for_a_weak_attack_against_a_raid_boss(): void
    {
        $counter = $this->counterFactory->buildCounter();
        $character = $this->counterFactory->buildCharacter();
        $this->counterFactory->seedCharacterSheet($character);
        $monster = $this->counterFactory->buildMonster(['is_raid_boss' => true]);

        $counter->monsterCounter($character, $monster);

        $this->assertContains([
            'message' => 'The enemy raises their weapon to counter your attack, alas they laugh outloud. "What a waste of of a fight you child. Come at me!"',
            'type' => 'enemy-action',
        ], $counter->getMessages());
    }

    public function test_monster_counter_does_nothing_when_the_monster_cannot_counter(): void
    {
        $counter = $this->counterFactory->buildCounter();
        $character = $this->counterFactory->buildCharacter();
        $counter->setCharacterHealth(1000);
        $this->counterFactory->seedCharacterSheet($character);
        $monster = $this->counterFactory->buildMonster(['counter_chance' => 0.0]);

        $counter->monsterCounter($character, $monster);

        $this->assertSame(1000, $counter->getCharacterHealth());
        $this->assertEmpty($counter->getMessages());
    }

    public function test_monster_counter_deals_damage_when_it_beats_the_character_ac(): void
    {
        $chanceCalculator = Mockery::mock(ChanceCalculator::class);
        $chanceCalculator->shouldReceive('passesPercentage')->once()->andReturn(true);

        $counter = $this->counterFactory->buildCounter($chanceCalculator);
        $character = $this->counterFactory->buildCharacter();
        $counter->setCharacterHealth(1000);
        $this->counterFactory->seedCharacterSheet($character, ['ac' => 5]);
        $monster = $this->counterFactory->buildMonster(['counter_chance' => 0.5, 'attack_range' => '20-20', 'increases_damage_by' => null]);

        $counter->monsterCounter($character, $monster);

        $this->assertSame(980, $counter->getCharacterHealth());
        $this->assertContains([
            'message' => 'The enemy counters your attack for: 20',
            'type' => 'enemy-action',
        ], $counter->getMessages());
    }

    public function test_monster_counter_is_blocked_when_it_does_not_beat_the_character_ac(): void
    {
        $chanceCalculator = Mockery::mock(ChanceCalculator::class);
        $chanceCalculator->shouldReceive('passesPercentage')->once()->andReturn(true);

        $counter = $this->counterFactory->buildCounter($chanceCalculator);
        $character = $this->counterFactory->buildCharacter();
        $counter->setCharacterHealth(1000);
        $this->counterFactory->seedCharacterSheet($character, ['ac' => 500]);
        $monster = $this->counterFactory->buildMonster(['counter_chance' => 0.5, 'attack_range' => '20-20', 'increases_damage_by' => null]);

        $counter->monsterCounter($character, $monster);

        $this->assertSame(1000, $counter->getCharacterHealth());
        $this->assertContains([
            'message' => 'You blocked the enemy counter attack!',
            'type' => 'player-action',
        ], $counter->getMessages());
    }

    public function test_player_counter_does_nothing_when_the_character_cannot_counter(): void
    {
        $counter = $this->counterFactory->buildCounter();
        $character = $this->counterFactory->buildCharacter();
        $counter->setMonsterHealth(1000);
        $this->counterFactory->seedCharacterSheet($character, ['counter_chance' => 0.0]);
        $monster = $this->counterFactory->buildMonster();

        $counter->playerCounter($character, $monster);

        $this->assertSame(1000, $counter->getMonsterHealth());
        $this->assertEmpty($counter->getMessages());
    }

    public function test_player_counter_deals_damage_when_it_beats_the_monster_ac(): void
    {
        $chanceCalculator = Mockery::mock(ChanceCalculator::class);
        $chanceCalculator->shouldReceive('passesPercentage')->once()->andReturn(true);

        $counter = $this->counterFactory->buildCounter($chanceCalculator);
        $character = $this->counterFactory->buildCharacter();
        $counter->setMonsterHealth(1000);
        $this->counterFactory->seedCharacterSheet($character, ['counter_chance' => 0.5]);
        Cache::put('character-attack-data-'.$character->id, [
            'attack_types' => [
                'attack' => ['weapon_damage' => 100],
            ],
        ]);
        $monster = $this->counterFactory->buildMonster(['ac' => 5]);

        $counter->playerCounter($character, $monster);

        $this->assertSame(900, $counter->getMonsterHealth());
        $this->assertContains([
            'message' => 'You counter the enemies attack for: 100',
            'type' => 'player-action',
        ], $counter->getMessages());
    }

    public function test_player_counter_uses_voided_attack_data_when_attacker_is_voided(): void
    {
        $chanceCalculator = Mockery::mock(ChanceCalculator::class);
        $chanceCalculator->shouldReceive('passesPercentage')->once()->andReturn(true);

        $counter = $this->counterFactory->buildCounter($chanceCalculator);
        $counter->setIsAttackerVoided(true);
        $character = $this->counterFactory->buildCharacter();
        $counter->setMonsterHealth(1000);
        $this->counterFactory->seedCharacterSheet($character, ['counter_chance' => 0.5]);
        Cache::put('character-attack-data-'.$character->id, [
            'attack_types' => [
                'voided_attack' => ['weapon_damage' => 500],
            ],
        ]);
        $monster = $this->counterFactory->buildMonster(['ac' => 5]);

        $counter->playerCounter($character, $monster);

        $this->assertSame(500, $counter->getMonsterHealth());
    }

    public function test_player_counter_is_blocked_when_it_does_not_beat_the_monster_ac(): void
    {
        $chanceCalculator = Mockery::mock(ChanceCalculator::class);
        $chanceCalculator->shouldReceive('passesPercentage')->once()->andReturn(true);

        $counter = $this->counterFactory->buildCounter($chanceCalculator);
        $character = $this->counterFactory->buildCharacter();
        $counter->setMonsterHealth(1000);
        $this->counterFactory->seedCharacterSheet($character, ['counter_chance' => 0.5]);
        Cache::put('character-attack-data-'.$character->id, [
            'attack_types' => [
                'attack' => ['weapon_damage' => 100],
            ],
        ]);
        $monster = $this->counterFactory->buildMonster(['ac' => 500]);

        $counter->playerCounter($character, $monster);

        $this->assertSame(1000, $counter->getMonsterHealth());
        $this->assertContains([
            'message' => 'The enemy managed to block your counter!',
            'type' => 'enemy-action',
        ], $counter->getMessages());
    }
}
