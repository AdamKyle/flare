<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Handlers;

use App\Game\BattleRewardProcessing\Handlers\FactionHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateFaction;
use Tests\Traits\CreateGameMapGemParamter;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateMonster;

class FactionHandlerTest extends TestCase
{
    use CreateFaction, CreateGameMapGemParamter, CreateGem, CreateMonster, RefreshDatabase;

    public function test_faction_points_are_unaffected_by_a_rolled_map_gem(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->user->update(['guide_enabled' => false]);
        $gameMap = $character->map->gameMap;

        $monster = $this->createMonster(['game_map_id' => $gameMap->id]);

        $faction = $this->createFaction([
            'character_id' => $character->id,
            'game_map_id' => $gameMap->id,
            'points_needed' => 100000,
        ]);

        resolve(FactionHandler::class)->handleFaction($character->refresh(), $monster);

        $pointsWithoutGem = $faction->refresh()->current_points;

        $faction->update(['current_points' => 0]);

        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile);
        $profile->update(['rolled_gem_id' => $gem->id]);

        resolve(FactionHandler::class)->handleFaction($character->refresh(), $monster);

        $pointsWithGem = $faction->refresh()->current_points;

        $this->assertSame($pointsWithoutGem, $pointsWithGem);
    }
}
