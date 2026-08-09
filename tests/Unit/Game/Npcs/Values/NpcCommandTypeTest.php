<?php

namespace Tests\Unit\Game\Npcs\Values;

use App\Game\Npcs\Values\NpcCommandType;
use Tests\TestCase;

class NpcCommandTypeTest extends TestCase
{
    public function test_label_returns_quest_for_quest_type(): void
    {
        $this->assertSame('Quest', NpcCommandType::QUEST->label());
    }

    public function test_label_returns_take_kingdom_for_take_kingdom_type(): void
    {
        $this->assertSame('Take Kingdom', NpcCommandType::TAKE_KINGDOM->label());
    }

    public function test_label_returns_conjure_for_conjure_type(): void
    {
        $this->assertSame('Conjure', NpcCommandType::CONJURE->label());
    }

    public function test_label_returns_re_roll_for_re_roll_type(): void
    {
        $this->assertSame('Re-Roll', NpcCommandType::RE_ROLL->label());
    }

    public function test_get_named_value_matches_label(): void
    {
        $this->assertSame('Conjure', NpcCommandType::CONJURE->getNamedValue());
    }

    public function test_get_named_values_maps_every_case_to_its_label(): void
    {
        $namedValues = NpcCommandType::getNamedValues();

        $this->assertSame([
            NpcCommandType::QUEST->value => 'Quest',
            NpcCommandType::TAKE_KINGDOM->value => 'Take Kingdom',
            NpcCommandType::CONJURE->value => 'Conjure',
            NpcCommandType::RE_ROLL->value => 'Re-Roll',
        ], $namedValues);
    }

    public function test_is_quest_is_true_for_quest_type(): void
    {
        $this->assertTrue(NpcCommandType::QUEST->isQuest());
    }

    public function test_is_quest_is_false_for_non_quest_type(): void
    {
        $this->assertFalse(NpcCommandType::CONJURE->isQuest());
    }

    public function test_is_take_kingdom_is_true_for_take_kingdom_type(): void
    {
        $this->assertTrue(NpcCommandType::TAKE_KINGDOM->isTakeKingdom());
    }

    public function test_is_take_kingdom_is_false_for_non_take_kingdom_type(): void
    {
        $this->assertFalse(NpcCommandType::QUEST->isTakeKingdom());
    }

    public function test_is_conjure_is_true_for_conjure_type(): void
    {
        $this->assertTrue(NpcCommandType::CONJURE->isConjure());
    }

    public function test_is_conjure_is_false_for_non_conjure_type(): void
    {
        $this->assertFalse(NpcCommandType::QUEST->isConjure());
    }

    public function test_is_re_roll_is_true_for_re_roll_type(): void
    {
        $this->assertTrue(NpcCommandType::RE_ROLL->isReRoll());
    }

    public function test_is_re_roll_is_false_for_non_re_roll_type(): void
    {
        $this->assertFalse(NpcCommandType::QUEST->isReRoll());
    }
}
