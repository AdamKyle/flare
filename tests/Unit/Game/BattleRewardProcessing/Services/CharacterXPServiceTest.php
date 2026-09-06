<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Services;

use App\Game\BattleRewardProcessing\Services\CharacterXPService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameMapGemParamter;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateMonster;

class CharacterXPServiceTest extends TestCase
{
    use CreateGameMapGemParamter, CreateGem, CreateMonster, RefreshDatabase;

    public function test_monster_xp_increase_from_rolled_map_gem_doubles_awarded_monster_xp(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['level' => 5]);
        $gameMap = $character->map->gameMap;

        $monster = $this->createMonster([
            'game_map_id' => $gameMap->id,
            'xp' => 1000,
            'max_level' => 999,
        ]);

        $service = resolve(CharacterXPService::class);

        $xpWithoutGem = $service->setCharacter($character->refresh())->fetchXpForMonster($monster);

        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['monster_xp_increase' => 1.0]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $xpWithGem = $service->setCharacter($character->refresh())->fetchXpForMonster($monster);

        $this->assertSame($xpWithoutGem * 2, $xpWithGem);
    }

    public function test_character_xp_bonus_from_rolled_map_gem_applies_exactly_once(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['level' => 5]);
        $gameMap = $character->map->gameMap;

        $monster = $this->createMonster([
            'game_map_id' => $gameMap->id,
            'xp' => 1000,
            'max_level' => 999,
        ]);

        $service = resolve(CharacterXPService::class);

        $xpWithoutGem = $service->setCharacter($character->refresh())->fetchXpForMonster($monster);

        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['character_xp_bonus' => 0.5]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $xpWithGem = $service->setCharacter($character->refresh())->fetchXpForMonster($monster);

        $this->assertSame((int) round($xpWithoutGem * 1.5), $xpWithGem);
    }
}
