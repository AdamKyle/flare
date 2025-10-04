<?php

namespace Tests\Unit\Flare\Items\Values;

use App\Flare\Items\Values\ItemType;
use PHPUnit\Framework\TestCase;

class ItemTypeTest extends TestCase
{
    public function test_valid_weapons(): void
    {
        $valid = ItemType::validWeapons();

        $expected = [
            ItemType::STAVE->value,
            ItemType::BOW->value,
            ItemType::DAGGER->value,
            ItemType::SCRATCH_AWL->value,
            ItemType::MACE->value,
            ItemType::HAMMER->value,
            ItemType::GUN->value,
            ItemType::FAN->value,
            ItemType::WAND->value,
            ItemType::CENSER->value,
            ItemType::CLAW->value,
            ItemType::SWORD->value,
            ItemType::TRINKET->value,
            ItemType::ARTIFACT->value,
        ];

        $this->assertEqualsCanonicalizing($expected, $valid);

        $this->assertNotContains(ItemType::SPELL_DAMAGE->value, $valid);
        $this->assertNotContains(ItemType::SPELL_HEALING->value, $valid);
        $this->assertNotContains(ItemType::RING->value, $valid);
    }

    public function test_all_weapon_types(): void
    {
        $allWeapons = ItemType::allWeaponTypes();

        // Expected: all except ring
        $expected = array_map(fn (ItemType $t) => $t->value, array_filter(
            ItemType::cases(),
            fn (ItemType $t) => $t !== ItemType::RING
        ));

        $this->assertEqualsCanonicalizing($expected, $allWeapons);
        $this->assertNotContains(ItemType::RING->value, $allWeapons);
        // But spells are included:
        $this->assertContains(ItemType::SPELL_DAMAGE->value, $allWeapons);
        $this->assertContains(ItemType::SPELL_HEALING->value, $allWeapons);
    }

    public function test_all_types(): void
    {
        $all = ItemType::allTypes();
        $this->assertEqualsCanonicalizing(
            array_map(fn (ItemType $t) => $t->value, ItemType::cases()),
            $all
        );
    }

    public function test_get_proper_name_for_type(): void
    {
        $this->assertSame('Scratch Awl', ItemType::getProperNameForType('scratch-awl'));
        $this->assertSame('Spell Damage', ItemType::getProperNameForType('spell-damage'));
        $this->assertSame('Ring', ItemType::getProperNameForType('ring'));
    }

    public function test_get_valid_weapons_as_options(): void
    {
        $options = ItemType::getValidWeaponsAsOptions();

        $valid = ItemType::validWeapons();
        $expectedLabels = array_map(
            fn ($type) => ucwords(str_replace('-', ' ', $type)),
            $valid
        );

        $this->assertSame(array_values($valid), array_keys($options));
        $this->assertSame($expectedLabels, array_values($options));

        $this->assertEquals('Scratch Awl', $options['scratch-awl']);
        $this->assertEquals('Sword', $options['sword']);
    }
}
