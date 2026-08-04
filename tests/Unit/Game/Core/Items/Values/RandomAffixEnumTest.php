<?php

namespace Tests\Unit\Game\Core\Items\Values;

use App\Game\Core\Items\Values\RandomAffixTier;
use Exception;
use Tests\TestCase;

class RandomAffixEnumTest extends TestCase
{
    public function test_legendary_ranges_are_preserved(): void
    {
        $this->assertSame([10, 65], RandomAffixTier::LEGENDARY->getPercentageRange());
        $this->assertSame([8, 25], RandomAffixTier::LEGENDARY->getDamageRange());
    }

    public function test_mythic_ranges_are_preserved(): void
    {
        $this->assertSame([65, 90], RandomAffixTier::MYTHIC->getPercentageRange());
        $this->assertSame([30, 60], RandomAffixTier::MYTHIC->getDamageRange());
    }

    public function test_cosmic_ranges_are_preserved(): void
    {
        $this->assertSame([95, 125], RandomAffixTier::COSMIC->getPercentageRange());
        $this->assertSame([75, 110], RandomAffixTier::COSMIC->getDamageRange());
    }

    public function test_invalid_tier_retains_the_existing_exception(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('99 does not exist.');

        RandomAffixTier::fromValue(99);
    }
}
