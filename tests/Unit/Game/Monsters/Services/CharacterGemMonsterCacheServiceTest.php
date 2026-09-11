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
use Tests\Setup\GemProgression\GemWorldRewardTestFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterGameMapGemProgression;
use Tests\Traits\CreateCharacterGameMapGemScroll;
use Tests\Traits\CreateGameMapGemParamter;
use Tests\Traits\CreateGameMapGemProgression;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateMonster;

class CharacterGemMonsterCacheServiceTest extends TestCase
{
    use CreateCharacterGameMapGemProgression,
        CreateCharacterGameMapGemScroll,
        CreateGameMapGemParamter,
        CreateGameMapGemProgression,
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
        $graph = (new GemWorldRewardTestFactory)->buildGeneratedMapGemWorldCharacter();
        $graph->mapProfile->rolledGem()->update(['enemy_strength_increase' => 0.10]);

        $this->createCharacterGameMapGemProgression([
            'character_id' => $graph->character->id,
            'game_map_gem_paramter_id' => $graph->mapProfile->id,
            'level' => 200,
            'xp' => 0,
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $graph->character->map->gameMap->monsterSourceGameMap()->id,
            'str' => 100,
            'damage_stat' => 'str',
        ]);

        $result = $this->characterGemMonsterCacheService->resolveForCharacter($graph->character->refresh(), ['data' => []]);

        $transformedMonster = collect($result['data'])->firstWhere('id', $monster->id);

