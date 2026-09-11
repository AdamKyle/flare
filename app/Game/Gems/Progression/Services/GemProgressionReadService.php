<?php

namespace App\Game\Gems\Progression\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterGameLocationGemProgression;
use App\Flare\Models\CharacterGameLocationGemScroll;
use App\Flare\Models\CharacterGameMapGemProgression;
use App\Flare\Models\CharacterGameMapGemScroll;
use App\Game\Gems\Progression\Values\GemProgressionBands;
use App\Game\Gems\Progression\Values\ResolvedGemWorldProfile;
use App\Game\Gems\Services\AreaGemEffectService;
use Illuminate\Support\Collection;

/**
 * Small Player-facing read model for the Character's current Gem
 * progression status. Owns no formulas of its own; every value is resolved
 * through the existing progression/effect/Scroll services.
 */
class GemProgressionReadService
{
    public function __construct(
        private readonly GemWorldProfileResolver $gemWorldProfileResolver,
        private readonly GemProgressionCurveService $gemProgressionCurveService,
        private readonly GemProgressionEffectService $gemProgressionEffectService,
        private readonly GemScrollEffectService $gemScrollEffectService,
        private readonly CharacterAreaGemEffectService $characterAreaGemEffectService,
        private readonly AreaGemEffectService $areaGemEffectService,
    ) {}

    /**
     * Resolve the Character's current Gem progression status, or an empty
     * result when the Character has no contributing Gem profile right now.
     */
    public function currentStatus(Character $character): array
    {
        $resolvedProfile = $this->gemWorldProfileResolver->resolveForCharacter($character);

        if (is_null($resolvedProfile)) {
            return ['profile' => null];
        }

        [$globalLevel, $globalXp] = $this->resolveGlobalProgression($resolvedProfile);
        [$personalLevel, $personalXp] = $this->resolvePersonalProgression($character, $resolvedProfile);

        $scrollAggregate = $resolvedProfile->isMapProfile()
            ? $this->gemScrollEffectService->aggregateForMapProfile($character, $resolvedProfile->mapProfile())
            : $this->gemScrollEffectService->aggregateForLocationProfile($character, $resolvedProfile->locationProfile());

        $rolledEffects = $this->areaGemEffectService->resolveForCharacter($character);
        $effectiveEffects = $this->characterAreaGemEffectService->resolveForCharacter($character);

        return [
            'profile' => [
                'type' => $resolvedProfile->type()->value,
                'id' => $resolvedProfile->profileId(),
                'generated_game_map_id' => $resolvedProfile->generatedGameMap()->id,
                'generated_game_map_name' => $resolvedProfile->generatedGameMap()->name,
            ],
            'global' => [
                'level' => $globalLevel,
                'xp' => $globalXp,
                'next_level_xp' => $this->gemProgressionCurveService->xpRequiredForGlobalLevel($globalLevel),
                'max_level' => $this->gemProgressionCurveService->globalMaxLevel(),
            ],
            'personal' => [
                'level' => $personalLevel,
                'xp' => $personalXp,
                'next_level_xp' => $this->gemProgressionCurveService->xpRequiredForPersonalLevel($personalLevel),
                'max_level' => $this->gemProgressionCurveService->personalMaxLevel(),
                'negative_bonus' => $this->gemProgressionEffectService->personalNegativeBonus($personalLevel),
                'unique_chance_bonus' => $this->gemProgressionEffectService->personalUniqueMythicBonus($personalLevel),
                'mythic_chance_bonus' => $this->gemProgressionEffectService->personalUniqueMythicBonus($personalLevel),
                'cosmic_chance_bonus' => $this->gemProgressionEffectService->personalCosmicBonus($personalLevel),
                'enhanced_equipment_chance' => $this->gemProgressionEffectService->enhancedEquipmentChance($personalLevel),
                'enhanced_equipment_unlocked' => $personalLevel >= GemProgressionBands::PERSONAL_ENHANCED_EQUIPMENT_LEVEL,
            ],
            'scroll_drop' => [
                'eligible' => $this->gemProgressionEffectService->isScrollDropEligible($personalLevel),
                'chance' => GemProgressionBands::GEM_SCROLL_DROP_CHANCE,
            ],
            'active_scrolls' => [
                'count' => $scrollAggregate->activeCount(),
                'total_primary_bonus' => $scrollAggregate->totalPrimaryBonus(),
                'cap' => GemProgressionBands::ACTIVE_SCROLL_PRIMARY_BONUS_CAP,
                'remaining_capacity' => $scrollAggregate->remainingCapacity(),
                'xp_bonus' => $scrollAggregate->xpBonusTotal(),
                'gold_bonus' => $scrollAggregate->goldBonusTotal(),
                'copper_coins_bonus' => $scrollAggregate->copperCoinBonusTotal(),
                'gold_dust_bonus' => $scrollAggregate->goldDustBonusTotal(),
                'shards_bonus' => $scrollAggregate->shardsBonusTotal(),
                'item_bonus' => $scrollAggregate->itemBonusTotal(),
            ],
            'active_scroll_rows' => $this->activeScrollRows($character, $resolvedProfile),
            'rolled_reward_effects' => $rolledEffects->rewardEffects()->toArray(),
            'effective_reward_effects' => $effectiveEffects->rewardEffects()->toArray(),
            'rolled_monster_effects' => $rolledEffects->monsterEffects()->toArray(),
            'effective_monster_effects' => $effectiveEffects->monsterEffects()->toArray(),
            'rolled_rarity_effects' => $rolledEffects->rarityEffects()->toArray(),
            'effective_rarity_effects' => $effectiveEffects->rarityEffects()->toArray(),
        ];
    }

