<?php

namespace App\Game\Monsters\Services;

use App\Flare\Models\Character;
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

class CharacterGemMonsterCacheService
{
    private const int CACHE_TTL_SECONDS = 900;

    /**
     * @param AreaGemEffectService $areaGemEffectService
     * @param CharacterAreaGemEffectService $characterAreaGemEffectService
     * @param MonsterCacheRevisionService $monsterCacheRevisionService
     * @param MonsterTransformer $monsterTransformer
     * @param Manager $manager
     */
    public function __construct(
        private readonly AreaGemEffectService $areaGemEffectService,
        private readonly CharacterAreaGemEffectService $characterAreaGemEffectService,
        private readonly MonsterCacheRevisionService $monsterCacheRevisionService,
        private readonly MonsterTransformer $monsterTransformer,
        private readonly Manager $manager,
    ) {}

    /**
     * Resolve the Character-effective Monster dataset, falling back to the given shared dataset when progression changes nothing Monster-relevant.
     *
     * @param Character $character
     * @param array $sharedDataset
     * @return array
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
     * Determine whether progression changed any Monster-relevant Gem effect field.
     *
     * @param ResolvedAreaGemEffects $baseEffects
     * @param ResolvedAreaGemEffects $adjustedEffects
     * @return bool
     */
    private function monsterRelevantEffectsDiffer(ResolvedAreaGemEffects $baseEffects, ResolvedAreaGemEffects $adjustedEffects): bool
    {
        if ($baseEffects->monsterEffects()->toArray() !== $adjustedEffects->monsterEffects()->toArray()) {
            return true;
        }

        foreach (AreaGemRewardEffect::monsterTransformedCases() as $rewardEffect) {
            if ($baseEffects->rewardEffect($rewardEffect) !== $adjustedEffects->rewardEffect($rewardEffect)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve and cache the Character-effective Monster dataset for a cache miss.
     *
     * @param Character $character
     * @param ResolvedAreaGemEffects $adjustedEffects
     * @return array
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
     * Build the Character-effective Monster cache key from Character, Game Map, canonical revision, and per-source progression signature.
     *
     * @param Character $character
     * @param int $gameMapId
     * @param ResolvedAreaGemEffects $adjustedEffects
     * @return string
     */
    private function buildCacheKey(Character $character, int $gameMapId, ResolvedAreaGemEffects $adjustedEffects): string
    {
        $levelSignature = collect($adjustedEffects->sources())
            ->map(fn (ResolvedAreaGemSource $source): string => $this->levelSignatureForSource($adjustedEffects, $source))
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
     * Resolve the already-resolved progression level signature for one contributing source.
     *
     * @param ResolvedAreaGemEffects $adjustedEffects
     * @param ResolvedAreaGemSource $source
     * @return string
     */
    private function levelSignatureForSource(ResolvedAreaGemEffects $adjustedEffects, ResolvedAreaGemSource $source): string
    {
        $prefix = $source->type() === GemSourceType::MAP_GEM ? 'map-' : 'location-';
        $globalLevel = $adjustedEffects->globalLevelForSource($source);
        $globalSignature = is_null($globalLevel) ? '' : '-g'.$globalLevel;

        return $prefix.$source->profileId().$globalSignature.'-p'.$adjustedEffects->personalLevelForSource($source);
    }
}
