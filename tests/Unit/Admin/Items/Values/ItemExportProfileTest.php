<?php

namespace Tests\Unit\Admin\Items\Values;

use App\Admin\Items\Values\ItemExportProfile;
use App\Game\Core\Items\Values\ArmourType;
use App\Game\Core\Items\Values\ItemSpecialtyType;
use Tests\TestCase;

class ItemExportProfileTest extends TestCase
{
    public function test_weapons_profile_resolves_the_exact_1_0_weapon_family(): void
    {
        $familyValues = ItemExportProfile::WEAPONS->familyValues();

        $this->assertSame(
            ['weapon', 'bow', 'hammer', 'stave', 'gun', 'fan', 'scratch-awl', 'mace'],
            $familyValues
        );
    }

    public function test_armour_profile_resolves_the_armour_type_family(): void
    {
        $this->assertSame(ArmourType::allTypes(), ItemExportProfile::ARMOUR->familyValues());
    }

    public function test_spells_profile_contains_both_damage_and_healing_spell_types(): void
    {
        $familyValues = ItemExportProfile::SPELLS->familyValues();

        $this->assertContains('spell-damage', $familyValues);
        $this->assertContains('spell-healing', $familyValues);
        $this->assertCount(2, $familyValues);
    }

    public function test_quest_profile_resolves_the_quest_family(): void
    {
        $this->assertSame(['quest'], ItemExportProfile::QUEST->familyValues());
    }

    public function test_alchemy_profile_resolves_the_alchemy_family(): void
    {
        $this->assertSame(['alchemy'], ItemExportProfile::ALCHEMY->familyValues());
    }

    public function test_trinket_profile_resolves_the_trinket_family(): void
    {
        $this->assertSame(['trinket'], ItemExportProfile::TRINKET->familyValues());
    }

    public function test_artifact_profile_resolves_the_artifact_family(): void
    {
        $this->assertSame(['artifact'], ItemExportProfile::ARTIFACT->familyValues());
    }

    public function test_specialty_shops_profile_resolves_every_item_specialty_type(): void
    {
        $expected = array_map(fn (ItemSpecialtyType $type): string => $type->value, ItemSpecialtyType::cases());

        $this->assertSame($expected, ItemExportProfile::SPECIALTY_SHOPS->familyValues());
    }
}