    /**
     * Resolve the current global level/xp pair for the resolved profile, defaulting to level 1/0 xp.
     */
    private function resolveGlobalProgression(ResolvedGemWorldProfile $resolvedProfile): array
    {
        if ($resolvedProfile->isMapProfile()) {
            $progression = $resolvedProfile->mapProfile()->progression;
        } else {
            $progression = $resolvedProfile->locationProfile()->progression;
        }

        return [$progression?->level ?? 1, $progression?->xp ?? 0];
    }

    /**
     * Resolve the current personal level/xp pair for the Character/resolved profile, defaulting to level 1/0 xp.
     */
    private function resolvePersonalProgression(Character $character, ResolvedGemWorldProfile $resolvedProfile): array
    {
        if ($resolvedProfile->isMapProfile()) {
            $progression = CharacterGameMapGemProgression::where('character_id', $character->id)
                ->where('game_map_gem_paramter_id', $resolvedProfile->profileId())
                ->first();
        } else {
            $progression = CharacterGameLocationGemProgression::where('character_id', $character->id)
                ->where('game_location_gem_paramter_id', $resolvedProfile->profileId())
                ->first();
        }

        return [$progression?->level ?? 1, $progression?->xp ?? 0];
    }

    /**
     * Resolve the Character's active Gem Scroll rows for the resolved profile, with the fields the Fill/Remove UI needs.
     */
    private function activeScrollRows(Character $character, ResolvedGemWorldProfile $resolvedProfile): array
    {
        if ($resolvedProfile->isMapProfile()) {
            $rows = CharacterGameMapGemScroll::with('item')
                ->where('character_id', $character->id)
                ->where('game_map_gem_paramter_id', $resolvedProfile->profileId())
                ->active()
                ->get();

            return $this->formatScrollRows($rows, isMapRow: true);
        }

        $rows = CharacterGameLocationGemScroll::with('item')
            ->where('character_id', $character->id)
            ->where('game_location_gem_paramter_id', $resolvedProfile->profileId())
            ->active()
            ->get();

        return $this->formatScrollRows($rows, isMapRow: false);
    }

    /**
     * Format a collection of active Gem Scroll rows into the factual shape the Fill/Remove UI needs.
     */
    private function formatScrollRows(Collection $rows, bool $isMapRow): array
    {
        return $rows->map(fn (CharacterGameMapGemScroll|CharacterGameLocationGemScroll $row): array => [
            'id' => $row->id,
            'is_map_scroll' => $isMapRow,
            'item_id' => $row->item_id,
            'item_name' => $row->item->name,
            'gem_scroll_type' => $row->item->gem_scroll_type,
            'gem_scroll_currency_type' => $row->item->gem_scroll_currency_type,
            'gem_scroll_bonus' => $row->item->gem_scroll_bonus,
            'started_at' => $row->started_at,
            'expires_at' => $row->expires_at,
        ])->values()->toArray();
    }
}
