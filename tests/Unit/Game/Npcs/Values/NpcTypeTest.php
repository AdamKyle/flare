<?php

namespace Tests\Unit\Game\Npcs\Values;

use App\Game\Npcs\Values\NpcType;
use Tests\TestCase;

class NpcTypeTest extends TestCase
{
    public function test_label_returns_kingdom_holder_for_kingdom_holder_type(): void
    {
        $this->assertSame('Kingdom Holder', NpcType::KINGDOM_HOLDER->label());
    }

    public function test_label_returns_summoner_for_summoner_type(): void
    {
        $this->assertSame('Summoner', NpcType::SUMMONER->label());
    }

    public function test_label_returns_quest_giver_for_quest_giver_type(): void
    {
        $this->assertSame('Quest Giver', NpcType::QUEST_GIVER->label());
    }

    public function test_label_returns_special_enchantments_for_special_enchants_type(): void
    {
        $this->assertSame('Special Enchantments', NpcType::SPECIAL_ENCHANTS->label());
    }

    public function test_get_named_value_matches_label(): void
    {
        $this->assertSame('Summoner', NpcType::SUMMONER->getNamedValue());
    }

    public function test_get_named_values_maps_every_case_to_its_label(): void
    {
        $namedValues = NpcType::getNamedValues();

        $this->assertSame([
            NpcType::KINGDOM_HOLDER->value => 'Kingdom Holder',
            NpcType::SUMMONER->value => 'Summoner',
            NpcType::QUEST_GIVER->value => 'Quest Giver',
            NpcType::SPECIAL_ENCHANTS->value => 'Special Enchantments',
        ], $namedValues);
    }

    public function test_is_kingdom_holder_is_true_for_kingdom_holder_type(): void
    {
        $this->assertTrue(NpcType::KINGDOM_HOLDER->isKingdomHolder());
    }

    public function test_is_kingdom_holder_is_false_for_non_kingdom_holder_type(): void
    {
        $this->assertFalse(NpcType::SUMMONER->isKingdomHolder());
    }

    public function test_is_quest_holder_is_true_for_quest_giver_type(): void
    {
        $this->assertTrue(NpcType::QUEST_GIVER->isQuestHolder());
    }

    public function test_is_quest_holder_is_false_for_non_quest_giver_type(): void
    {
        $this->assertFalse(NpcType::KINGDOM_HOLDER->isQuestHolder());
    }

    public function test_is_conjurer_is_true_for_summoner_type(): void
    {
        $this->assertTrue(NpcType::SUMMONER->isConjurer());
    }

    public function test_is_conjurer_is_false_for_non_summoner_type(): void
    {
        $this->assertFalse(NpcType::KINGDOM_HOLDER->isConjurer());
    }

    public function test_is_enchantress_is_true_for_special_enchants_type(): void
    {
        $this->assertTrue(NpcType::SPECIAL_ENCHANTS->isEnchantress());
    }

    public function test_is_enchantress_is_false_for_non_special_enchants_type(): void
    {
        $this->assertFalse(NpcType::KINGDOM_HOLDER->isEnchantress());
    }
}
