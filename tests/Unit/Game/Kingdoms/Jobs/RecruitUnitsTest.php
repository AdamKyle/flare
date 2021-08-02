<?php

namespace Tests\Unit\Game\Kingdoms\Jobs;

use App\Flare\Models\GameMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Game\Kingdoms\Jobs\RecruitUnits;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameUnit;
use Tests\Traits\CreateKingdom;

class RecruitUnitsTest extends TestCase
{
    use RefreshDatabase, CreateKingdom, CreateGameUnit;

    public function testJobReturnsEarlyWithNoQueue()
    {
        $kingdom = $this->createKingdom([
            'character_id'       => (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter()->id,
            'game_map_id'        => GameMap::first()->id,
        ]);

        $unit = $this->createGameUnit();

        RecruitUnits::dispatch($unit, $kingdom, 100, 1);

        $this->assertTrue($kingdom->refresh()->units->isEmpty());
    }
}
