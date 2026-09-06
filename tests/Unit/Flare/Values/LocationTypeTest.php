<?php

namespace Tests\Unit\Flare\Values;

use App\Game\Maps\Values\LocationType;
use Tests\TestCase;

class LocationTypeTest extends TestCase
{
    public function test_values_returns_all_integer_values(): void
    {
        $this->assertSame([
            0,
            1,
            2,
            3,
            4,
            5,
            6,
            7,
            8,
            9,
            10,
            11,
            12,
            13,
        ], LocationType::values());
    }

    public function test_special_has_expected_value(): void
    {
        $this->assertSame(13, LocationType::SPECIAL->value);
    }

    public function test_get_named_values_returns_integer_labels_for_admin_forms(): void
    {
        $this->assertSame('Purgatory Smiths House', LocationType::PURGATORY_SMITH_HOUSE->label());
        $this->assertSame('Gold Mines', LocationType::getNamedValues()[LocationType::GOLD_MINES->value]);
        $this->assertSame('Special', LocationType::getNamedValues()[LocationType::SPECIAL->value]);
    }

    public function test_type_eleven_is_cave_of_shadows(): void
    {
        $this->assertSame(11, LocationType::CAVE_OF_SHADOWS->value);
        $this->assertSame('Cave of Shadows', LocationType::CAVE_OF_SHADOWS->label());
        $this->assertTrue(LocationType::CAVE_OF_SHADOWS->isCaveOfShadows());
    }

    public function test_type_twelve_is_cave_of_memories(): void
    {
        $this->assertSame(12, LocationType::CAVE_OF_MEMORIES->value);
        $this->assertSame('Cave of Memories', LocationType::CAVE_OF_MEMORIES->label());
        $this->assertTrue(LocationType::CAVE_OF_MEMORIES->isCaveOfMemories());
    }

    public function test_weekly_fight_location_types_are_exactly_seven_through_ten(): void
    {
        $this->assertSame([
            LocationType::ALCHEMY_CHURCH->value,
            LocationType::LORDS_STRONG_HOLD->value,
            LocationType::BROKEN_ANVIL->value,
            LocationType::TWISTED_MAIDENS_DUNGEONS->value,
        ], LocationType::weeklyFightLocationTypes());
    }

    public function test_cave_of_memories_is_not_a_weekly_fight_location_type(): void
    {
        $this->assertFalse(LocationType::CAVE_OF_MEMORIES->isWeeklyFightLocationType());
    }

    public function test_manual_quest_drop_values_exclude_both_cave_location_types(): void
    {
        $this->assertContains(LocationType::SPECIAL->value, LocationType::manualQuestDropValues());
        $this->assertNotContains(LocationType::CAVE_OF_SHADOWS->value, LocationType::manualQuestDropValues());
        $this->assertNotContains(LocationType::CAVE_OF_MEMORIES->value, LocationType::manualQuestDropValues());
    }

    public function test_cave_of_shadows_cannot_drop_manual_quest_items(): void
    {
        $this->assertFalse(LocationType::CAVE_OF_SHADOWS->canDropManualQuestItems());
    }

    public function test_cave_of_memories_cannot_drop_manual_quest_items(): void
    {
        $this->assertFalse(LocationType::CAVE_OF_MEMORIES->canDropManualQuestItems());
    }

    public function test_special_can_drop_manual_quest_items(): void
    {
        $this->assertTrue(LocationType::SPECIAL->canDropManualQuestItems());
    }
}
