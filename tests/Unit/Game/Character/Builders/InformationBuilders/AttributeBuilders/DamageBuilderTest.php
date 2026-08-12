<?php

namespace Tests\Unit\Game\Character\Builders\InformationBuilders\AttributeBuilders;

use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\DamageBuilder;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use App\Game\Character\Values\CharacterClass;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class DamageBuilderTest extends TestCase
{
    use CreateClass, CreateGameSkill, CreateGem, CreateItem, CreateItemAffix, RefreshDatabase;

    private ?CharacterStatBuilder $characterStatBuilder;

    private ?DamageBuilder $damageBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->characterStatBuilder = resolve(CharacterStatBuilder::class);

        $this->damageBuilder = resolve(DamageBuilder::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->characterStatBuilder = null;
        $this->damageBuilder = null;
    }

    public function test_arcane_alchemist_does_more_damage_with_stave()
    {
        $arcaneAlchemist = (new CharacterFactory)->createBaseCharacter([], $this->createClass([
            'name' => CharacterClass::ARCANE_ALCHEMIST->value,
            'damage_stat' => 'str',
        ]))->assignSkill(
            $this->createGameSkill([
                'class_bonus' => 0.01,
            ]),
            5
        )->givePlayerLocation()
            ->levelCharacterUp(10)
            ->inventoryManagement()
            ->giveItem(
                $this->createItem(['type' => 'stave', 'base_damage' => 100]),
                true,
                'left-hand'
            )
            ->getCharacter();

        $prophet = (new CharacterFactory)->createBaseCharacter([], $this->createClass([
            'name' => CharacterClass::PROPHET->value,
            'damage_stat' => 'str',
        ]))->assignSkill(
            $this->createGameSkill([
                'class_bonus' => 0.01,
            ]),
            5
        )->givePlayerLocation()
            ->levelCharacterUp(10)
            ->inventoryManagement()
            ->giveItem(
                $this->createItem(['type' => 'stave', 'base_damage' => 100]),
                true,
                'left-hand'
            )
            ->getCharacter();

        $prophetEquipped = $this->characterStatBuilder->fetchEquipped($prophet);

        $this->damageBuilder->initialize($prophet, $prophet->skills, $prophetEquipped);

        $prophetDamage = $this->damageBuilder->buildWeaponDamage($this->characterStatBuilder->setCharacter($prophet)->statMod('chr'));

        $arcaneEquipped = $this->characterStatBuilder->fetchEquipped($arcaneAlchemist);

        $this->damageBuilder->initialize($arcaneAlchemist, $arcaneAlchemist->skills, $arcaneEquipped);

        $arcaneDamage = $this->damageBuilder->buildWeaponDamage($this->characterStatBuilder->setCharacter($arcaneAlchemist)->statMod('chr'));

        $this->assertGreaterThan($prophetDamage, $arcaneDamage);
    }

    public function test_build_weapon_damage_uses_default_bonus_for_unmatched_class(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::VAMPIRE->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->damageBuilder->initialize($character, $character->skills, $equipped);
        $damage = $this->damageBuilder->buildWeaponDamage($this->characterStatBuilder->setCharacter($character)->statMod('chr'));

        $this->assertGreaterThan(0, $damage);
    }

    public function test_build_weapon_damage_gives_thief_bonus_for_dual_handed_bow(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::THIEF->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'bow', 'base_damage' => 10]), true, 'left-hand')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->damageBuilder->initialize($character, $character->skills, $equipped);
        $damage = $this->damageBuilder->buildWeaponDamage($this->characterStatBuilder->setCharacter($character)->statMod('chr'));

        $this->assertGreaterThan(0, $damage);
    }

    public function test_build_weapon_damage_gives_thief_bonus_for_two_daggers(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::THIEF->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'dagger', 'base_damage' => 10]), true, 'left-hand')
            ->giveItem($this->createItem(['type' => 'dagger', 'base_damage' => 10]), true, 'right-hand')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->damageBuilder->initialize($character, $character->skills, $equipped);
        $damage = $this->damageBuilder->buildWeaponDamage($this->characterStatBuilder->setCharacter($character)->statMod('chr'));

        $this->assertGreaterThan(0, $damage);
    }

    public function test_build_weapon_damage_gives_merchant_bonus_for_bow(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::MERCHANT->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'bow', 'base_damage' => 10]), true, 'left-hand')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->damageBuilder->initialize($character, $character->skills, $equipped);
        $damage = $this->damageBuilder->buildWeaponDamage($this->characterStatBuilder->setCharacter($character)->statMod('chr'));

        $this->assertGreaterThan(0, $damage);
    }

    public function test_build_weapon_damage_gives_fighter_bonus_for_two_swords(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::FIGHTER->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'sword', 'base_damage' => 10]), true, 'left-hand')
            ->giveItem($this->createItem(['type' => 'sword', 'base_damage' => 10]), true, 'right-hand')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->damageBuilder->initialize($character, $character->skills, $equipped);
        $damage = $this->damageBuilder->buildWeaponDamage($this->characterStatBuilder->setCharacter($character)->statMod('chr'));

        $this->assertGreaterThan(0, $damage);
    }

    public function test_build_weapon_damage_gives_heretic_bonus_for_stave(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::HERETIC->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'stave', 'base_damage' => 10]), true, 'left-hand')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->damageBuilder->initialize($character, $character->skills, $equipped);
        $damage = $this->damageBuilder->buildWeaponDamage($this->characterStatBuilder->setCharacter($character)->statMod('chr'));

        $this->assertGreaterThan(0, $damage);
    }

    public function test_build_weapon_damage_gives_blacksmith_bonus_for_hammer(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::BLACKSMITH->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'hammer', 'base_damage' => 10]), true, 'left-hand')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->damageBuilder->initialize($character, $character->skills, $equipped);
        $damage = $this->damageBuilder->buildWeaponDamage($this->characterStatBuilder->setCharacter($character)->statMod('chr'));

        $this->assertGreaterThan(0, $damage);
    }

    public function test_build_weapon_damage_gives_cleric_bonus_for_mace(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::CLERIC->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'mace', 'base_damage' => 10]), true, 'left-hand')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->damageBuilder->initialize($character, $character->skills, $equipped);
        $damage = $this->damageBuilder->buildWeaponDamage($this->characterStatBuilder->setCharacter($character)->statMod('chr'));

        $this->assertGreaterThan(0, $damage);
    }

    public function test_build_weapon_damage_gives_gunslinger_bonus_for_two_guns(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::GUNSLINGER->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'gun', 'base_damage' => 10]), true, 'left-hand')
            ->giveItem($this->createItem(['type' => 'gun', 'base_damage' => 10]), true, 'right-hand')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->damageBuilder->initialize($character, $character->skills, $equipped);
        $damage = $this->damageBuilder->buildWeaponDamage($this->characterStatBuilder->setCharacter($character)->statMod('chr'));

        $this->assertGreaterThan(0, $damage);
    }

    public function test_build_weapon_damage_gives_dancer_bonus_for_fan(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::DANCER->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'fan', 'base_damage' => 10]), true, 'left-hand')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->damageBuilder->initialize($character, $character->skills, $equipped);
        $damage = $this->damageBuilder->buildWeaponDamage($this->characterStatBuilder->setCharacter($character)->statMod('chr'));

        $this->assertGreaterThan(0, $damage);
    }

    public function test_build_weapon_damage_gives_book_binder_bonus_for_scratch_awl(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::BOOK_BINDER->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'scratch-awl', 'base_damage' => 10]), true, 'left-hand')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->damageBuilder->initialize($character, $character->skills, $equipped);
        $damage = $this->damageBuilder->buildWeaponDamage($this->characterStatBuilder->setCharacter($character)->statMod('chr'));

        $this->assertGreaterThan(0, $damage);
    }

    public function test_build_weapon_damage_gives_ranger_bonus_for_bow(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::RANGER->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'bow', 'base_damage' => 10]), true, 'left-hand')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->damageBuilder->initialize($character, $character->skills, $equipped);
        $damage = $this->damageBuilder->buildWeaponDamage($this->characterStatBuilder->setCharacter($character)->statMod('chr'));

        $this->assertGreaterThan(0, $damage);
    }

    public function test_build_weapon_damage_voided_excludes_affix_and_mastery_bonuses(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::FIGHTER->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'sword', 'base_damage' => 50]), true, 'left-hand')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->damageBuilder->initialize($character, $character->skills, $equipped);

        $damage = $this->damageBuilder->buildWeaponDamage($this->characterStatBuilder->setCharacter($character)->statMod('chr'), true);

        $this->assertGreaterThan(0, $damage);
    }

    public function test_build_weapon_damage_reduces_damage_for_alcoholic_with_item_damage(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::ALCOHOLIC->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'sword', 'base_damage' => 100]), true, 'left-hand')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->damageBuilder->initialize($character, $character->skills, $equipped);
        $damage = $this->damageBuilder->buildWeaponDamage($this->characterStatBuilder->setCharacter($character)->statMod('chr'));

        $this->assertGreaterThan(0, $damage);
    }

    public function test_build_weapon_damage_clamps_low_damage_to_minimum(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::VAMPIRE->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->getCharacter();

        $character->update(['str' => 0]);
        $character = $character->fresh();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->damageBuilder->initialize($character, $character->skills, $equipped);
        $damage = $this->damageBuilder->buildWeaponDamage($this->characterStatBuilder->setCharacter($character)->statMod('chr'));

        $this->assertSame(8.0, $damage);
    }

    public function test_build_weapon_damage_break_down_for_fighter(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::FIGHTER->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->damageBuilder->initialize($character, $character->skills, $equipped);

        $details = $this->damageBuilder->buildWeaponDamageBreakDown(100, false);

        $this->assertSame(0.08, $details['percentage_of_stat_used']);
        $this->assertIsNotString($details['base_damage']);
    }

    public function test_build_weapon_damage_break_down_for_arcane_alchemist_with_stave(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::ARCANE_ALCHEMIST->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'stave', 'base_damage' => 10]), true, 'left-hand')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->damageBuilder->initialize($character, $character->skills, $equipped);

        $details = $this->damageBuilder->buildWeaponDamageBreakDown(100, false);

        $this->assertSame(0.15, $details['percentage_of_stat_used']);
    }

    public function test_build_weapon_damage_break_down_for_arcane_alchemist_without_stave(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::ARCANE_ALCHEMIST->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->damageBuilder->initialize($character, $character->skills, $equipped);

        $details = $this->damageBuilder->buildWeaponDamageBreakDown(100, false);

        $this->assertSame(0.05, $details['percentage_of_stat_used']);
    }

    public function test_build_weapon_damage_break_down_for_default_class(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::VAMPIRE->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->damageBuilder->initialize($character, $character->skills, $equipped);

        $details = $this->damageBuilder->buildWeaponDamageBreakDown(100, false);

        $this->assertSame(0.05, $details['percentage_of_stat_used']);
    }

    public function test_build_weapon_damage_break_down_includes_masteries_for_equipped_weapons(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::FIGHTER->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->createClassRanks()
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'sword', 'base_damage' => 10]), true, 'left-hand')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->damageBuilder->initialize($character, $character->skills, $equipped);

        $details = $this->damageBuilder->buildWeaponDamageBreakDown(100, false);

        $this->assertIsArray($details['masteries']);
    }

    public function test_build_weapon_damage_break_down_returns_early_when_inventory_is_null(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::FIGHTER->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->getCharacter();

        $this->damageBuilder->initialize($character, $character->skills, null);

        $details = $this->damageBuilder->buildWeaponDamageBreakDown(100, false);

        $this->assertSame([], $details['masteries']);
    }

    public function test_build_ring_damage_sums_ring_base_damage(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::FIGHTER->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'ring', 'base_damage' => 15]), true, 'ring-one')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->damageBuilder->initialize($character, $character->skills, $equipped);

        $this->assertSame(15, $this->damageBuilder->buildRingDamage());
    }

    public function test_build_spell_damage_voided_excludes_affix_and_mastery_bonuses(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::HERETIC->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'spell-damage', 'base_damage' => 20]), true, 'spell-one')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->damageBuilder->initialize($character, $character->skills, $equipped);

        $damage = $this->damageBuilder->buildSpellDamage(true);

        $this->assertSame(20.0, $damage);
    }

    public function test_build_spell_damage_includes_affix_and_mastery_bonuses(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::HERETIC->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'spell-damage', 'base_damage' => 20]), true, 'spell-one')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->damageBuilder->initialize($character, $character->skills, $equipped);

        $damage = $this->damageBuilder->buildSpellDamage(false);

        $this->assertGreaterThanOrEqual(20.0, $damage);
    }

    public function test_build_spell_damage_break_down_details(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::HERETIC->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'spell-damage', 'base_damage' => 20]), true, 'spell-one')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->damageBuilder->initialize($character, $character->skills, $equipped);

        $details = $this->damageBuilder->buildSpellDamageBreakDownDetails(false);

        $this->assertSame(20, $details['base_damage']);
        $this->assertIsArray($details['masteries']);
    }

    public function test_build_ring_damage_break_down_returns_all_keys(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::FIGHTER->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'ring', 'base_damage' => 15]), true, 'ring-one')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->damageBuilder->initialize($character, $character->skills, $equipped);

        $details = $this->damageBuilder->buildRingDamageBreakDown();

        $this->assertSame(15, $details['base_damage']);
        $this->assertArrayHasKey('attached_affixes', $details);
        $this->assertArrayHasKey('spell_evasion', $details);
    }

    public function test_build_ring_damage_break_down_attached_affixes_excludes_non_ring_items(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::FIGHTER->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'ring', 'base_damage' => 15]), true, 'ring-one')
            ->giveItem($this->createItem(['type' => 'sword', 'base_damage' => 10]), true, 'right-hand')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->damageBuilder->initialize($character, $character->skills, $equipped);

        $details = $this->damageBuilder->buildRingDamageBreakDown();

        $this->assertCount(1, $details['attached_affixes']);
    }

    public function test_build_ring_damage_break_down_attached_affixes_includes_prefix_and_suffix_details(): void
    {
        $prefix = $this->createItemAffix(['type' => 'prefix', 'base_damage_mod' => 0.10]);
        $suffix = $this->createItemAffix(['type' => 'suffix', 'base_damage_mod' => 0.05]);

        $ring = $this->createItem([
            'type' => 'ring',
            'base_damage' => 15,
            'item_prefix_id' => $prefix->id,
            'item_suffix_id' => $suffix->id,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::FIGHTER->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->inventoryManagement()
            ->giveItem($ring, true, 'ring-one')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->damageBuilder->initialize($character, $character->skills, $equipped);

        $details = $this->damageBuilder->buildRingDamageBreakDown();

        $affixNames = array_column($details['attached_affixes'][0]['affixes'], 'name');

        $this->assertContains($prefix->name, $affixNames);
        $this->assertContains($suffix->name, $affixNames);
    }

    public function test_build_affix_stacking_damage_returns_zero_when_voided(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::FIGHTER->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'sword', 'base_damage' => 10]), true, 'left-hand')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->damageBuilder->initialize($character, $character->skills, $equipped);

        $this->assertSame(0.0, $this->damageBuilder->buildAffixStackingDamage(true));
    }

    public function test_build_affix_stacking_damage_returns_zero_when_inventory_is_null(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::FIGHTER->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->getCharacter();

        $this->damageBuilder->initialize($character, $character->skills, null);

        $this->assertSame(0.0, $this->damageBuilder->buildAffixStackingDamage(false));
    }

    public function test_build_affix_stacking_damage_sums_stacking_affix_damage(): void
    {
        $suffix = $this->createItemAffix(['damage_can_stack' => true, 'damage_amount' => 5]);

        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::FIGHTER->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->inventoryManagement()
            ->giveItem($this->createItem([
                'type' => 'sword', 'base_damage' => 10, 'item_suffix_id' => $suffix->id,
            ]), true, 'left-hand')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->damageBuilder->initialize($character, $character->skills, $equipped);

        $this->assertSame(5.0, $this->damageBuilder->buildAffixStackingDamage(false));
    }

    public function test_build_affix_non_stacking_damage_returns_zero_when_voided(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::FIGHTER->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'sword', 'base_damage' => 10]), true, 'left-hand')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->damageBuilder->initialize($character, $character->skills, $equipped);

        $this->assertSame(0.0, $this->damageBuilder->buildAffixNonStackingDamage(true));
    }

    public function test_build_affix_non_stacking_damage_returns_zero_when_no_amounts(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::FIGHTER->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'sword', 'base_damage' => 10]), true, 'left-hand')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->damageBuilder->initialize($character, $character->skills, $equipped);

        $this->assertSame(0.0, $this->damageBuilder->buildAffixNonStackingDamage(false));
    }

    public function test_build_affix_non_stacking_damage_returns_max_non_stacking_amount(): void
    {
        $suffix = $this->createItemAffix(['damage_can_stack' => false, 'damage_amount' => 7]);

        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::FIGHTER->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->inventoryManagement()
            ->giveItem($this->createItem([
                'type' => 'sword', 'base_damage' => 10, 'item_suffix_id' => $suffix->id,
            ]), true, 'left-hand')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->damageBuilder->initialize($character, $character->skills, $equipped);

        $this->assertSame(7.0, $this->damageBuilder->buildAffixNonStackingDamage(false));
    }

    public function test_build_life_stealing_damage_returns_zero_when_voided(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::VAMPIRE->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'sword', 'base_damage' => 10]), true, 'left-hand')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->damageBuilder->initialize($character, $character->skills, $equipped);

        $this->assertSame(0.0, $this->damageBuilder->buildLifeStealingDamage(true));
    }

    public function test_build_life_stealing_damage_returns_zero_when_inventory_is_null(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::VAMPIRE->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->getCharacter();

        $this->damageBuilder->initialize($character, $character->skills, null);

        $this->assertSame(0.0, $this->damageBuilder->buildLifeStealingDamage(false));
    }

    public function test_build_life_stealing_damage_clamps_vampire_life_steal_at_075(): void
    {
        $suffix = $this->createItemAffix(['steal_life_amount' => 1.5]);

        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::VAMPIRE->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->inventoryManagement()
            ->giveItem($this->createItem([
                'type' => 'sword', 'base_damage' => 10, 'item_suffix_id' => $suffix->id,
            ]), true, 'left-hand')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->damageBuilder->initialize($character, $character->skills, $equipped);

        $this->assertSame(0.75, $this->damageBuilder->buildLifeStealingDamage(false));
    }

    public function test_build_life_stealing_damage_for_vampire_below_clamp(): void
    {
        $suffix = $this->createItemAffix(['steal_life_amount' => 0.1]);

        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::VAMPIRE->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->inventoryManagement()
            ->giveItem($this->createItem([
                'type' => 'sword', 'base_damage' => 10, 'item_suffix_id' => $suffix->id,
            ]), true, 'left-hand')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->damageBuilder->initialize($character, $character->skills, $equipped);

        $this->assertSame(0.1, $this->damageBuilder->buildLifeStealingDamage(false));
    }

    public function test_build_life_stealing_damage_returns_zero_for_non_vampire_with_no_amounts(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::FIGHTER->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'sword', 'base_damage' => 10]), true, 'left-hand')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->damageBuilder->initialize($character, $character->skills, $equipped);

        $this->assertSame(0.0, $this->damageBuilder->buildLifeStealingDamage(false));
    }

    public function test_build_life_stealing_damage_for_non_vampire_takes_smallest_amount(): void
    {
        $suffix = $this->createItemAffix(['steal_life_amount' => 0.2]);

        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::FIGHTER->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->inventoryManagement()
            ->giveItem($this->createItem([
                'type' => 'sword', 'base_damage' => 10, 'item_suffix_id' => $suffix->id,
            ]), true, 'left-hand')
            ->getCharacter();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->damageBuilder->initialize($character, $character->skills, $equipped);

        $this->assertSame(0.2, $this->damageBuilder->buildLifeStealingDamage(false));
    }

    public function test_life_steal_is_reduced_in_hell(): void
    {
        $suffix = $this->createItemAffix(['steal_life_amount' => 0.4]);

        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::FIGHTER->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->inventoryManagement()
            ->giveItem($this->createItem([
                'type' => 'sword', 'base_damage' => 10, 'item_suffix_id' => $suffix->id,
            ]), true, 'left-hand')
            ->getCharacter();

        $character->map->gameMap->update(['name' => 'Hell']);
        $character = $character->fresh();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->damageBuilder->initialize($character, $character->skills, $equipped);

        $this->assertSame(0.36, round($this->damageBuilder->buildLifeStealingDamage(false), 2));
    }

    public function test_life_steal_is_reduced_in_purgatory(): void
    {
        $suffix = $this->createItemAffix(['steal_life_amount' => 0.4]);

        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::FIGHTER->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->inventoryManagement()
            ->giveItem($this->createItem([
                'type' => 'sword', 'base_damage' => 10, 'item_suffix_id' => $suffix->id,
            ]), true, 'left-hand')
            ->getCharacter();

        $character->map->gameMap->update(['name' => 'Purgatory']);
        $character = $character->fresh();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->damageBuilder->initialize($character, $character->skills, $equipped);

        $this->assertSame(0.32, round($this->damageBuilder->buildLifeStealingDamage(false), 2));
    }

    public function test_life_steal_is_reduced_in_twisted_memories(): void
    {
        $suffix = $this->createItemAffix(['steal_life_amount' => 0.4]);

        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::FIGHTER->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->inventoryManagement()
            ->giveItem($this->createItem([
                'type' => 'sword', 'base_damage' => 10, 'item_suffix_id' => $suffix->id,
            ]), true, 'left-hand')
            ->getCharacter();

        $character->map->gameMap->update(['name' => 'Twisted Memories']);
        $character = $character->fresh();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->damageBuilder->initialize($character, $character->skills, $equipped);

        $this->assertSame(0.3, round($this->damageBuilder->buildLifeStealingDamage(false), 2));
    }

    public function test_life_steal_is_unreduced_on_the_surface(): void
    {
        $suffix = $this->createItemAffix(['steal_life_amount' => 0.4]);

        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::FIGHTER->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10)
            ->inventoryManagement()
            ->giveItem($this->createItem([
                'type' => 'sword', 'base_damage' => 10, 'item_suffix_id' => $suffix->id,
            ]), true, 'left-hand')
            ->getCharacter();

        $character->map->gameMap->update(['name' => 'Surface']);
        $character = $character->fresh();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->damageBuilder->initialize($character, $character->skills, $equipped);

        $this->assertSame(0.4, round($this->damageBuilder->buildLifeStealingDamage(false), 2));
    }

    public function test_life_steal_is_reduced_on_the_ice_plane_with_purgatory_item(): void
    {
        $suffix = $this->createItemAffix(['steal_life_amount' => 0.4]);
        $factory = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::FIGHTER->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10);

        $factory->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'sword', 'base_damage' => 10, 'item_suffix_id' => $suffix->id]), true, 'left-hand')
            ->giveItem($this->createItem(['type' => 'quest', 'effect' => 'purgatory']));

        $character = $factory->getCharacter();
        $character->map->gameMap->update(['name' => 'The Ice Plane']);
        $character = $character->fresh();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->damageBuilder->initialize($character, $character->skills, $equipped);

        $this->assertSame(0.32, round($this->damageBuilder->buildLifeStealingDamage(false), 2));
    }

    public function test_life_steal_is_reduced_on_delusional_memories_with_purgatory_item(): void
    {
        $suffix = $this->createItemAffix(['steal_life_amount' => 0.4]);
        $factory = (new CharacterFactory)->createBaseCharacter([], $this->createClass(['name' => CharacterClass::FIGHTER->value]))
            ->givePlayerLocation()
            ->levelCharacterUp(10);

        $factory->inventoryManagement()
            ->giveItem($this->createItem(['type' => 'sword', 'base_damage' => 10, 'item_suffix_id' => $suffix->id]), true, 'left-hand')
            ->giveItem($this->createItem(['type' => 'quest', 'effect' => 'purgatory']));

        $character = $factory->getCharacter();
        $character->map->gameMap->update(['name' => 'Delusional Memories']);
        $character = $character->fresh();

        $equipped = $this->characterStatBuilder->fetchEquipped($character);
        $this->damageBuilder->initialize($character, $character->skills, $equipped);

        $this->assertSame(0.3, round($this->damageBuilder->buildLifeStealingDamage(false), 2));
    }
}
