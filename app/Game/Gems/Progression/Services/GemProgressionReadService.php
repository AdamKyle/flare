<?php

namespace App\Game\Gems\Progression\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterGameLocationGemProgression;
use App\Flare\Models\CharacterGameMapGemProgression;
use App\Game\Gems\Progression\Values\GemProgressionBands;
use App\Game\Gems\Progression\Values\ResolvedGemWorldProfile;

class GemProgressionReadService
{
    /**
     * @param GemWorldProfileResolver $gemWorldProfileResolver
     * @param GemProgressionCurveService $gemProgressionCurveService
     * @param GemProgressionEffectService $gemProgressionEffectService
     * @param GemScrollEffectService $gemScrollEffectService
     * @param CharacterAreaGemEffectService $characterAreaGemEffectService
     */
    public function __construct(
        private readonly GemWorldProfileResolver $gemWorldProfileResolver,
        private readonly GemProgressionCurveService $gemProgressionCurveService,
        private readonly GemProgressionEffectService $gemProgressionEffectService,
        private readonly GemScrollEffectService $gemScrollEffectService,
        private readonly CharacterAreaGemEffectService $characterAreaGemEffectService,
    ) {}

    /**
     * Resolve the Character's current Gem progression status, or an empty result when no profile currently contributes.
     *
     * @param Character $character
     * @return array
     */
    public function currentStatus(Character $character): array
    {
        $resolvedProfile = $this->gemWorldProfileResolver->resolveForCharacter($character);

        if (is_null($resolvedProfile)) {
            return ['profile' => null];
        }

        [$globalLevel, $globalXp] = $this->resolveGlobalProgression($resolvedProfile);
        [$personalLevel, $personalXp] = $this->resolvePersonalProgression($character, $resolvedProfile);

        return array_merge(
            $this->compactStatusPayload($character, $resolvedProfile, $globalLevel, $globalXp, $personalLevel, $personalXp),
            $this->characterAreaGemEffectService->resolveEffectBreakdownsForCharacter($character),
        );
    }

    /**
     * Build the compact Gem progression dashboard payload shared by the status read endpoint and the live progression broadcasts.
     *
     * @param Character $character
     * @param ResolvedGemWorldProfile $resolvedProfile
     * @param int $globalLevel
     * @param int $globalXp
     * @param int $personalLevel
     * @param int $personalXp
     * @return array
     */
    public function compactStatusPayload(Character $character, ResolvedGemWorldProfile $resolvedProfile, int $globalLevel, int $globalXp, int $personalLevel, int $personalXp): array
    {
        $scrollAggregate = $resolvedProfile->isMapProfile()
            ? $this->gemScrollEffectService->aggregateForMapProfile($character, $resolvedProfile->mapProfile())
            : $this->gemScrollEffectService->aggregateForLocationProfile($character, $resolvedProfile->locationProfile());

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
                'next_unlock' => $this->resolveNextPersonalUnlock($personalLevel),
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
        ];
    }

    /**
     * Resolve the next personal progression unlock milestone still ahead of
     * the given personal level, or null once every milestone is reached.
     *
     * @param int $personalLevel
     * @return ?array
     */
    private function resolveNextPersonalUnlock(int $personalLevel): ?array
    {
        $unlocks = [
            GemProgressionBands::PERSONAL_RARITY_UNLOCK_LEVEL => 'Unique and Mythic Gem drop chance bonus',
            GemProgressionBands::PERSONAL_COSMIC_UNLOCK_LEVEL => 'Cosmic Gem drop chance bonus',
            GemProgressionBands::PERSONAL_ENHANCED_EQUIPMENT_LEVEL => 'Pre-gemmed enhanced equipment reward chance',
            GemProgressionBands::PERSONAL_MAX_LEVEL => 'Maximum personal Gem progression',
        ];

        foreach ($unlocks as $level => $description) {
            if ($personalLevel < $level) {
                return ['level' => $level, 'description' => $description];
            }
        }

        return null;
    }

    /**
     * Resolve the current global level/xp pair for the resolved profile, defaulting to level 1/0 xp.
     *
     * @param ResolvedGemWorldProfile $resolvedProfile
     * @return array
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
     *
     * @param Character $character
     * @param ResolvedGemWorldProfile $resolvedProfile
     * @return array
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
}
