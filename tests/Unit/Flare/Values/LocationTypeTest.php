<?php

namespace Tests\Unit\Flare\Values;

use App\Flare\Values\LocationType;
use Tests\TestCase;

class LocationTypeTest extends TestCase
{
    public function testValuesReturnsAllIntegerValues(): void
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

    public function testSpecialHasExpectedValue(): void
    {
        $this->assertSame(13, LocationType::SPECIAL->value);
    }

    public function testGetNamedValuesReturnsIntegerLabelsForAdminForms(): void
    {
        $this->assertSame('Purgatory Smiths House', LocationType::PURGATORY_SMITH_HOUSE->label());
        $this->assertSame('Gold Mines', LocationType::getNamedValues()[LocationType::GOLD_MINES->value]);
        $this->assertSame('Special', LocationType::getNamedValues()[LocationType::SPECIAL->value]);
    }

    public function testManualQuestDropValuesExcludeCaveOfMemories(): void
    {
        $this->assertContains(LocationType::SPECIAL->value, LocationType::manualQuestDropValues());
        $this->assertNotContains(LocationType::CAVE_OF_MEMORIES->value, LocationType::manualQuestDropValues());
    }

    public function testCaveOfMemoriesCannotDropManualQuestItems(): void
    {
        $this->assertFalse(LocationType::CAVE_OF_MEMORIES->canDropManualQuestItems());
    }

    public function testSpecialCanDropManualQuestItems(): void
    {
        $this->assertTrue(LocationType::SPECIAL->canDropManualQuestItems());
    }
}