        // Rolled 0.10 x the 2.0 Map Gem World monster multiplier (0.20), plus the personal negative bonus at level 200 (0.03) = 0.23.
        $this->assertSame(123, $transformedMonster['str']);
    }

    public function test_scrolls_do_not_change_the_monster_cache_identity_or_values(): void
    {
        $graph = (new GemWorldRewardTestFactory)->buildGeneratedMapGemWorldCharacter();
        $graph->mapProfile->rolledGem()->update(['enemy_strength_increase' => 0.10]);

        $this->createCharacterGameMapGemProgression([
            'character_id' => $graph->character->id,
            'game_map_gem_paramter_id' => $graph->mapProfile->id,
            'level' => 200,
            'xp' => 0,
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $graph->character->map->gameMap->monsterSourceGameMap()->id,
            'str' => 100,
            'damage_stat' => 'str',
        ]);

        $this->createCharacterGameMapGemScroll([
            'character_id' => $graph->character->id,
            'game_map_gem_paramter_id' => $graph->mapProfile->id,
        ]);

        $result = $this->characterGemMonsterCacheService->resolveForCharacter($graph->character->refresh(), ['data' => []]);

        $transformedMonster = collect($result['data'])->firstWhere('id', $monster->id);

        $this->assertSame(123, $transformedMonster['str']);
    }

    public function test_cache_key_changes_when_the_canonical_revision_changes(): void
    {
        $graph = (new GemWorldRewardTestFactory)->buildGeneratedMapGemWorldCharacter();
        $graph->mapProfile->rolledGem()->update(['enemy_strength_increase' => 0.10]);

        $this->createCharacterGameMapGemProgression([
            'character_id' => $graph->character->id,
            'game_map_gem_paramter_id' => $graph->mapProfile->id,
            'level' => 200,
            'xp' => 0,
        ]);

        $sourceGameMapId = $graph->character->map->gameMap->monsterSourceGameMap()->id;

        $this->createMonster(['game_map_id' => $sourceGameMapId, 'str' => 100, 'damage_stat' => 'str']);

        $this->characterGemMonsterCacheService->resolveForCharacter($graph->character->refresh(), ['data' => []]);

        $this->monsterCacheRevisionService->bump();

        $newMonster = $this->createMonster(['game_map_id' => $sourceGameMapId, 'str' => 50, 'damage_stat' => 'str']);

        $result = $this->characterGemMonsterCacheService->resolveForCharacter($graph->character->refresh(), ['data' => []]);

        $this->assertNotNull(collect($result['data'])->firstWhere('id', $newMonster->id));
    }

    public function test_cache_key_changes_when_the_personal_level_changes(): void
    {
        $graph = (new GemWorldRewardTestFactory)->buildGeneratedMapGemWorldCharacter();
        $graph->mapProfile->rolledGem()->update(['enemy_strength_increase' => 0.10]);

        $progression = $this->createCharacterGameMapGemProgression([
            'character_id' => $graph->character->id,
            'game_map_gem_paramter_id' => $graph->mapProfile->id,
            'level' => 200,
            'xp' => 0,
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $graph->character->map->gameMap->monsterSourceGameMap()->id,
            'str' => 100,
            'damage_stat' => 'str',
        ]);

        $firstResult = $this->characterGemMonsterCacheService->resolveForCharacter($graph->character->refresh(), ['data' => []]);
        $firstStr = collect($firstResult['data'])->firstWhere('id', $monster->id)['str'];

        $progression->update(['level' => 300]);

        $secondResult = $this->characterGemMonsterCacheService->resolveForCharacter($graph->character->refresh(), ['data' => []]);
        $secondStr = collect($secondResult['data'])->firstWhere('id', $monster->id)['str'];

        $this->assertNotSame($firstStr, $secondStr);
        // Rolled 0.10 x 2.0 world multiplier (0.20), plus the personal negative bonus at level 300 (0.05) = 0.25.
        $this->assertSame(125, $secondStr);
    }

    public function test_global_level_change_does_not_cause_a_derived_cache_miss_when_no_reward_field_is_affected(): void
    {
        $graph = (new GemWorldRewardTestFactory)->buildGeneratedMapGemWorldCharacter();
        $graph->mapProfile->rolledGem()->update(['enemy_strength_increase' => 0.10, 'gold_gain' => 0.0]);

        $globalProgression = $this->createGameMapGemProgression([
            'game_map_gem_paramter_id' => $graph->mapProfile->id,
            'level' => 1,
            'xp' => 0,
        ]);

        $this->createCharacterGameMapGemProgression([
            'character_id' => $graph->character->id,
            'game_map_gem_paramter_id' => $graph->mapProfile->id,
            'level' => 200,
            'xp' => 0,
        ]);

        $this->createMonster([
            'game_map_id' => $graph->character->map->gameMap->monsterSourceGameMap()->id,
            'str' => 100,
            'damage_stat' => 'str',
        ]);

        $this->characterGemMonsterCacheService->resolveForCharacter($graph->character->refresh(), ['data' => []]);

        $globalProgression->update(['level' => 90]);

        DB::enableQueryLog();

        $this->characterGemMonsterCacheService->resolveForCharacter($graph->character->refresh(), ['data' => []]);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $monsterTableQueries = collect($queries)->filter(fn (array $query): bool => str_contains($query['query'], 'from `monsters`'));

        $this->assertCount(0, $monsterTableQueries);
    }

    public function test_global_level_change_causes_a_derived_cache_miss_when_a_monster_reward_field_is_affected(): void
    {
        $graph = (new GemWorldRewardTestFactory)->buildGeneratedMapGemWorldCharacter();
        $graph->mapProfile->rolledGem()->update(['monster_xp_increase' => 0.10, 'gold_gain' => 0.0]);

        $globalProgression = $this->createGameMapGemProgression([
            'game_map_gem_paramter_id' => $graph->mapProfile->id,
            'level' => 1,
            'xp' => 0,
        ]);

        $this->createCharacterGameMapGemProgression([
            'character_id' => $graph->character->id,
            'game_map_gem_paramter_id' => $graph->mapProfile->id,
            'level' => 200,
            'xp' => 0,
        ]);

        $this->createMonster([
            'game_map_id' => $graph->character->map->gameMap->monsterSourceGameMap()->id,
            'xp' => 100,
            'damage_stat' => 'str',
        ]);

        $this->characterGemMonsterCacheService->resolveForCharacter($graph->character->refresh(), ['data' => []]);

        $globalProgression->update(['level' => 90]);

        DB::enableQueryLog();

        $this->characterGemMonsterCacheService->resolveForCharacter($graph->character->refresh(), ['data' => []]);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $monsterTableQueries = collect($queries)->filter(fn (array $query): bool => str_contains($query['query'], 'from `monsters`'));

        $this->assertCount(1, $monsterTableQueries);
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
