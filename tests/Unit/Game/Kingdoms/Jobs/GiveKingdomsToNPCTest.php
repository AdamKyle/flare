<?php

namespace Tests\Unit\Game\Kingdoms\Jobs;

use App\Flare\Models\Kingdom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Game\Kingdoms\Jobs\GiveKingdomsToNPC;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateNpc;

class GiveKingdomsToNPCTest extends TestCase {

    use RefreshDatabase, CreateGameMap, CreateNpc;

    public function testAllKingdomsGivenToNpc() {

        $character = (new CharacterFactory())->createBaseCharacter()
                                             ->givePlayerLocation()
                                             ->kingdomManagement()
                                             ->assignKingdom()
                                             ->getCharacter();

        $this->createNpc([
            'game_map_id' => $character->map->gameMap->id,
        ]);


        GiveKingdomsToNPC::dispatch($character->user);

        $character = $character->refresh();

        $this->assertTrue($character->kingdoms->isEmpty());
    }

    public function testNoKingdomsGivenToNpc() {

        $character = (new CharacterFactory())->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter(false);

        $this->createNpc([
            'game_map_id' => $character->map->gameMap->id,
        ]);


        GiveKingdomsToNPC::dispatch($character->user);

        $this->assertTrue(Kingdom::where('npc_owned', true)->get()->isEmpty());
    }
}
