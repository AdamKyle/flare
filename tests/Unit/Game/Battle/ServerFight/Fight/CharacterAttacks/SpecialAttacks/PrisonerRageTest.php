<?php

namespace Tests\Unit\Game\Battle\ServerFight\Fight\CharacterAttacks\SpecialAttacks;

use App\Game\Battle\ServerFight\BattleBase;
use App\Game\Core\Chance\RandomNumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\Setup\Battle\ServerFight\SpecialAttackFactory;
use Tests\TestCase;

class PrisonerRageTest extends TestCase
{
    use RefreshDatabase;

    private SpecialAttackFactory $specialAttackFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->specialAttackFactory = new SpecialAttackFactory();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->specialAttackFactory);
    }

    public function test_does_not_fire_when_the_character_has_no_item(): void
    {
        $character = $this->specialAttackFactory->buildCharacter('Prisoner', ['damage_stat' => 'str', 'to_hit_stat' => 'dex']);
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => ['has_item' => false, 'chance' => 1.0],
        ]);

        $special = $this->specialAttackFactory->buildPrisonerRage();
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth(10000);
        $special->handleAttack($character, ['weapon_damage' => 1000, 'damage_deduction' => 0.0]);

        $this->assertSame(10000, $special->getMonsterHealth());
        $this->assertEmpty($special->getMessages());
    }

    public function test_does_not_fire_when_the_chance_roll_fails(): void
    {
        $character = $this->specialAttackFactory->buildCharacter('Prisoner', ['damage_stat' => 'str', 'to_hit_stat' => 'dex']);
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => ['has_item' => true, 'chance' => 0.0],
        ]);

        $special = $this->specialAttackFactory->buildPrisonerRage();
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth(10000);
        $special->handleAttack($character, ['weapon_damage' => 1000, 'damage_deduction' => 0.0]);

        $this->assertSame(10000, $special->getMonsterHealth());
        $this->assertEmpty($special->getMessages());
    }

    public function test_rage_hits_the_rolled_number_of_times_with_deduction_and_raid_cap(): void
    {
        $character = $this->specialAttackFactory->buildCharacter('Prisoner', ['damage_stat' => 'str', 'to_hit_stat' => 'dex']);
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => ['has_item' => true, 'chance' => 1.0],
        ]);

        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);
        $randomNumberGenerator->shouldReceive('numberBetween')->once()->with(1, 4)->andReturn(2);

        $special = $this->specialAttackFactory->buildPrisonerRage($randomNumberGenerator);
        $special->setIsRaidBoss(true);
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth(BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 100);
        $special->handleAttack($character, ['weapon_damage' => BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 10, 'damage_deduction' => 0.5]);

        $this->assertContains([
            'message' => 'The Plane weakens your ability to do full damage!',
            'type' => 'enemy-action',
        ], $special->getMessages());

        $playerMessages = array_values(array_filter($special->getMessages(), fn ($msg) => $msg['type'] === 'player-action'));
        $this->assertCount(3, $playerMessages);

        $this->assertSame(
            BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 100 - BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 3,
            $special->getMonsterHealth()
        );
    }
}
