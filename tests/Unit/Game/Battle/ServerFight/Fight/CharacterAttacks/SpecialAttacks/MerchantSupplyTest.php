<?php

namespace Tests\Unit\Game\Battle\ServerFight\Fight\CharacterAttacks\SpecialAttacks;

use App\Game\Battle\ServerFight\BattleBase;
use App\Game\Core\Chance\RandomNumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\Setup\Battle\ServerFight\SpecialAttackFactory;
use Tests\TestCase;

class MerchantSupplyTest extends TestCase
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
        $character = $this->specialAttackFactory->buildCharacter('Merchant', ['damage_stat' => 'chr', 'to_hit_stat' => 'dex']);
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => ['has_item' => false, 'chance' => 1.0],
        ]);

        $special = $this->specialAttackFactory->buildMerchantSupply();
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth(10000);
        $special->handleAttack($character, ['weapon_damage' => 100, 'damage_deduction' => 0.0]);

        $this->assertSame(10000, $special->getMonsterHealth());
        $this->assertEmpty($special->getMessages());
    }

    public function test_does_not_fire_when_the_chance_roll_fails(): void
    {
        $character = $this->specialAttackFactory->buildCharacter('Merchant', ['damage_stat' => 'chr', 'to_hit_stat' => 'dex']);
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => ['has_item' => true, 'chance' => 0.0],
        ]);

        $special = $this->specialAttackFactory->buildMerchantSupply();
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth(10000);
        $special->handleAttack($character, ['weapon_damage' => 100, 'damage_deduction' => 0.0]);

        $this->assertSame(10000, $special->getMonsterHealth());
        $this->assertEmpty($special->getMessages());
    }

    public function test_heads_deals_quadruple_damage_with_deduction_and_raid_cap(): void
    {
        $character = $this->specialAttackFactory->buildCharacter('Merchant', ['damage_stat' => 'chr', 'to_hit_stat' => 'dex']);
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => ['has_item' => true, 'chance' => 1.0],
        ]);

        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);
        $randomNumberGenerator->shouldReceive('numberBetween')->once()->with(1, 100)->andReturn(100);

        $special = $this->specialAttackFactory->buildMerchantSupply($randomNumberGenerator);
        $special->setIsRaidBoss(true);
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth(BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 100);
        $special->handleAttack($character, ['weapon_damage' => BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 10, 'damage_deduction' => 0.5]);

        $this->assertContains([
            'message' => 'The Plane weakens your ability to do full damage!',
            'type' => 'enemy-action',
        ], $special->getMessages());
        $this->assertContains([
            'message' => 'You flip the coin: Heads! You do 4x the damage for a total of: '.number_format(BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES),
            'type' => 'player-action',
        ], $special->getMessages());
        $this->assertSame(
            BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 100 - BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES,
            $special->getMonsterHealth()
        );
    }

    public function test_tails_deals_double_damage_without_deduction(): void
    {
        $character = $this->specialAttackFactory->buildCharacter('Merchant', ['damage_stat' => 'chr', 'to_hit_stat' => 'dex']);
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => ['has_item' => true, 'chance' => 1.0],
        ]);

        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);
        $randomNumberGenerator->shouldReceive('numberBetween')->once()->with(1, 100)->andReturn(50);

        $special = $this->specialAttackFactory->buildMerchantSupply($randomNumberGenerator);
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth(10000);
        $special->handleAttack($character, ['weapon_damage' => 100, 'damage_deduction' => 0.0]);

        $this->assertContains([
            'message' => 'You flip the coin: Tails! You do 2x the damage for a total of: 200',
            'type' => 'player-action',
        ], $special->getMessages());
        $this->assertSame(10000 - 200, $special->getMonsterHealth());
    }

    public function test_tails_caps_damage_for_raid_bosses(): void
    {
        $character = $this->specialAttackFactory->buildCharacter('Merchant', ['damage_stat' => 'chr', 'to_hit_stat' => 'dex']);
        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'extra_action_chance' => ['has_item' => true, 'chance' => 1.0],
        ]);

        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);
        $randomNumberGenerator->shouldReceive('numberBetween')->once()->with(1, 100)->andReturn(50);

        $special = $this->specialAttackFactory->buildMerchantSupply($randomNumberGenerator);
        $special->setIsRaidBoss(true);
        $special->setCharacterHealth(1000);
        $special->setMonsterHealth(BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 100);
        $special->handleAttack($character, ['weapon_damage' => BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 10, 'damage_deduction' => 0.0]);

        $this->assertContains([
            'message' => 'You flip the coin: Tails! You do 2x the damage for a total of: '.number_format(BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES),
            'type' => 'player-action',
        ], $special->getMessages());
        $this->assertSame(
            BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 100 - BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES,
            $special->getMonsterHealth()
        );
    }
}
