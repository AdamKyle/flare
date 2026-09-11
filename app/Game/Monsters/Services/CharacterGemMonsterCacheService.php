<?php

namespace App\Game\Monsters\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterGameLocationGemProgression;
use App\Flare\Models\CharacterGameMapGemProgression;
use App\Flare\Models\GameLocationGemParamter;
use App\Flare\Models\GameMapGemParamter;
use App\Flare\Models\Monster;
use App\Game\Gems\Progression\Services\CharacterAreaGemEffectService;
use App\Game\Gems\Services\AreaGemEffectService;
use App\Game\Gems\Values\AreaGemRewardEffect;
use App\Game\Gems\Values\GemSourceType;
use App\Game\Gems\Values\ResolvedAreaGemEffects;
use App\Game\Gems\Values\ResolvedAreaGemSource;
use App\Game\Monsters\Transformers\MonsterTransformer;
use Illuminate\Support\Facades\Cache;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;

/**
 * Lazily resolves the Character-effective Monster dataset when a
 * Character's global/personal Gem progression actually changes the
 * Monster-relevant Gem effects for their current context. Shared,
 * Character-neutral Monster cache entries remain the fast default path;
 * this service is only consulted when a progressed Gem context exists.
 */
class CharacterGemMonsterCacheService
{
    private const int CACHE_TTL_SECONDS = 900;

    public function __construct(
        private readonly AreaGemEffectService $areaGemEffectService,
        private readonly CharacterAreaGemEffectService $characterAreaGemEffectService,
        private readonly MonsterCacheRevisionService $monsterCacheRevisionService,
        private readonly MonsterTransformer $monsterTransformer,
        private readonly Manager $manager,
    ) {}

    /**
     * Resolve the Monster dataset for the Character's current context,
     * overlaying global/personal Gem progression only when it actually
     * changes a Monster-relevant Gem effect; otherwise returns the given
     * shared dataset unchanged.
     */
    public function resolveForCharacter(Character $character, array $sharedDataset): array
    {
        $adjustedEffects = $this->characterAreaGemEffectService->resolveForCharacter($character);

        if (empty($adjustedEffects->sources())) {
            return $sharedDataset;
        }

        $baseEffects = $this->areaGemEffectService->resolveForCharacter($character);

        if (! $this->monsterRelevantEffectsDiffer($baseEffects, $adjustedEffects)) {
            return $sharedDataset;
        }

        return $this->resolveDerivedDataset($character, $adjustedEffects);
    }

    /**
     * Determine whether progression changed any Monster-relevant Gem effect
     * field between the base (Character-neutral) and adjusted resolutions.
     */
    private function monsterRelevantEffectsDiffer(ResolvedAreaGemEffects $baseEffects, ResolvedAreaGemEffects $adjustedEffects): bool
    {
        if ($baseEffects->monsterEffects()->toArray() !== $adjustedEffects->monsterEffects()->toArray()) {
            return true;
        }

        $monsterRelevantRewardEffects = [
            AreaGemRewardEffect::MONSTER_XP_INCREASE,
            AreaGemRewardEffect::MONSTER_GOLD_DROP_INCREASE,
            AreaGemRewardEffect::ENEMY_QUEST_ITEM_DROP_CHANCE_INCREASE,
        ];

        foreach ($monsterRelevantRewardEffects as $rewardEffect) {
            if ($baseEffects->rewardEffect($rewardEffect) !== $adjustedEffects->rewardEffect($rewardEffect)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve the Character-effective Monster dataset, transforming the
     * real persisted source Monsters through the real MonsterTransformer on
     * a cache miss and caching the result under a level-identity-scoped key.
     */
    private function resolveDerivedDataset(Character $character, ResolvedAreaGemEffects $adjustedEffects): array
    {
        $gameMap = $character->map->gameMap;
        $cacheKey = $this->buildCacheKey($character, $gameMap->id, $adjustedEffects);

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($gameMap, $adjustedEffects): array {
            $monsterSourceMap = $gameMap->monsterSourceGameMap();

            $monsters = new Collection(
                Monster::where('is_celestial_entity', false)
                    ->where('is_raid_monster', false)
                    ->where('is_raid_boss', false)
                    ->whereNull('only_for_location_type')
                    ->where('game_map_id', $monsterSourceMap->id)
                    ->get(),
                $this->monsterTransformer->withAreaGemEffects($adjustedEffects),
            );

            return $this->manager->createData($monsters)->toArray();
        });
    }

    /**
     * Build the Character-effective Monster cache key from the Character id,
     * current Game Map id, canonical Monster cache revision, and every
     * currently contributing global/personal progression level.
     */
    private function buildCacheKey(Character $character, int $gameMapId, ResolvedAreaGemEffects $adjustedEffects): string
    {
        $levelSignature = collect($adjustedEffects->sources())
            ->map(fn (ResolvedAreaGemSource $source): string => $this->levelSignatureForSource($character, $source))
            ->implode('-');

        return sprintf(
            'character-gem-monsters-%d-%d-%d-%s',
            $character->id,
            $gameMapId,
            $this->monsterCacheRevisionService->current(),
            $levelSignature,
        );
    }

    /**
     * Resolve the current global/personal progression level pair contributing from one resolved source.
     */
    private function levelSignatureForSource(Character $character, ResolvedAreaGemSource $source): string
    {
        if ($source->type() === GemSourceType::MAP_GEM) {
            $profile = GameMapGemParamter::find($source->profileId());
            $globalLevel = $profile?->progression?->level ?? 1;
            $personalLevel = CharacterGameMapGemProgression::where('character_id', $character->id)
                ->where('game_map_gem_paramter_id', $source->profileId())
                ->value('level') ?? 1;

            return 'map-'.$source->profileId().'-'.$globalLevel.'-'.$personalLevel;
        }

        $profile = GameLocationGemParamter::find($source->profileId());
        $globalLevel = $profile?->progression?->level ?? 1;
        $personalLevel = CharacterGameLocationGemProgression::where('character_id', $character->id)
            ->where('game_location_gem_paramter_id', $source->profileId())
            ->value('level') ?? 1;

        return 'location-'.$source->profileId().'-'.$globalLevel.'-'.$personalLevel;
    }
}
