<?php

namespace Tests\Unit\Flare\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateMonster;

class MonsterTest extends TestCase
{
    use RefreshDatabase,
       CreateMonster,
       CreateGameMap;

    public function testGetGameMap() {

        $monster = $this->createMonster([
            'game_map_id' => $this->createGameMap()->id,
        ]);

        $this->assertNotNull($monster->gameMap);
    }
}
