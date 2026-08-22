<?php

namespace Tests\Unit\Game\Automation\BatchCrafting\Values;

use App\Game\Automation\BatchCrafting\Enums\CraftSetPosition;
use App\Game\Automation\BatchCrafting\Values\CraftAndEnchantSetPlanEntry;
use Tests\TestCase;

class CraftAndEnchantSetPlanEntryTest extends TestCase
{
    public function test_from_array_builds_an_entry_from_its_persisted_shape(): void
    {
        $entry = CraftAndEnchantSetPlanEntry::fromArray([
            'position' => 'body',
            'item_id' => 5,
            'crafting_type' => 'armour',
            'item_name' => 'Set Body',
            'prefix_id' => 10,
            'suffix_id' => null,
        ]);

        $this->assertSame(CraftSetPosition::BODY, $entry->position);
        $this->assertSame(5, $entry->itemId);
        $this->assertSame('armour', $entry->craftingType);
        $this->assertSame('Set Body', $entry->itemName);
        $this->assertSame(10, $entry->prefixId);
        $this->assertNull($entry->suffixId);
    }

    public function test_to_array_returns_the_persisted_shape(): void
    {
        $entry = new CraftAndEnchantSetPlanEntry(CraftSetPosition::LEFT_HAND, 7, 'weapon', 'Set Sword', null, 12);

        $this->assertSame([
            'position' => 'left_hand',
            'item_id' => 7,
            'crafting_type' => 'weapon',
            'item_name' => 'Set Sword',
            'prefix_id' => null,
            'suffix_id' => 12,
        ], $entry->toArray());
    }

    public function test_has_enchantment_is_true_when_a_prefix_or_suffix_is_selected(): void
    {
        $prefixOnly = new CraftAndEnchantSetPlanEntry(CraftSetPosition::BODY, 1, 'armour', 'Set Body', 10, null);
        $suffixOnly = new CraftAndEnchantSetPlanEntry(CraftSetPosition::BODY, 1, 'armour', 'Set Body', null, 20);

        $this->assertTrue($prefixOnly->hasEnchantment());
        $this->assertTrue($suffixOnly->hasEnchantment());
    }

    public function test_has_enchantment_is_false_without_a_prefix_or_suffix(): void
    {
        $entry = new CraftAndEnchantSetPlanEntry(CraftSetPosition::BODY, 1, 'armour', 'Set Body', null, null);

        $this->assertFalse($entry->hasEnchantment());
    }
}
