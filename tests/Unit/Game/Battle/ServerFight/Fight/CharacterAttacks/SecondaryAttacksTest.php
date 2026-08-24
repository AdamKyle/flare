<?php

namespace Tests\Unit\Game\Battle\ServerFight\Fight\CharacterAttacks;

use App\Flare\Models\Location;
use App\Game\Core\Combat\Values\AttackType;
use App\Game\Maps\Values\LocationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Setup\Battle\ServerFight\SecondaryAttacksFactory;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateMonster;

class SecondaryAttacksTest extends TestCase
{
    use CreateMonster, RefreshDatabase;

    private SecondaryAttacksFactory $secondaryAttacksFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->secondaryAttacksFactory = new SecondaryAttacksFactory();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->secondaryAttacksFactory);
    }

    public function test_normal_monster_life_stealing_resistance_reduces_damage_heals_and_caps_player_health(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'health' => 1000,
        ]);

        $monster = $this->createMonster([
            'affix_resistance' => 0.0,
            'life_stealing_resistance' => 0.5,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
        ]);

        $serverMonster = $this->secondaryAttacksFactory->buildServerMonster($monster->toArray());
        $secondaryAttacks = $this->secondaryAttacksFactory->buildSecondaryAttacks();
        $secondaryAttacks->setCharacterHealth(900);
        $secondaryAttacks->setMonsterHealth(1000);
        $secondaryAttacks->setAttackData([
            'attack_type' => AttackType::ATTACK->value,
            'damage_deduction' => 0.0,
            'affixes' => [
                'cant_be_resisted' => true,
                'stacking_life_stealing' => 0.0,
                'life_stealing' => 0.5,
            ],
            'weapon_damage' => 100,
            'special_damage' => [],
            'ring_damage' => 0,
        ]);

        $secondaryAttacks->affixLifeStealingDamage($character, $serverMonster);

        $this->assertEquals(750, $secondaryAttacks->getMonsterHealth());
        $this->assertEquals(1000, $secondaryAttacks->getCharacterHealth());
        $this->assertContains([
            'message' => 'The enemy resisted your attempt to steal 50.00% of their health and instead you stole 25.00%, dealing 250 damage.',
            'type' => 'enemy-action',
        ], $secondaryAttacks->getMessages());
    }

    public function test_vampire_at_underwater_caves_can_life_steal_seventy_five_percent(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], [
            'name' => 'Vampire',
        ])->givePlayerLocation()->getCharacter();
        $character->map->x_position = 16;
        $character->map->y_position = 16;

        Location::create([
            'name' => 'Underwater Caves',
            'game_map_id' => $character->map->game_map_id,
            'can_players_enter' => true,
            'can_auto_battle' => true,
            'description' => 'sample',
            'is_port' => false,
            'x' => 16,
            'y' => 16,
            'type' => LocationType::UNDERWATER_CAVES->value,
        ]);

        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'health' => 1000,
        ]);

        $monster = $this->createMonster([
            'affix_resistance' => 0.0,
            'life_stealing_resistance' => 0.0,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
        ]);

        $serverMonster = $this->secondaryAttacksFactory->buildServerMonster($monster->toArray());
        $secondaryAttacks = $this->secondaryAttacksFactory->buildSecondaryAttacks();
        $secondaryAttacks->setCharacterHealth(250);
        $secondaryAttacks->setMonsterHealth(1000);
        $secondaryAttacks->setAttackData([
            'attack_type' => AttackType::ATTACK->value,
            'damage_deduction' => 0.0,
            'affixes' => [
                'cant_be_resisted' => true,
                'stacking_life_stealing' => 0.75,
                'life_stealing' => 0.0,
            ],
            'weapon_damage' => 100,
            'special_damage' => [],
            'ring_damage' => 0,
        ]);

        $secondaryAttacks->affixLifeStealingDamage($character, $serverMonster);

        $this->assertEquals(250, $secondaryAttacks->getMonsterHealth());
        $this->assertEquals(1000, $secondaryAttacks->getCharacterHealth());
    }

    public function test_monster_with_zero_life_stealing_resistance_does_not_add_resistance_message(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'health' => 1000,
        ]);

        $monster = $this->createMonster([
            'affix_resistance' => 0.0,
            'life_stealing_resistance' => 0.0,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
        ]);

        $serverMonster = $this->secondaryAttacksFactory->buildServerMonster($monster->toArray());
        $secondaryAttacks = $this->secondaryAttacksFactory->buildSecondaryAttacks();
        $secondaryAttacks->setCharacterHealth(500);
        $secondaryAttacks->setMonsterHealth(1000);
        $secondaryAttacks->setAttackData([
            'attack_type' => AttackType::ATTACK->value,
            'damage_deduction' => 0.0,
            'affixes' => [
                'cant_be_resisted' => true,
                'stacking_life_stealing' => 0.0,
                'life_stealing' => 0.5,
            ],
            'weapon_damage' => 100,
            'special_damage' => [],
            'ring_damage' => 0,
        ]);

        $secondaryAttacks->affixLifeStealingDamage($character, $serverMonster);

        $this->assertEquals(500, $secondaryAttacks->getMonsterHealth());
        $this->assertEquals(1000, $secondaryAttacks->getCharacterHealth());
        $this->assertNotContains([
            'message' => 'The enemy resisted your attempt to steal 50.00% of their health and instead you stole 25.00%, dealing 250 damage.',
            'type' => 'enemy-action',
        ], $secondaryAttacks->getMessages());
    }

    public function test_player_elemental_damage_does_not_fire_when_monster_has_no_atonement(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'health' => 1000,
            'elemental_atonement' => [
                'atonements' => [
                    'fire' => 0.5,
                    'ice' => 0,
                    'water' => 0,
                ],
            ],
            'weapon_attack' => 100,
            'spell_attack' => 100,
        ]);

        $monster = $this->createMonster([
            'fire_atonement' => null,
            'ice_atonement' => null,
            'water_atonement' => null,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
        ]);

        $serverMonster = $this->secondaryAttacksFactory->buildServerMonster($monster->toArray());
        $secondaryAttacks = $this->secondaryAttacksFactory->buildSecondaryAttacks();
        $secondaryAttacks->setCharacterHealth(1000);
        $secondaryAttacks->setMonsterHealth(1000);
        $secondaryAttacks->setAttackData([
            'attack_type' => AttackType::ATTACK->value,
            'damage_deduction' => 0.0,
            'affixes' => [],
            'weapon_damage' => 100,
            'special_damage' => [],
            'ring_damage' => 0,
        ]);

        $secondaryAttacks->dealElementalDamage($character, $serverMonster, true);

        $this->assertEquals(1000, $secondaryAttacks->getMonsterHealth());
        $this->assertEmpty($secondaryAttacks->getMessages());
    }

    public function test_player_elemental_damage_does_not_fire_when_character_has_no_elemental_atonement_cached(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'health' => 1000,
            'elemental_atonement' => null,
        ]);

        $monster = $this->createMonster([
            'fire_atonement' => 0.5,
            'ice_atonement' => 0,
            'water_atonement' => 0,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
        ]);

        $serverMonster = $this->secondaryAttacksFactory->buildServerMonster($monster->toArray());
        $secondaryAttacks = $this->secondaryAttacksFactory->buildSecondaryAttacks();
        $secondaryAttacks->setCharacterHealth(1000);
        $secondaryAttacks->setMonsterHealth(1000);
        $secondaryAttacks->setAttackData([
            'attack_type' => AttackType::ATTACK->value,
            'damage_deduction' => 0.0,
            'affixes' => [],
            'weapon_damage' => 100,
            'special_damage' => [],
            'ring_damage' => 0,
        ]);

        $secondaryAttacks->dealElementalDamage($character, $serverMonster, true);

        $this->assertEquals(1000, $secondaryAttacks->getMonsterHealth());
        $this->assertEmpty($secondaryAttacks->getMessages());
    }

    public function test_player_elemental_damage_uses_correct_damage_source_for_attack_types(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'health' => 1000,
            'elemental_atonement' => [
                'atonements' => [
                    'fire' => 0.5,
                    'ice' => 0,
                    'water' => 0,
                ],
            ],
            'weapon_attack' => 200,
            'spell_attack' => 100,
        ]);

        $monster = $this->createMonster([
            'fire_atonement' => 0.5,
            'ice_atonement' => 0,
            'water_atonement' => 0,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
        ]);

        $serverMonster = $this->secondaryAttacksFactory->buildServerMonster($monster->toArray());
        $secondaryAttacks = $this->secondaryAttacksFactory->buildSecondaryAttacks();
        $secondaryAttacks->setCharacterHealth(1000);
        $secondaryAttacks->setMonsterHealth(1000);
        $secondaryAttacks->setAttackData([
            'attack_type' => AttackType::ATTACK->value,
            'damage_deduction' => 0.0,
            'affixes' => [],
            'weapon_damage' => 200,
            'special_damage' => [],
            'ring_damage' => 0,
        ]);
        $secondaryAttacks->dealElementalDamage($character, $serverMonster, true);

        $this->assertEquals(950, $secondaryAttacks->getMonsterHealth());

        $secondaryAttacks = $this->secondaryAttacksFactory->buildSecondaryAttacks();
        $secondaryAttacks->setCharacterHealth(1000);
        $secondaryAttacks->setMonsterHealth(1000);
        $secondaryAttacks->setAttackData([
            'attack_type' => AttackType::CAST->value,
            'damage_deduction' => 0.0,
            'affixes' => [],
            'spell_damage' => 100,
            'special_damage' => [],
            'ring_damage' => 0,
        ]);
        $secondaryAttacks->dealElementalDamage($character, $serverMonster, true);

        $this->assertEquals(975, $secondaryAttacks->getMonsterHealth());

        $secondaryAttacks = $this->secondaryAttacksFactory->buildSecondaryAttacks();
        $secondaryAttacks->setCharacterHealth(1000);
        $secondaryAttacks->setMonsterHealth(1000);
        $secondaryAttacks->setAttackData([
            'attack_type' => AttackType::ATTACK_AND_CAST->value,
            'damage_deduction' => 0.0,
            'affixes' => [],
            'weapon_damage' => 200,
            'spell_damage' => 100,
            'special_damage' => [],
            'ring_damage' => 0,
        ]);
        $secondaryAttacks->dealElementalDamage($character, $serverMonster, true);

        $this->assertEquals(950, $secondaryAttacks->getMonsterHealth());

        $secondaryAttacks = $this->secondaryAttacksFactory->buildSecondaryAttacks();
        $secondaryAttacks->setCharacterHealth(1000);
        $secondaryAttacks->setMonsterHealth(1000);
        $secondaryAttacks->setAttackData([
            'attack_type' => AttackType::CAST_AND_ATTACK->value,
            'damage_deduction' => 0.0,
            'affixes' => [],
            'weapon_damage' => 200,
            'spell_damage' => 100,
            'special_damage' => [],
            'ring_damage' => 0,
        ]);
        $secondaryAttacks->dealElementalDamage($character, $serverMonster, true);

        $this->assertEquals(975, $secondaryAttacks->getMonsterHealth());
    }

    public function test_player_elemental_damage_does_not_fire_when_defending(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'health' => 1000,
            'elemental_atonement' => [
                'atonements' => [
                    'fire' => 0.5,
                    'ice' => 0,
                    'water' => 0,
                ],
            ],
            'weapon_attack' => 200,
            'spell_attack' => 100,
        ]);

        $monster = $this->createMonster([
            'fire_atonement' => 0.5,
            'ice_atonement' => 0,
            'water_atonement' => 0,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
        ]);

        $serverMonster = $this->secondaryAttacksFactory->buildServerMonster($monster->toArray());
        $secondaryAttacks = $this->secondaryAttacksFactory->buildSecondaryAttacks();
        $secondaryAttacks->setCharacterHealth(1000);
        $secondaryAttacks->setMonsterHealth(1000);
        $secondaryAttacks->setAttackData([
            'attack_type' => AttackType::DEFEND->value,
            'damage_deduction' => 0.0,
            'affixes' => [],
            'special_damage' => [],
            'ring_damage' => 0,
        ]);

        $secondaryAttacks->dealElementalDamage($character, $serverMonster, true);

        $this->assertEquals(1000, $secondaryAttacks->getMonsterHealth());
        $this->assertEmpty($secondaryAttacks->getMessages());
    }

    public function test_affix_life_stealing_damage_does_nothing_when_monster_health_is_not_positive(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $secondaryAttacks = $this->secondaryAttacksFactory->buildSecondaryAttacks();
        $secondaryAttacks->setCharacterHealth(500);
        $secondaryAttacks->setMonsterHealth(0);
        $secondaryAttacks->setAttackData([
            'affixes' => ['life_stealing' => 0.5, 'cant_be_resisted' => false],
        ]);

        $secondaryAttacks->affixLifeStealingDamage($character);

        $this->assertEquals(500, $secondaryAttacks->getCharacterHealth());
        $this->assertEquals(0, $secondaryAttacks->getMonsterHealth());
    }

    public function test_do_secondary_attack_skips_rings_and_affixes_when_voided(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'health' => 1000,
        ]);

        $monster = $this->createMonster([
            'affix_resistance' => 0.0,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
        ]);
        $serverMonster = $this->secondaryAttacksFactory->buildServerMonster($monster->toArray());

        $secondaryAttacks = $this->secondaryAttacksFactory->buildSecondaryAttacks();
        $secondaryAttacks->setCharacterHealth(1000);
        $secondaryAttacks->setMonsterHealth(1000);
        $secondaryAttacks->setIsCharacterVoided(true);
        $secondaryAttacks->setAttackData([
            'attack_type' => AttackType::DEFEND->value,
            'damage_deduction' => 0.0,
            'affixes' => [],
            'special_damage' => [],
            'ring_damage' => 0,
        ]);

        $secondaryAttacks->doSecondaryAttack($character, $serverMonster);

        $this->assertEquals(1000, $secondaryAttacks->getMonsterHealth());
        $this->assertContains([
            'message' => 'You are voided, none of your rings or enchantments fire ...',
            'type' => 'enemy-action',
        ], $secondaryAttacks->getMessages());
    }

    public function test_do_secondary_attack_resets_affix_reduction_when_enemy_is_entranced(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'health' => 1000,
        ]);

        $monster = $this->createMonster([
            'affix_resistance' => 0.0,
            'life_stealing_resistance' => 0.0,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
        ]);
        $serverMonster = $this->secondaryAttacksFactory->buildServerMonster($monster->toArray());

        $secondaryAttacks = $this->secondaryAttacksFactory->buildSecondaryAttacks();
        $secondaryAttacks->setCharacterHealth(1000);
        $secondaryAttacks->setMonsterHealth(1000);
        $secondaryAttacks->setIsCharacterVoided(false);
        $secondaryAttacks->setIsEnemyEntranced(true);
        $secondaryAttacks->setAttackData([
            'attack_type' => AttackType::DEFEND->value,
            'damage_deduction' => 0.0,
            'affixes' => [
                'cant_be_resisted' => false,
                'stacking_life_stealing' => 0.0,
                'life_stealing' => 0.0,
            ],
            'special_damage' => [],
            'ring_damage' => 0,
        ]);

        $secondaryAttacks->doSecondaryAttack($character, $serverMonster, 0.5);

        $this->assertEquals(1000, $secondaryAttacks->getMonsterHealth());
    }

    public function test_do_secondary_attack_runs_the_normal_non_entranced_path(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        Cache::put('character-sheet-'.$character->id, [
            'level' => $character->level,
            'health' => 1000,
        ]);

        $monster = $this->createMonster([
            'affix_resistance' => 0.0,
            'life_stealing_resistance' => 0.0,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
        ]);
        $serverMonster = $this->secondaryAttacksFactory->buildServerMonster($monster->toArray());

        $secondaryAttacks = $this->secondaryAttacksFactory->buildSecondaryAttacks();
        $secondaryAttacks->setCharacterHealth(1000);
        $secondaryAttacks->setMonsterHealth(1000);
        $secondaryAttacks->setIsCharacterVoided(false);
        $secondaryAttacks->setIsEnemyEntranced(false);
        $secondaryAttacks->setAttackData([
            'attack_type' => AttackType::DEFEND->value,
            'damage_deduction' => 0.0,
            'affixes' => [
                'cant_be_resisted' => false,
                'stacking_life_stealing' => 0.0,
                'life_stealing' => 0.0,
            ],
            'special_damage' => [],
            'ring_damage' => 0,
        ]);

        $secondaryAttacks->doSecondaryAttack($character, $serverMonster);

        $this->assertEquals(1000, $secondaryAttacks->getMonsterHealth());
    }

    public function test_class_specialty_damage_does_nothing_when_no_special_is_present(): void
    {
        $secondaryAttacks = $this->secondaryAttacksFactory->buildSecondaryAttacks();
        $secondaryAttacks->setMonsterHealth(1000);
        $secondaryAttacks->setAttackData([
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $secondaryAttacks->classSpecialtyDamage();

        $this->assertEquals(1000, $secondaryAttacks->getMonsterHealth());
        $this->assertEmpty($secondaryAttacks->getMessages());
    }

    public function test_class_specialty_damage_fires_when_the_attack_type_matches(): void
    {
        $secondaryAttacks = $this->secondaryAttacksFactory->buildSecondaryAttacks();
        $secondaryAttacks->setMonsterHealth(1000);
        $secondaryAttacks->setAttackData([
            'attack_type' => AttackType::ATTACK->value,
            'special_damage' => [
                'required_attack_type' => AttackType::ATTACK->value,
                'damage' => 100,
                'name' => 'Test Special',
            ],
        ]);

        $secondaryAttacks->classSpecialtyDamage();

        $this->assertEquals(900, $secondaryAttacks->getMonsterHealth());
        $this->assertContains([
            'message' => 'Your class special: Test Special fires off and you do: 100 damage to the enemy!',
            'type' => 'player-action',
        ], $secondaryAttacks->getMessages());
    }

    public function test_class_specialty_damage_does_nothing_when_the_attack_type_does_not_match(): void
    {
        $secondaryAttacks = $this->secondaryAttacksFactory->buildSecondaryAttacks();
        $secondaryAttacks->setMonsterHealth(1000);
        $secondaryAttacks->setAttackData([
            'attack_type' => AttackType::CAST->value,
            'special_damage' => [
                'required_attack_type' => AttackType::ATTACK->value,
                'damage' => 100,
                'name' => 'Test Special',
            ],
        ]);

        $secondaryAttacks->classSpecialtyDamage();

        $this->assertEquals(1000, $secondaryAttacks->getMonsterHealth());
    }

    public function test_affix_damage_deals_damage_without_a_monster(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $secondaryAttacks = $this->secondaryAttacksFactory->buildSecondaryAttacks();
        $secondaryAttacks->setMonsterHealth(1000);
        $secondaryAttacks->setAttackData([
            'attack_type' => AttackType::ATTACK->value,
            'weapon_damage' => 100,
            'damage_deduction' => 0.0,
            'affixes' => [
                'stacking_damage' => 0.5,
                'non_stacking_damage' => 0.5,
                'cant_be_resisted' => true,
            ],
        ]);

        $secondaryAttacks->affixDamage($character, null);

        $this->assertEquals(850, $secondaryAttacks->getMonsterHealth());
    }

    public function test_affix_damage_uses_monster_affix_resistance_and_skips_when_damage_is_not_positive(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $monster = $this->createMonster([
            'affix_resistance' => 0.0,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
        ]);
        $serverMonster = $this->secondaryAttacksFactory->buildServerMonster($monster->toArray());

        $secondaryAttacks = $this->secondaryAttacksFactory->buildSecondaryAttacks();
        $secondaryAttacks->setMonsterHealth(1000);
        $secondaryAttacks->setAttackData([
            'attack_type' => AttackType::ATTACK->value,
            'weapon_damage' => 100,
            'damage_deduction' => 0.0,
            'affixes' => [
                'stacking_damage' => 0.0,
                'non_stacking_damage' => 0.0,
                'cant_be_resisted' => true,
            ],
        ]);

        $secondaryAttacks->affixDamage($character, $serverMonster);

        $this->assertEquals(1000, $secondaryAttacks->getMonsterHealth());
    }

    public function test_ring_damage_reduces_monster_health_when_positive(): void
    {
        $secondaryAttacks = $this->secondaryAttacksFactory->buildSecondaryAttacks();
        $secondaryAttacks->setMonsterHealth(1000);
        $secondaryAttacks->setAttackData([
            'ring_damage' => 100,
            'damage_deduction' => 0.0,
        ]);

        $secondaryAttacks->ringDamage();

        $this->assertEquals(900, $secondaryAttacks->getMonsterHealth());
        $this->assertContains([
            'message' => 'Your rings hit for: 100',
            'type' => 'player-action',
        ], $secondaryAttacks->getMessages());
    }

    public function test_ring_damage_does_nothing_when_ring_damage_is_not_positive(): void
    {
        $secondaryAttacks = $this->secondaryAttacksFactory->buildSecondaryAttacks();
        $secondaryAttacks->setMonsterHealth(1000);
        $secondaryAttacks->setAttackData([
            'ring_damage' => 0,
            'damage_deduction' => 0.0,
        ]);

        $secondaryAttacks->ringDamage();

        $this->assertEquals(1000, $secondaryAttacks->getMonsterHealth());
        $this->assertEmpty($secondaryAttacks->getMessages());
    }

    public function test_deal_elemental_damage_does_nothing_when_not_allowed(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $secondaryAttacks = $this->secondaryAttacksFactory->buildSecondaryAttacks();
        $secondaryAttacks->setMonsterHealth(1000);
        $secondaryAttacks->setAttackData([
            'attack_type' => AttackType::ATTACK->value,
        ]);

        $secondaryAttacks->dealElementalDamage($character, null, false);

        $this->assertEquals(1000, $secondaryAttacks->getMonsterHealth());
    }
}
