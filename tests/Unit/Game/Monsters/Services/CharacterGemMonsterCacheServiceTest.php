<?php

namespace Tests\Unit\Game\Monsters\Services;

use App\Game\Gems\Progression\Services\CharacterAreaGemEffectService;
use App\Game\Gems\Progression\Services\GemProgressionEffectService;
use App\Game\Gems\Services\AreaGemEffectService;
use App\Game\Monsters\Services\CharacterGemMonsterCacheService;
use App\Game\Monsters\Services\MonsterCacheRevisionService;
use App\Game\Monsters\Transformers\MonsterTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use League\Fractal\Manager;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterGameMapGemProgression;
use Tests\Traits\CreateCharacterGameMapGemScroll;
use Tests\Traits\CreateGameMapGemParamter;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateMonster;

class CharacterGemMonsterCacheServiceTest extends TestCase
{
    use CreateCharacterGameMapGemProgression,
        CreateCharacterGameMapGemScroll,
        CreateGameMapGemParamter,
        CreateGem,
        CreateMonster,
        RefreshDatabase;

    private CharacterGemMonsterCacheService $characterGemMonsterCacheService;

    private MonsterCacheRevisionService $monsterCacheRevisionService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->monsterCacheRevisionService = new MonsterCacheRevisionService;

        $this->characterGemMonsterCacheService = new CharacterGemMonsterCacheService(
            new AreaGemEffectService,
            new CharacterAreaGemEffectService(new AreaGemEffectService, new GemProgressionEffectService),
            $this->monsterCacheRevisionService,
            new MonsterTransformer,
            new Manager,
        );
    }

    public function test_no_progression_returns_the_shared_dataset_unchanged(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $gameMap = $character->map->gameMap;

        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['enemy_strength_increase' => 0.10]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $sharedDataset = ['data' => ['placeholder' => true]];

        $result = $this->characterGemMonsterCacheService->resolveForCharacter($character->refresh(), $sharedDataset);

        $this->assertSame($sharedDataset, $result);
    }

    public function test_cache_miss_transforms_real_persisted_monsters_with_character_aware_effects(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $gameMap = $character->map->gameMap;

        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['enemy_strength_increase' => 0.10]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $this->createCharacterGameMapGemProgression([
            'character_id' => $character->id,
            'game_map_gem_paramter_id' => $profile->id,
            'level' => 200,
            'xp' => 0,
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $gameMap->id,
            'str' => 100,
            'damage_stat' => 'str',
        ]);

        $result = $this->characterGemMonsterCacheService->resolveForCharacter($character->refresh(), ['data' => []]);

        $transformedMonster = collect($result['data'])->firstWhere('id', $monster->id);

        $this->assertSame(113, $transformedMonster['str']);
    }

    public function test_scrolls_do_not_change_the_monster_cache_identity_or_values(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $gameMap = $character->map->gameMap;

        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['enemy_strength_increase' => 0.10]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $this->createCharacterGameMapGemProgression([
            'character_id' => $character->id,
            'game_map_gem_paramter_id' => $profile->id,
            'level' => 200,
            'xp' => 0,
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $gameMap->id,
            'str' => 100,
            'damage_stat' => 'str',
        ]);

        $this->createCharacterGameMapGemScroll([
            'character_id' => $character->id,
            'game_map_gem_paramter_id' => $profile->id,
        ]);

        $result = $this->characterGemMonsterCacheService->resolveForCharacter($character->refresh(), ['data' => []]);

        $transformedMonster = collect($result['data'])->firstWhere('id', $monster->id);

        $this->assertSame(113, $transformedMonster['str']);
    }

    public function test_cache_key_changes_when_the_canonical_revision_changes(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $gameMap = $character->map->gameMap;

        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['enemy_strength_increase' => 0.10]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $this->createCharacterGameMapGemProgression([
            'character_id' => $character->id,
            'game_map_gem_paramter_id' => $profile->id,
            'level' => 200,
            'xp' => 0,
        ]);

        $this->createMonster(['game_map_id' => $gameMap->id, 'str' => 100, 'damage_stat' => 'str']);

        $this->characterGemMonsterCacheService->resolveForCharacter($character->refresh(), ['data' => []]);

        $this->monsterCacheRevisionService->bump();

        $newMonster = $this->createMonster(['game_map_id' => $gameMap->id, 'str' => 50, 'damage_stat' => 'str']);

        $result = $this->characterGemMonsterCacheService->resolveForCharacter($character->refresh(), ['data' => []]);

        $this->assertNotNull(collect($result['data'])->firstWhere('id', $newMonster->id));
    }

    public function test_cache_key_changes_when_the_personal_level_changes(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $gameMap = $character->map->gameMap;

        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['enemy_strength_increase' => 0.10]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $progression = $this->createCharacterGameMapGemProgression([
            'character_id' => $character->id,
            'game_map_gem_paramter_id' => $profile->id,
            'level' => 200,
            'xp' => 0,
        ]);

        $monster = $this->createMonster(['game_map_id' => $gameMap->id, 'str' => 100, 'damage_stat' => 'str']);

        $firstResult = $this->characterGemMonsterCacheService->resolveForCharacter($character->refresh(), ['data' => []]);
        $firstStr = collect($firstResult['data'])->firstWhere('id', $monster->id)['str'];

        $progression->update(['level' => 300]);

        $secondResult = $this->characterGemMonsterCacheService->resolveForCharacter($character->refresh(), ['data' => []]);
        $secondStr = collect($secondResult['data'])->firstWhere('id', $monster->id)['str'];

        $this->assertNotSame($firstStr, $secondStr);
        $this->assertSame(115, $secondStr);
    }

    public function test_cache_hit_does_not_repeat_the_monster_transformation_query(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $gameMap = $character->map->gameMap;

        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['enemy_strength_increase' => 0.10]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $this->createCharacterGameMapGemProgression([
            'character_id' => $character->id,
            'game_map_gem_paramter_id' => $profile->id,
            'level' => 200,
            'xp' => 0,
        ]);

        $this->createMonster(['game_map_id' => $gameMap->id, 'str' => 100, 'damage_stat' => 'str']);

        $this->characterGemMonsterCacheService->resolveForCharacter($character->refresh(), ['data' => []]);

        DB::enableQueryLog();

        $this->characterGemMonsterCacheService->resolveForCharacter($character->refresh(), ['data' => []]);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $monsterTableQueries = collect($queries)->filter(fn (array $query): bool => str_contains($query['query'], 'from `monsters`'));

        $this->assertCount(0, $monsterTableQueries);
    }
}
