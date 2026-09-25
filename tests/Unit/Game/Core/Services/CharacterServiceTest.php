<?php

namespace Tests\Unit\Game\Core\Services;

use App\Game\Core\Services\CharacterService;
use App\Game\Core\Values\LevelUpValue;
use Tests\TestCase;

class CharacterServiceTest extends TestCase
{
    public function test_xp_for_next_level_preserves_the_flat_cubic_and_capped_requirement_curve(): void
    {
        $characterService = new CharacterService(new LevelUpValue);

        $this->assertSame(100, $characterService->getXPForNextLevel(1000));
        $this->assertSame(1000, $characterService->getXPForNextLevel(1001));
        $this->assertSame(5246, $characterService->getXPForNextLevel(3000));
        $this->assertSame(34974, $characterService->getXPForNextLevel(4999));
        $this->assertSame(35000, $characterService->getXPForNextLevel(5000));
    }
}
