<?php

namespace Tests\Unit\Game\Battle\ServerFight\Fight\CharacterAttacks\Types;

use App\Game\Battle\ServerFight\BattleBase;
use App\Game\Battle\ServerFight\Fight\CanHit;
use App\Game\Battle\ServerFight\Fight\Entrance;
use App\Game\Core\Chance\ChanceCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\Setup\Battle\ServerFight\CastTypeFactory;
use Tests\TestCase;

class CastTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_cast_attack_heals_and_does_secondary_attacks_when_spell_damage_is_not_positive(): void
    {
        $factory = new CastTypeFactory();
        $character = $factory->buildCharacter();
        $factory->seedCharacterSheet($character);
        $secondaryAttacks = $factory->mockSecondaryAttack(1000, 1000);

        $castType = $factory->build(secondaryAttacks: $secondaryAttacks);
        $castType->setCharacterHealth(1000);
        $castType->setMonsterHealth(1000);
        $factory->setAttackData($character, $castType, ['spell_damage' => 0]);

        $castType->castAttack($character, $factory->buildMonster());

        $this->assertSame(1000, $castType->getMonsterHealth());
    }

    public function test_cast_attack_entrances_the_enemy_and_casts_when_entrance_succeeds(): void
    {
        $factory = new CastTypeFactory();
        $character = $factory->buildCharacter();
        $factory->seedCharacterSheet($character);

        $entrance = Mockery::mock(Entrance::class);
        $entrance->shouldReceive('playerEntrance')->once();
        $entrance->shouldReceive('getMessages')->once()->andReturn([]);
        $entrance->shouldReceive('isEnemyEntranced')->once()->andReturn(true);

        $secondaryAttacks = $factory->mockSecondaryAttack(1000, 900);

        $castType = $factory->build(entrance: $entrance, secondaryAttacks: $secondaryAttacks);
        $castType->setCharacterHealth(1000);
        $castType->setMonsterHealth(1000);
        $factory->setAttackData($character, $castType, ['affixes' => ['entrancing_chance' => 0.5]]);
        $castType->setAllowEntrancing(true);

        $castType->castAttack($character, $factory->buildMonster());

        $this->assertSame(900, $castType->getMonsterHealth());
    }

    public function test_cast_attack_auto_hits_for_a_thief(): void
    {
        $factory = new CastTypeFactory();
        $character = $factory->buildCharacter(['name' => 'Thief']);
        $factory->seedCharacterSheet($character, ['extra_action_chance' => ['has_item' => true, 'chance' => 1.0]]);
        $secondaryAttacks = $factory->mockSecondaryAttack(1000, 900);

        $canHit = Mockery::mock(CanHit::class);
        $canHit->shouldReceive('canPlayerAutoHit')->once()->andReturn(true);

        $castType = $factory->build(canHit: $canHit, secondaryAttacks: $secondaryAttacks);
        $castType->setCharacterHealth(1000);
        $castType->setMonsterHealth(1000);
        $factory->setAttackData($character, $castType);
        $castType->setAllowEntrancing(false);

        $castType->castAttack($character, $factory->buildMonster());

        $this->assertContains([
            'message' => 'You dance along in the shadows, the enemy doesn\'t see you. Strike now!',
            'type' => 'regular',
        ], $castType->getMessages());
    }

    public function test_cast_attack_taunts_a_weak_spell_against_a_raid_boss(): void
    {
        $factory = new CastTypeFactory();
        $character = $factory->buildCharacter();
        $factory->seedCharacterSheet($character, ['ac' => 0]);
        $counter = $factory->mockMonsterCounter(1000, 1000);
        $secondaryAttacks = $factory->mockSecondaryAttack(1000, 900);

        $canHit = Mockery::mock(CanHit::class);
        $canHit->shouldReceive('canPlayerAutoHit')->once()->andReturn(false);
        $canHit->shouldReceive('canPlayerCastSpell')->once()->andReturn(true);

        $castType = $factory->build(canHit: $canHit, secondaryAttacks: $secondaryAttacks, counter: $counter);
        $castType->setCharacterHealth(1000);
        $castType->setMonsterHealth(1000);
        $factory->setAttackData($character, $castType, ['spell_damage' => 100]);
        $castType->setAllowEntrancing(false);

        $castType->castAttack($character, $factory->buildMonster(['is_raid_boss' => true, 'ac' => 0]));

        $this->assertContains([
            'message' => 'The enemy laughs at you. "Child your spells mean nothing to me. Go on, give it your best shot!"',
            'type' => 'enemy-action',
        ], $castType->getMessages());
    }

    public function test_cast_attack_is_blocked_when_ac_beats_the_spell_damage(): void
    {
        $factory = new CastTypeFactory();
        $character = $factory->buildCharacter();
        $factory->seedCharacterSheet($character);
        $secondaryAttacks = $factory->mockSecondaryAttack(1000, 1000);

        $canHit = Mockery::mock(CanHit::class);
        $canHit->shouldReceive('canPlayerAutoHit')->once()->andReturn(false);
        $canHit->shouldReceive('canPlayerCastSpell')->once()->andReturn(true);

        $castType = $factory->build(canHit: $canHit, secondaryAttacks: $secondaryAttacks);
        $castType->setCharacterHealth(1000);
        $castType->setMonsterHealth(1000);
        $factory->setAttackData($character, $castType, ['spell_damage' => 5]);
        $castType->setAllowEntrancing(false);

        $castType->castAttack($character, $factory->buildMonster(['ac' => 1000]));

        $this->assertContains([
            'message' => 'Your damage spell was blocked!',
            'type' => 'enemy-action',
        ], $castType->getMessages());
        $this->assertSame(1000, $castType->getMonsterHealth());
    }

    public function test_cast_attack_hits_when_not_blocked(): void
    {
        $factory = new CastTypeFactory();
        $character = $factory->buildCharacter();
        $factory->seedCharacterSheet($character);
        $counter = $factory->mockMonsterCounter(1000, 500);
        $secondaryAttacks = $factory->mockSecondaryAttack(1000, 500);

        $canHit = Mockery::mock(CanHit::class);
        $canHit->shouldReceive('canPlayerAutoHit')->once()->andReturn(false);
        $canHit->shouldReceive('canPlayerCastSpell')->once()->andReturn(true);

        $castType = $factory->build(canHit: $canHit, secondaryAttacks: $secondaryAttacks, counter: $counter);
        $castType->setCharacterHealth(1000);
        $castType->setMonsterHealth(1000);
        $factory->setAttackData($character, $castType, ['spell_damage' => 500]);
        $castType->setAllowEntrancing(false);

        $castType->castAttack($character, $factory->buildMonster(['ac' => 0]));

        $this->assertContains([
            'message' => 'Your damage spell(s) hits Test Monster for: 500',
            'type' => 'player-action',
        ], $castType->getMessages());
        $this->assertSame(500, $castType->getMonsterHealth());
    }

    public function test_cast_attack_fizzles_and_falls_back_to_secondary_attack(): void
    {
        $factory = new CastTypeFactory();
        $character = $factory->buildCharacter();
        $factory->seedCharacterSheet($character);
        $secondaryAttacks = $factory->mockSecondaryAttack(1000, 1000);

        $canHit = Mockery::mock(CanHit::class);
        $canHit->shouldReceive('canPlayerAutoHit')->once()->andReturn(false);
        $canHit->shouldReceive('canPlayerCastSpell')->once()->andReturn(false);

        $castType = $factory->build(canHit: $canHit, secondaryAttacks: $secondaryAttacks);
        $castType->setCharacterHealth(1000);
        $castType->setMonsterHealth(1000);
        $factory->setAttackData($character, $castType);
        $castType->setAllowEntrancing(false);

        $castType->castAttack($character, $factory->buildMonster());

        $this->assertContains([
            'message' => 'Your damage spell(s) fizzled and failed!',
            'type' => 'enemy-action',
        ], $castType->getMessages());
    }

    public function test_cast_attack_heals_after_entrancing_when_heal_for_is_positive(): void
    {
        $factory = new CastTypeFactory();
        $character = $factory->buildCharacter();
        $factory->seedCharacterSheet($character);

        $entrance = Mockery::mock(Entrance::class);
        $entrance->shouldReceive('playerEntrance')->once();
        $entrance->shouldReceive('getMessages')->once()->andReturn([]);
        $entrance->shouldReceive('isEnemyEntranced')->once()->andReturn(true);

        $secondaryAttacks = $factory->mockSecondaryAttack(950, 900);

        $castType = $factory->build(entrance: $entrance, secondaryAttacks: $secondaryAttacks);
        $castType->setCharacterHealth(950);
        $castType->setMonsterHealth(1000);
        $factory->setAttackData($character, $castType, ['affixes' => ['entrancing_chance' => 0.5], 'heal_for' => 50]);
        $castType->setAllowEntrancing(true);

        $castType->castAttack($character, $factory->buildMonster());

        $this->assertContains([
            'message' => 'Your healing spell(s) heals you completely for: 50',
            'type' => 'player-action',
        ], $castType->getMessages());
    }

    public function test_cast_attack_heals_on_auto_hit_when_heal_for_is_positive(): void
    {
        $factory = new CastTypeFactory();
        $character = $factory->buildCharacter(['name' => 'Thief']);
        $factory->seedCharacterSheet($character, ['extra_action_chance' => ['has_item' => true, 'chance' => 1.0]]);
        $secondaryAttacks = $factory->mockSecondaryAttack(950, 900);

        $canHit = Mockery::mock(CanHit::class);
        $canHit->shouldReceive('canPlayerAutoHit')->once()->andReturn(true);

        $castType = $factory->build(canHit: $canHit, secondaryAttacks: $secondaryAttacks);
        $castType->setCharacterHealth(950);
        $castType->setMonsterHealth(1000);
        $factory->setAttackData($character, $castType, ['heal_for' => 50]);
        $castType->setAllowEntrancing(false);

        $castType->castAttack($character, $factory->buildMonster());

        $this->assertContains([
            'message' => 'Your healing spell(s) heals you completely for: 50',
            'type' => 'player-action',
        ], $castType->getMessages());
    }

    public function test_cast_attack_heals_when_blocked_and_heal_for_is_positive(): void
    {
        $factory = new CastTypeFactory();
        $character = $factory->buildCharacter();
        $factory->seedCharacterSheet($character, ['health' => 960]);
        $secondaryAttacks = $factory->mockSecondaryAttack(960, 1000);

        $canHit = Mockery::mock(CanHit::class);
        $canHit->shouldReceive('canPlayerAutoHit')->once()->andReturn(false);
        $canHit->shouldReceive('canPlayerCastSpell')->once()->andReturn(true);

        $castType = $factory->build(canHit: $canHit, secondaryAttacks: $secondaryAttacks);
        $castType->setCharacterHealth(950);
        $castType->setMonsterHealth(1000);
        $factory->setAttackData($character, $castType, ['spell_damage' => 5, 'heal_for' => 50]);
        $castType->setAllowEntrancing(false);

        $castType->castAttack($character, $factory->buildMonster(['ac' => 1000]));

        $this->assertContains([
            'message' => 'Your healing spell(s) heals you for: 40',
            'type' => 'player-action',
        ], $castType->getMessages());
    }

    public function test_cast_attack_heals_when_fizzled_and_heal_for_is_positive(): void
    {
        $factory = new CastTypeFactory();
        $character = $factory->buildCharacter();
        $factory->seedCharacterSheet($character, ['health' => 960]);
        $secondaryAttacks = $factory->mockSecondaryAttack(960, 1000);

        $canHit = Mockery::mock(CanHit::class);
        $canHit->shouldReceive('canPlayerAutoHit')->once()->andReturn(false);
        $canHit->shouldReceive('canPlayerCastSpell')->once()->andReturn(false);

        $castType = $factory->build(canHit: $canHit, secondaryAttacks: $secondaryAttacks);
        $castType->setCharacterHealth(950);
        $castType->setMonsterHealth(1000);
        $factory->setAttackData($character, $castType, ['heal_for' => 50]);
        $castType->setAllowEntrancing(false);

        $castType->castAttack($character, $factory->buildMonster());

        $this->assertContains([
            'message' => 'Your healing spell(s) heals you for: 40',
            'type' => 'player-action',
        ], $castType->getMessages());
    }

    public function test_cast_attack_applies_a_critical_strike_to_spell_damage(): void
    {
        $factory = new CastTypeFactory();
        $character = $factory->buildCharacter();
        $factory->seedCharacterSheet($character, ['skills' => ['criticality' => 1.0]]);
        $counter = $factory->mockMonsterCounter(1000, 800);
        $secondaryAttacks = $factory->mockSecondaryAttack(1000, 800);

        $canHit = Mockery::mock(CanHit::class);
        $canHit->shouldReceive('canPlayerAutoHit')->once()->andReturn(false);
        $canHit->shouldReceive('canPlayerCastSpell')->once()->andReturn(true);

        $castType = $factory->build(canHit: $canHit, secondaryAttacks: $secondaryAttacks, counter: $counter);
        $castType->setCharacterHealth(1000);
        $castType->setMonsterHealth(1000);
        $factory->setAttackData($character, $castType, ['spell_damage' => 100]);
        $castType->setAllowEntrancing(false);

        $castType->castAttack($character, $factory->buildMonster(['ac' => 0]));

        $this->assertContains([
            'message' => 'Your magic radiates across the plane. Even The Creator is terrified! (Critical strike!)',
            'type' => 'player-action',
        ], $castType->getMessages());
        $this->assertContains([
            'message' => 'Your damage spell(s) hits Test Monster for: 200',
            'type' => 'player-action',
        ], $castType->getMessages());
    }

    public function test_heal_deals_chr_damage_from_cache_when_character_is_already_at_max_health(): void
    {
        $factory = new CastTypeFactory();
        $character = $factory->buildCharacter(['name' => 'Cleric']);
        $factory->seedCharacterSheet($character, ['health' => 1000, 'chr_modded' => 400]);

        Cache::put('character-'.$character->id.'-healing-amount', 50);

        $castType = $factory->build();
        $castType->setCharacterHealth(1000);
        $castType->setMonsterHealth(1000);
        $factory->setAttackData($character, $castType, ['heal_for' => 0]);

        $castType->heal($character);

        $this->assertSame(900, $castType->getMonsterHealth());
        $this->assertContains([
            'message' => 'Your prayers for health rage at the enemy as you lash out in a fevered holy pitch for: 100!',
            'type' => 'player-action',
        ], $castType->getMessages());
    }

    public function test_spell_damage_evades_when_monster_evasion_is_above_one(): void
    {
        $factory = new CastTypeFactory();
        $character = $factory->buildCharacter();

        $castType = $factory->build();
        $castType->setCharacterHealth(1000);
        $castType->setMonsterHealth(1000);
        $factory->setAttackData($character, $castType);

        $castType->spellDamage($character, $factory->buildMonster(['spell_evasion' => 2.0]), 100);

        $this->assertContains([
            'message' => 'The enemy evades your magic!',
            'type' => 'enemy-action',
        ], $castType->getMessages());
        $this->assertSame(1000, $castType->getMonsterHealth());
    }

    public function test_spell_damage_evades_when_the_chance_calculator_allows_the_evasion(): void
    {
        $factory = new CastTypeFactory();
        $character = $factory->buildCharacter();

        $chanceCalculator = Mockery::mock(ChanceCalculator::class);
        $chanceCalculator->shouldReceive('passesPercentage')->once()->andReturn(true);

        $castType = $factory->build(chanceCalculator: $chanceCalculator);
        $castType->setCharacterHealth(1000);
        $castType->setMonsterHealth(1000);
        $factory->setAttackData($character, $castType);

        $castType->spellDamage($character, $factory->buildMonster(['spell_evasion' => 0.5]), 100);

        $this->assertContains([
            'message' => 'The enemy evades your magic!',
            'type' => 'enemy-action',
        ], $castType->getMessages());
        $this->assertSame(1000, $castType->getMonsterHealth());
    }

    public function test_spell_damage_lands_when_the_enemy_fails_to_evade(): void
    {
        $factory = new CastTypeFactory();
        $character = $factory->buildCharacter();

        $castType = $factory->build();
        $castType->setCharacterHealth(1000);
        $castType->setMonsterHealth(1000);
        $factory->setAttackData($character, $castType);

        $castType->spellDamage($character, $factory->buildMonster(['spell_evasion' => 0.0]), 100);

        $this->assertContains([
            'message' => 'The enemy fails to evade your magics',
            'type' => 'player-action',
        ], $castType->getMessages());
        $this->assertSame(900, $castType->getMonsterHealth());
    }

    public function test_spell_damage_skips_evasion_checks_when_entranced(): void
    {
        $factory = new CastTypeFactory();
        $character = $factory->buildCharacter();

        $castType = $factory->build();
        $castType->setCharacterHealth(1000);
        $castType->setMonsterHealth(1000);
        $factory->setAttackData($character, $castType);

        $castType->spellDamage($character, $factory->buildMonster(['spell_evasion' => 2.0]), 100, true);

        $this->assertSame(900, $castType->getMonsterHealth());
    }

    public function test_spell_damage_caps_for_raid_bosses(): void
    {
        $factory = new CastTypeFactory();
        $character = $factory->buildCharacter();

        $castType = $factory->build();
        $castType->setIsRaidBoss(true);
        $castType->setCharacterHealth(1000);
        $castType->setMonsterHealth(BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 100);
        $factory->setAttackData($character, $castType);

        $castType->spellDamage($character, $factory->buildMonster(), BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 10);

        $this->assertSame(
            BattleBase::MAX_DAMAGE_FOR_RAID_BOSSES * 99,
            $castType->getMonsterHealth()
        );
    }

    public function test_do_spell_damage_aborts_when_the_character_dies_and_skips_secondary_attack(): void
    {
        $factory = new CastTypeFactory();
        $character = $factory->buildCharacter();

        $specialAttacks = $factory->noOpSpecialAttacks();
        $counter = $factory->mockMonsterCounter(0, 1000);
        $castType = $factory->build(specialAttacks: $specialAttacks, counter: $counter);
        $castType->setCharacterHealth(0);
        $castType->setMonsterHealth(1000);
        $factory->setAttackData($character, $castType);

        $castType->doSpellDamage($character, $factory->buildMonster(), 100);

        $this->assertSame(0, $castType->getCharacterHealth());
    }

    public function test_heal_does_nothing_without_cached_or_attack_healing(): void
    {
        $factory = new CastTypeFactory();
        $character = $factory->buildCharacter();
        $factory->seedCharacterSheet($character);

        $castType = $factory->build();
        $castType->setCharacterHealth(1000);
        $castType->setMonsterHealth(1000);
        $factory->setAttackData($character, $castType, ['heal_for' => 0]);

        $castType->heal($character);

        $this->assertSame(1000, $castType->getCharacterHealth());
        $this->assertEmpty($castType->getMessages());
    }

    public function test_heal_partially_heals_when_the_amount_exceeds_what_is_needed(): void
    {
        $factory = new CastTypeFactory();
        $character = $factory->buildCharacter();
        $factory->seedCharacterSheet($character, ['health' => 1000]);

        $castType = $factory->build();
        $castType->setCharacterHealth(950);
        $castType->setMonsterHealth(1000);
        $factory->setAttackData($character, $castType, ['heal_for' => 100]);

        $castType->heal($character);

        $this->assertSame(1000, $castType->getCharacterHealth());
        $this->assertContains([
            'message' => 'Your healing spell(s) partially heals you for: 50',
            'type' => 'player-action',
        ], $castType->getMessages());
    }

    public function test_heal_deals_chr_damage_when_character_is_already_at_max_health(): void
    {
        $factory = new CastTypeFactory();
        $character = $factory->buildCharacter(['name' => 'Cleric']);
        $factory->seedCharacterSheet($character, ['health' => 1000, 'chr_modded' => 400]);

        $castType = $factory->build();
        $castType->setCharacterHealth(1000);
        $castType->setMonsterHealth(1000);
        $factory->setAttackData($character, $castType, ['heal_for' => 100]);

        $castType->heal($character);

        $this->assertSame(900, $castType->getMonsterHealth());
        $this->assertContains([
            'message' => 'Your prayers for health rage at the enemy as you lash out in a fevered holy pitch for: 100!',
            'type' => 'player-action',
        ], $castType->getMessages());
    }

    public function test_heal_uses_the_lower_percentage_chr_damage_for_non_healer_classes(): void
    {
        $factory = new CastTypeFactory();
        $character = $factory->buildCharacter(['name' => 'Fighter']);
        $factory->seedCharacterSheet($character, ['health' => 1000, 'chr_modded' => 400]);

        $castType = $factory->build();
        $castType->setCharacterHealth(1000);
        $castType->setMonsterHealth(1000);
        $factory->setAttackData($character, $castType, ['heal_for' => 100]);

        $castType->heal($character);

        $this->assertSame(980, $castType->getMonsterHealth());
    }

    public function test_heal_applies_a_critical_heal_and_fully_heals_from_cache_recursively(): void
    {
        $factory = new CastTypeFactory();
        $character = $factory->buildCharacter();
        $factory->seedCharacterSheet($character, ['health' => 1000, 'skills' => ['criticality' => 1.0]]);

        Cache::put('character-'.$character->id.'-healing-amount', 40);

        $castType = $factory->build();
        $castType->setCharacterHealth(950);
        $castType->setMonsterHealth(1000);
        $factory->setAttackData($character, $castType, ['heal_for' => 100]);

        $castType->heal($character);

        $this->assertSame(1000, $castType->getCharacterHealth());
        $this->assertContains([
            'message' => 'Your healing spell(s) heals you completely for: 40',
            'type' => 'player-action',
        ], $castType->getMessages());
        $this->assertContains([
            'message' => 'The heavens open and your wounds start to heal over (Critical heal!)',
            'type' => 'player-action',
        ], $castType->getMessages());
    }

    public function test_heal_during_fight_heals_from_the_cache_when_below_max_health(): void
    {
        $factory = new CastTypeFactory();
        $character = $factory->buildCharacter();
        $factory->seedCharacterSheet($character, ['health' => 1000]);

        Cache::put('character-'.$character->id.'-healing-amount', 40);

        $castType = $factory->build();
        $castType->setCharacterHealth(950);
        $castType->setMonsterHealth(1000);

        $castType->healDuringFight($character);

        $this->assertSame(990, $castType->getCharacterHealth());
        $this->assertContains([
            'message' => 'You reserved healing bursts forward and you feel life flowing through your veins.',
            'type' => 'player-action',
        ], $castType->getMessages());
    }

    public function test_heal_during_fight_does_nothing_when_no_cached_healing_exists(): void
    {
        $factory = new CastTypeFactory();
        $character = $factory->buildCharacter();
        $factory->seedCharacterSheet($character, ['health' => 1000]);

        $castType = $factory->build();
        $castType->setCharacterHealth(950);
        $castType->setMonsterHealth(1000);

        $castType->healDuringFight($character);

        $this->assertSame(950, $castType->getCharacterHealth());
        $this->assertEmpty($castType->getMessages());
    }

    public function test_reset_messages_clears_both_cast_and_entrance_messages(): void
    {
        $factory = new CastTypeFactory();

        $entrance = Mockery::mock(Entrance::class);
        $entrance->shouldReceive('clearMessages')->once();

        $castType = $factory->build(entrance: $entrance);

        $castType->resetMessages();

        $this->assertEmpty($castType->getMessages());
    }

    public function test_set_character_cast_and_attack_reads_from_the_cast_and_attack_cache(): void
    {
        $factory = new CastTypeFactory();
        $character = $factory->buildCharacter();

        $factory->seedCharacterSheet($character);

        Cache::put('character-attack-data-'.$character->id, [
            'attack_types' => ['cast_and_attack' => ['spell_damage' => 0, 'heal_for' => 0, 'damage_deduction' => 0.0]],
        ]);

        $secondaryAttacks = $factory->mockSecondaryAttack(1000, 1000);
        $castType = $factory->build(secondaryAttacks: $secondaryAttacks);
        $castType->setCharacterCastAndAttack($character, false);

        $castType->setCharacterHealth(1000);
        $castType->setMonsterHealth(1000);

        $castType->castAttack($character, $factory->buildMonster());

        $this->assertSame(1000, $castType->getMonsterHealth());
    }

    public function test_set_character_attack_and_cast_reads_from_the_attack_and_cast_cache(): void
    {
        $factory = new CastTypeFactory();
        $character = $factory->buildCharacter();

        $factory->seedCharacterSheet($character);

        Cache::put('character-attack-data-'.$character->id, [
            'attack_types' => ['voided_attack_and_cast' => ['spell_damage' => 0, 'heal_for' => 0, 'damage_deduction' => 0.0]],
        ]);

        $secondaryAttacks = $factory->mockSecondaryAttack(1000, 1000);
        $castType = $factory->build(secondaryAttacks: $secondaryAttacks);
        $castType->setCharacterAttackAndCast($character, true);

        $castType->setCharacterHealth(1000);
        $castType->setMonsterHealth(1000);

        $castType->castAttack($character, $factory->buildMonster());

        $this->assertSame(1000, $castType->getMonsterHealth());
    }
}
