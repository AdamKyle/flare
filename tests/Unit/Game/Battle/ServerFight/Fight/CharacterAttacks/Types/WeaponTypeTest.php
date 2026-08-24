<?php

namespace Tests\Unit\Game\Battle\ServerFight\Fight\CharacterAttacks\Types;

use App\Game\Battle\ServerFight\BattleBase;
use App\Game\Battle\ServerFight\Fight\CanHit;
use App\Game\Battle\ServerFight\Fight\CharacterAttacks\SpecialAttacks;
use App\Game\Battle\ServerFight\Fight\Entrance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\Setup\Battle\ServerFight\WeaponTypeFactory;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class WeaponTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_do_weapon_attack_finishes_the_entranced_hit_when_already_entranced(): void
    {
        $factory = new WeaponTypeFactory();
        $character = $factory->buildCharacter();
        $factory->seedCharacterSheet($character);

        $specialAttacks = Mockery::mock(SpecialAttacks::class);
        $factory->stubSpecialAttacksNoOp($specialAttacks, 1000, 900);
        $secondaryAttacks = $factory->mockSecondaryAttack(1000, 900);

        $weaponType = $factory->build(specialAttacks: $specialAttacks, secondaryAttacks: $secondaryAttacks);
        $weaponType->setCharacterHealth(1000);
        $weaponType->setMonsterHealth(1000);
        $weaponType->setEntranced();
        $factory->setAttackData($character, $weaponType);
        $weaponType->setAllowEntrancing(false);

        $weaponType->doWeaponAttack($character, $factory->buildMonster());

        $this->assertSame(900, $weaponType->getMonsterHealth());
    }

    public function test_do_weapon_attack_entrances_the_enemy_and_attacks_when_entrance_succeeds(): void
    {
        $factory = new WeaponTypeFactory();
        $character = $factory->buildCharacter();
        $factory->seedCharacterSheet($character);

        $entrance = Mockery::mock(Entrance::class);
        $entrance->shouldReceive('playerEntrance')->once();
        $entrance->shouldReceive('getMessages')->once()->andReturn([]);
        $entrance->shouldReceive('isEnemyEntranced')->once()->andReturn(true);

        $specialAttacks = Mockery::mock(SpecialAttacks::class);
        $factory->stubSpecialAttacksNoOp($specialAttacks, 1000, 900);
        $secondaryAttacks = $factory->mockSecondaryAttack(1000, 900);

        $weaponType = $factory->build(entrance: $entrance, specialAttacks: $specialAttacks, secondaryAttacks: $secondaryAttacks);
        $weaponType->setCharacterHealth(1000);
        $weaponType->setMonsterHealth(1000);
        $factory->setAttackData($character, $weaponType, ['affixes' => ['entrancing_chance' => 0.5]]);
        $weaponType->setAllowEntrancing(true);

        $weaponType->doWeaponAttack($character, $factory->buildMonster());

        $this->assertSame(900, $weaponType->getMonsterHealth());
    }

    public function test_do_weapon_attack_auto_hits_for_a_thief(): void
    {
        $factory = new WeaponTypeFactory();
        $character = (new CharacterFactory())->createBaseCharacter([], ['name' => 'Thief'], assignPassiveSkills: false)->getCharacter();
        $factory->seedCharacterSheet($character, ['extra_action_chance' => ['has_item' => true, 'chance' => 1.0]]);

        $specialAttacks = Mockery::mock(SpecialAttacks::class);
        $factory->stubSpecialAttacksNoOp($specialAttacks, 1000, 900);
        $counter = $factory->mockMonsterCounter(1000, 1000);
        $secondaryAttacks = $factory->mockSecondaryAttack(1000, 900);

        $canHit = Mockery::mock(CanHit::class);
        $canHit->shouldReceive('canPlayerAutoHit')->once()->andReturn(true);

        $weaponType = $factory->build(canHit: $canHit, specialAttacks: $specialAttacks, secondaryAttacks: $secondaryAttacks, counter: $counter);
        $weaponType->setCharacterHealth(1000);
        $weaponType->setMonsterHealth(1000);
        $factory->setAttackData($character, $weaponType);
        $weaponType->setAllowEntrancing(false);

        $weaponType->doWeaponAttack($character, $factory->buildMonster());

        $this->assertContains([
            'message' => 'You dance along in the shadows, the enemy doesn\'t see you. Strike now!',
            'type' => 'regular',
        ], $weaponType->getMessages());
    }

    public function test_do_weapon_attack_blocks_and_falls_back_to_secondary_attack_when_blocked(): void
    {
        $factory = new WeaponTypeFactory();
        $character = $factory->buildCharacter();
        $factory->seedCharacterSheet($character);

        $secondaryAttacks = $factory->mockSecondaryAttack(1000, 1000);

        $canHit = Mockery::mock(CanHit::class);
        $canHit->shouldReceive('canPlayerAutoHit')->once()->andReturn(false);
        $canHit->shouldReceive('canPlayerHitMonster')->once()->andReturn(true);

        $weaponType = $factory->build(canHit: $canHit, secondaryAttacks: $secondaryAttacks);
        $weaponType->setCharacterHealth(1000);
        $weaponType->setMonsterHealth(1000);
        $factory->setAttackData($character, $weaponType, ['weapon_damage' => 5]);
        $weaponType->setAllowEntrancing(false);

        $weaponType->doWeaponAttack($character, $factory->buildMonster(['ac' => 1000]));

        $this->assertContains([
            'message' => 'Your weapon was blocked!',
            'type' => 'enemy-action',
        ], $weaponType->getMessages());
        $this->assertSame(1000, $weaponType->getMonsterHealth());
    }

    public function test_do_weapon_attack_hits_when_not_blocked(): void
    {
        $factory = new WeaponTypeFactory();
        $character = $factory->buildCharacter();
        $factory->seedCharacterSheet($character);

        $specialAttacks = Mockery::mock(SpecialAttacks::class);
        $factory->stubSpecialAttacksNoOp($specialAttacks, 1000, 500);
        $counter = $factory->mockMonsterCounter(1000, 500);
        $secondaryAttacks = $factory->mockSecondaryAttack(1000, 500);

        $canHit = Mockery::mock(CanHit::class);
        $canHit->shouldReceive('canPlayerAutoHit')->once()->andReturn(false);
        $canHit->shouldReceive('canPlayerHitMonster')->once()->andReturn(true);

        $weaponType = $factory->build(canHit: $canHit, specialAttacks: $specialAttacks, secondaryAttacks: $secondaryAttacks, counter: $counter);
        $weaponType->setCharacterHealth(1000);
        $weaponType->setMonsterHealth(1000);
        $factory->setAttackData($character, $weaponType, ['weapon_damage' => 500]);
        $weaponType->setAllowEntrancing(false);

        $weaponType->doWeaponAttack($character, $factory->buildMonster(['ac' => 0]));

        $this->assertContains([
            'message' => 'Your weapon hits Test Monster for: 500',
            'type' => 'player-action',
        ], $weaponType->getMessages());
        $this->assertSame(500, $weaponType->getMonsterHealth());
    }

    public function test_do_weapon_attack_misses_and_falls_back_to_secondary_attack(): void
    {
        $factory = new WeaponTypeFactory();
        $character = $factory->buildCharacter();
        $factory->seedCharacterSheet($character);

        $secondaryAttacks = $factory->mockSecondaryAttack(1000, 1000);

        $canHit = Mockery::mock(CanHit::class);
        $canHit->shouldReceive('canPlayerAutoHit')->once()->andReturn(false);
        $canHit->shouldReceive('canPlayerHitMonster')->once()->andReturn(false);

        $weaponType = $factory->build(canHit: $canHit, secondaryAttacks: $secondaryAttacks);
        $weaponType->setCharacterHealth(1000);
        $weaponType->setMonsterHealth(1000);
        $factory->setAttackData($character, $weaponType);
        $weaponType->setAllowEntrancing(false);

        $weaponType->doWeaponAttack($character, $factory->buildMonster());

        $this->assertContains([
            'message' => 'Your attack missed!',
            'type' => 'enemy-action',
        ], $weaponType->getMessages());
    }

    public function test_weapon_damage_applies_deduction_and_caps_for_raid_bosses(): void
    {
        $factory = new WeaponTypeFactory();
        $character = $factory->buildCharacter();

        $specialAttacks = Mockery::mock(SpecialAttacks::class);
        $factory->stubSpecialAttacksNoOp($specialAttacks, 1000, BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 99);

        $weaponType = $factory->build(specialAttacks: $specialAttacks);
        $weaponType->setIsRaidBoss(true);
        $weaponType->setCharacterHealth(1000);
        $weaponType->setMonsterHealth(BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 100);
        $factory->setAttackData($character, $weaponType, ['weapon_damage' => BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 10, 'damage_deduction' => 0.5]);

        $weaponType->weaponDamage($character, 'Test Monster', BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 10);

        $this->assertSame(BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 99, $weaponType->getMonsterHealth());
    }

    public function test_weapon_attack_aborts_when_the_character_dies_and_skips_secondary_attack(): void
    {
        $factory = new WeaponTypeFactory();
        $character = $factory->buildCharacter();

        $specialAttacks = Mockery::mock(SpecialAttacks::class);
        $factory->stubSpecialAttacksNoOp($specialAttacks, 900, 500);
        $counter = $factory->mockMonsterCounter(0, 500);

        $weaponType = $factory->build(specialAttacks: $specialAttacks, counter: $counter);
        $weaponType->setCharacterHealth(0);
        $weaponType->setMonsterHealth(1000);
        $factory->setAttackData($character, $weaponType, ['weapon_damage' => 500]);

        $weaponType->weaponAttack($character, $factory->buildMonster(), 500);

        $this->assertSame(0, $weaponType->getCharacterHealth());
    }

    public function test_set_character_attack_data_prefixes_voided_when_the_character_is_voided(): void
    {
        $factory = new WeaponTypeFactory();
        $character = $factory->buildCharacter();

        Cache::put('character-attack-data-'.$character->id, [
            'attack_types' => ['voided_attack' => ['weapon_damage' => 50, 'damage_deduction' => 0.0]],
        ]);

        $specialAttacks = Mockery::mock(SpecialAttacks::class);
        $factory->stubSpecialAttacksNoOp($specialAttacks, 1000, 950);

        $weaponType = $factory->build(specialAttacks: $specialAttacks);
        $weaponType->setCharacterHealth(1000);
        $weaponType->setMonsterHealth(1000);
        $weaponType->setCharacterAttackData($character, true, 'attack');

        $weaponType->weaponDamage($character, 'Test Monster', 50);

        $this->assertContains([
            'message' => 'Your weapon hits Test Monster for: 50',
            'type' => 'player-action',
        ], $weaponType->getMessages());
    }

    public function test_reset_messages_clears_both_weapon_and_entrance_messages(): void
    {
        $factory = new WeaponTypeFactory();

        $entrance = Mockery::mock(Entrance::class);
        $entrance->shouldReceive('clearMessages')->once();

        $weaponType = $factory->build(entrance: $entrance);

        $weaponType->resetMessages();

        $this->assertEmpty($weaponType->getMessages());
    }
}
