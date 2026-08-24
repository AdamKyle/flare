<?php

namespace Tests\Unit\Game\Battle\ServerFight\Fight\CharacterAttacks\SpecialAttacks;

use App\Game\Battle\ServerFight\BattleBase;
use App\Game\Core\Chance\RandomNumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\Setup\Battle\ServerFight\SpecialAttackFactory;
use Tests\TestCase;

class HammerSmashTest extends TestCase
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
        $character = $this->specialAttackFactory->buildCharacter('Blacksmith', ['damage_stat' => 'str', 'to_hit_stat' => 'dex']);
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => ['has_item' => false, 'chance' => 1.0],
        ]);

        $special = $this->specialAttackFactory->buildHammerSmash();
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth(10000);
        $special->handleHammerSmash($character, ['weapon_damage' => 1000, 'damage_deduction' => 0.0]);

        $this->assertSame(10000, $special->getMonsterHealth());
        $this->assertEmpty($special->getMessages());
    }

    public function test_does_not_fire_when_the_chance_roll_fails(): void
    {
        $character = $this->specialAttackFactory->buildCharacter('Blacksmith', ['damage_stat' => 'str', 'to_hit_stat' => 'dex']);
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => ['has_item' => true, 'chance' => 0.0],
        ]);

        $special = $this->specialAttackFactory->buildHammerSmash();
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth(10000);
        $special->handleHammerSmash($character, ['weapon_damage' => 1000, 'damage_deduction' => 0.0]);

        $this->assertSame(10000, $special->getMonsterHealth());
        $this->assertEmpty($special->getMessages());
    }

    public function test_smash_deals_damage_reduces_by_deduction_and_caps_for_raid_bosses_with_no_aftershock(): void
    {
        $character = $this->specialAttackFactory->buildCharacter('Blacksmith', ['damage_stat' => 'str', 'to_hit_stat' => 'dex']);
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => ['has_item' => true, 'chance' => 1.0],
            'str_modded' => 4_000_000_000_000_000,
        ]);

        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);
        $randomNumberGenerator->shouldReceive('numberBetween')->once()->with(1, 10_000)->andReturn(10_000);

        $special = $this->specialAttackFactory->buildHammerSmash($randomNumberGenerator);
        $special->setIsRaidBoss(true);
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth(BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 100_000);
        $special->handleHammerSmash($character, ['weapon_damage' => 1000, 'damage_deduction' => 0.5]);

        $this->assertContains([
            'message' => 'The Plane weakens your ability to do full damage!',
            'type' => 'enemy-action',
        ], $special->getMessages());
        $this->assertSame(
            BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 100_000 - BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES,
            $special->getMonsterHealth()
        );
    }

    public function test_smash_fires_aftershocks_when_the_chance_passes(): void
    {
        $character = $this->specialAttackFactory->buildCharacter('Blacksmith', ['damage_stat' => 'str', 'to_hit_stat' => 'dex']);
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => ['has_item' => true, 'chance' => 1.0],
            'str_modded' => 1000,
        ]);

        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);
        $randomNumberGenerator->shouldReceive('numberBetween')->once()->with(1, 10_000)->andReturn(1);

        $special = $this->specialAttackFactory->buildHammerSmash($randomNumberGenerator);
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth(1_000_000);
        $special->handleHammerSmash($character, ['weapon_damage' => 1000, 'damage_deduction' => 0.0]);

        $this->assertContains([
            'message' => 'The enemy feels the aftershocks of the Hammer Smash!',
            'type' => 'regular',
        ], $special->getMessages());

        $playerActionMessages = array_values(array_filter($special->getMessages(), fn ($msg) => $msg['type'] === 'player-action'));
        $this->assertGreaterThan(1, count($playerActionMessages));
    }
}
