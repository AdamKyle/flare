<?php

namespace App\Game\Gems\Progression\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterGameLocationGemProgression;
use App\Flare\Models\CharacterGameMapGemProgression;
use App\Flare\Models\GameLocationGemParamter;
use App\Flare\Models\GameMapGemParamter;
use App\Flare\Models\Gem;
use App\Game\Gems\Progression\Values\GemFieldProgressionBreakdown;
use App\Game\Gems\Progression\Values\SourceProgressionContext;
use App\Game\Gems\Services\AreaGemEffectService;
use App\Game\Gems\Values\AreaGemContext;
use App\Game\Gems\Values\AreaGemMonsterEffect;
use App\Game\Gems\Values\AreaGemRewardEffect;
use App\Game\Gems\Values\GemSourceType;
use App\Game\Gems\Values\ResolvedAreaGemAtonement;
use App\Game\Gems\Values\ResolvedAreaGemEffects;
use App\Game\Gems\Values\ResolvedAreaGemMonsterEffects;
use App\Game\Gems\Values\ResolvedAreaGemRarityEffects;
use App\Game\Gems\Values\ResolvedAreaGemRewardEffects;
use App\Game\Gems\Values\ResolvedAreaGemSource;

class CharacterAreaGemEffectService
{
    /**
     * @param AreaGemEffectService $areaGemEffectService
     * @param GemProgressionEffectService $gemProgressionEffectService
     */
    public function __construct(
        private readonly AreaGemEffectService $areaGemEffectService,
        private readonly GemProgressionEffectService $gemProgressionEffectService,
    ) {}

    /**
     * Resolve the Character-aware Gem effects for the Character's current
     * Map/Location context, adjusted by global/personal Gem progression.
     *
     * @param Character $character
     * @return ResolvedAreaGemEffects
     */
    public function resolveForCharacter(Character $character): ResolvedAreaGemEffects
    {
        $baseResolved = $this->areaGemEffectService->resolveForCharacter($character);

        if (empty($baseResolved->sources())) {
            return $baseResolved;
        }

        $sourceContexts = $this->resolveSourceContexts($character, $baseResolved->sources());

        if (empty($sourceContexts)) {
            return $baseResolved;
        }

        $inGeneratedGemWorld = in_array($baseResolved->contextType(), [AreaGemContext::MAP_GEM_WORLD, AreaGemContext::LOCATION_GEM_WORLD], true);

        return new ResolvedAreaGemEffects(
            monsterEffects: $inGeneratedGemWorld
                ? $this->overlayMonsterEffects($baseResolved->monsterEffects(), $sourceContexts)
                : $baseResolved->monsterEffects(),
            rewardEffects: $this->overlayRewardEffects($baseResolved->rewardEffects(), $sourceContexts),
            characterPowerReduction: $inGeneratedGemWorld
                ? $this->overlayCharacterPowerReduction($baseResolved->characterPowerReduction(), $sourceContexts)
                : $baseResolved->characterPowerReduction(),
            craftingSkillBonuses: $baseResolved->craftingSkillBonuses(),
            rarityEffects: $this->overlayRarityEffects($baseResolved->rarityEffects(), $sourceContexts),
            sources: $baseResolved->sources(),
            progressionLevelsBySource: $this->progressionLevelsBySource($sourceContexts),
            contextType: $baseResolved->contextType(),
            contextLabel: $baseResolved->contextLabel(),
            sourceGameMapId: $baseResolved->sourceGameMapId(),
            currentGameMapId: $baseResolved->currentGameMapId(),
            currentGameMapName: $baseResolved->currentGameMapName(),
            locationId: $baseResolved->locationId(),
            locationName: $baseResolved->locationName(),
        );
    }

    /**
     * Resolve the Character-facing base/global/personal/effective breakdown
     * for every currently applicable positive reward and rarity effect
     * field, in a single resolution pass over the Character's Gem sources.
     *
     * @param Character $character
     * @return array
     */
    public function resolveEffectBreakdownsForCharacter(Character $character): array
    {
        $baseResolved = $this->areaGemEffectService->resolveForCharacter($character);
        $sourceContexts = $this->resolveSourceContexts($character, $baseResolved->sources());

        return [
            'reward_effect_breakdown' => $this->rewardEffectBreakdowns($baseResolved->rewardEffects(), $sourceContexts),
            'rarity_effect_breakdown' => $this->rarityEffectBreakdowns($baseResolved->rarityEffects(), $sourceContexts),
        ];
    }

    /**
     * Build the applicable reward effect breakdowns for every closed reward effect field.
     *
     * @param ResolvedAreaGemRewardEffects $base
     * @param array $sourceContexts
     * @return array
     */
    private function rewardEffectBreakdowns(ResolvedAreaGemRewardEffects $base, array $sourceContexts): array
    {
        $breakdowns = [];

        foreach (AreaGemRewardEffect::cases() as $case) {
            $breakdown = $this->resolveFieldBreakdown(
                $case->value,
                $base->effect($case),
                $sourceContexts,
                fn (SourceProgressionContext $context): float => $context->rewardFieldValue($case),
            );

            if ($breakdown->isApplicable()) {
                $breakdowns[] = $breakdown->toArray();
            }
        }

        return $breakdowns;
    }

    /**
     * Build the applicable rarity effect breakdowns for the Unique/Mythic/Cosmic fields.
     *
     * @param ResolvedAreaGemRarityEffects $base
     * @param array $sourceContexts
     * @return array
     */
    private function rarityEffectBreakdowns(ResolvedAreaGemRarityEffects $base, array $sourceContexts): array
    {
        $rolledFieldResolvers = [
            'unique' => fn (SourceProgressionContext $context): float => $context->gem->unique_item_drop_chance_increase ?? 0.0,
            'mythic' => fn (SourceProgressionContext $context): float => $context->gem->mythic_item_drop_chance_increase ?? 0.0,
            'cosmic' => fn (SourceProgressionContext $context): float => $context->gem->cosmic_item_drop_chance_increase ?? 0.0,
        ];

        $baseValues = ['unique' => $base->unique(), 'mythic' => $base->mythic(), 'cosmic' => $base->cosmic()];

        $breakdowns = [];

        foreach ($rolledFieldResolvers as $field => $rolledFieldResolver) {
            $breakdown = $this->resolveFieldBreakdown($field, $baseValues[$field], $sourceContexts, $rolledFieldResolver);

            if ($breakdown->isApplicable()) {
                $breakdowns[] = $breakdown->toArray();
            }
        }

        return $breakdowns;
    }

    /**
     * Resolve the base/global/personal/effective breakdown for one additive
     * positive Gem effect field, summing every contributing source's own
     * global and personal progression bonus on top of the already-combined
     * base value.
     *
     * @param string $field
     * @param float $baseValue
     * @param array $sourceContexts
     * @param callable $rolledFieldResolver
     * @return GemFieldProgressionBreakdown
     */
    private function resolveFieldBreakdown(string $field, float $baseValue, array $sourceContexts, callable $rolledFieldResolver): GemFieldProgressionBreakdown
    {
        $globalBonus = 0.0;
        $personalBonus = 0.0;

        foreach ($sourceContexts as $context) {
            $sourceMultiplier = $context->source->rewardMultiplier();

            if ($sourceMultiplier <= 0.0) {
                continue;
            }

            $originalRolledValue = $rolledFieldResolver($context) * $sourceMultiplier;

            $globalBonus += $this->gemProgressionEffectService->globalPositiveBonus($originalRolledValue, $context->globalLevel);
            $personalBonus += $this->gemProgressionEffectService->personalBaseBonus($originalRolledValue, $context->personalLevel)
                + $this->gemProgressionEffectService->personalPositiveBandBonus($originalRolledValue, $context->personalLevel);
        }

        return new GemFieldProgressionBreakdown($field, $baseValue, $globalBonus, $personalBonus, $baseValue + $globalBonus + $personalBonus);
    }

    /**
     * Build the compact global/personal-level-by-source map consumed by Character-derived Monster cache key construction.
     *
     * @param array $sourceContexts
     * @return array
     */
    private function progressionLevelsBySource(array $sourceContexts): array
    {
        $levels = [];

        foreach ($sourceContexts as $context) {
            $key = ($context->source->type() === GemSourceType::MAP_GEM ? 'map-' : 'location-').$context->source->profileId();
            $levels[$key] = [
                'global' => $this->contributesMonsterTransformedReward($context) ? $context->globalLevel : null,
                'personal' => $context->personalLevel,
            ];
        }

        return $levels;
    }

    /**
     * Determine whether a source's rolled Gem has a non-zero value for a Monster-transformed reward field.
     *
     * @param SourceProgressionContext $context
     * @return bool
     */
    private function contributesMonsterTransformedReward(SourceProgressionContext $context): bool
    {
        if ($context->source->rewardMultiplier() <= 0.0) {
            return false;
        }

        foreach (AreaGemRewardEffect::monsterTransformedCases() as $rewardEffect) {
            if ($context->rewardFieldValue($rewardEffect) > 0.0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve the rolled Gem and global/personal progression level for every
     * contributing resolved source.
     *
     * @param Character $character
     * @param array $sources
     * @return array
     */
    private function resolveSourceContexts(Character $character, array $sources): array
    {
        $contexts = [];

        foreach ($sources as $source) {
            $context = $this->resolveSourceContext($character, $source);

            if (! is_null($context)) {
                $contexts[] = $context;
            }
        }

        return $contexts;
    }

    /**
     * Resolve the progression context for one resolved source, or null when
     * the rolled Gem or profile can no longer be found.
     *
     * @param Character $character
     * @param ResolvedAreaGemSource $source
     * @return ?SourceProgressionContext
     */
    private function resolveSourceContext(Character $character, ResolvedAreaGemSource $source): ?SourceProgressionContext
    {
        $gem = Gem::find($source->rolledGemId());

        if (is_null($gem)) {
            return null;
        }

        if ($source->type() === GemSourceType::MAP_GEM) {
            return $this->resolveMapSourceContext($character, $source, $gem);
        }

        return $this->resolveLocationSourceContext($character, $source, $gem);
    }

    /**
     * Resolve the progression context for a Map Gem source.
     *
     * @param Character $character
     * @param ResolvedAreaGemSource $source
     * @param Gem $gem
     * @return ?SourceProgressionContext
     */
    private function resolveMapSourceContext(Character $character, ResolvedAreaGemSource $source, Gem $gem): ?SourceProgressionContext
    {
        $profile = GameMapGemParamter::find($source->profileId());

        if (is_null($profile)) {
            return null;
        }

        $globalLevel = $profile->progression?->level ?? 1;
        $personalLevel = CharacterGameMapGemProgression::where('character_id', $character->id)
            ->where('game_map_gem_paramter_id', $profile->id)
            ->value('level') ?? 1;

        return new SourceProgressionContext($source, $gem, $globalLevel, $personalLevel);
    }

    /**
     * Resolve the progression context for a Location Gem source.
     *
     * @param Character $character
     * @param ResolvedAreaGemSource $source
     * @param Gem $gem
     * @return ?SourceProgressionContext
     */
    private function resolveLocationSourceContext(Character $character, ResolvedAreaGemSource $source, Gem $gem): ?SourceProgressionContext
    {
        $profile = GameLocationGemParamter::find($source->profileId());

        if (is_null($profile)) {
            return null;
        }

        $globalLevel = $profile->progression?->level ?? 1;
        $personalLevel = CharacterGameLocationGemProgression::where('character_id', $character->id)
            ->where('game_location_gem_paramter_id', $profile->id)
            ->value('level') ?? 1;

        return new SourceProgressionContext($source, $gem, $globalLevel, $personalLevel);
    }

    /**
     * Overlay global/personal progression on every additive reward effect
     * field, summing each contributing source's own progression bonus.
     *
     * @param ResolvedAreaGemRewardEffects $base
     * @param array $sourceContexts
     * @return ResolvedAreaGemRewardEffects
     */
    private function overlayRewardEffects(ResolvedAreaGemRewardEffects $base, array $sourceContexts): ResolvedAreaGemRewardEffects
    {
        $values = [];

        foreach (AreaGemRewardEffect::cases() as $case) {
            $values[$case->value] = $this->overlayPositiveField(
                $base->effect($case),
                $sourceContexts,
                fn (SourceProgressionContext $context): float => $context->rewardFieldValue($case),
            );
        }

        return new ResolvedAreaGemRewardEffects(
            $values[AreaGemRewardEffect::CHARACTER_XP_BONUS->value],
            $values[AreaGemRewardEffect::CHARACTER_CLASS_RANK_XP_BONUS->value],
            $values[AreaGemRewardEffect::KINGDOM_PASSIVE_TRAINING_REDUCTION->value],
            $values[AreaGemRewardEffect::GOLD_GAIN->value],
            $values[AreaGemRewardEffect::GOLD_DUST_GAIN->value],
            $values[AreaGemRewardEffect::SHARDS_GAIN->value],
            $values[AreaGemRewardEffect::COPPER_COIN_GAIN->value],
            $values[AreaGemRewardEffect::CHARACTER_CLASS_SPECIALTY_XP_GAIN->value],
            $values[AreaGemRewardEffect::ITEM_DROP_CHANCE_INCREASE->value],
            $values[AreaGemRewardEffect::ENEMY_QUEST_ITEM_DROP_CHANCE_INCREASE->value],
            $values[AreaGemRewardEffect::MONSTER_XP_INCREASE->value],
            $values[AreaGemRewardEffect::MONSTER_GOLD_DROP_INCREASE->value],
        );
    }

    /**
     * Overlay global/personal progression on the Unique/Mythic/Cosmic rolled
     * rarity values, summing each contributing source's own progression bonus.
     *
     * @param ResolvedAreaGemRarityEffects $base
     * @param array $sourceContexts
     * @return ResolvedAreaGemRarityEffects
     */
    private function overlayRarityEffects(ResolvedAreaGemRarityEffects $base, array $sourceContexts): ResolvedAreaGemRarityEffects
    {
        return new ResolvedAreaGemRarityEffects(
            $this->overlayPositiveField($base->unique(), $sourceContexts, fn (SourceProgressionContext $context): float => $context->gem->unique_item_drop_chance_increase ?? 0.0),
            $this->overlayPositiveField($base->mythic(), $sourceContexts, fn (SourceProgressionContext $context): float => $context->gem->mythic_item_drop_chance_increase ?? 0.0),
            $this->overlayPositiveField($base->cosmic(), $sourceContexts, fn (SourceProgressionContext $context): float => $context->gem->cosmic_item_drop_chance_increase ?? 0.0),
        );
    }

    /**
     * Resolve the effective value of one additive positive Gem effect field
     * by summing every contributing source's global and personal progression
     * bonus on top of the already-combined base value.
     *
     * @param float $baseValue
     * @param array $sourceContexts
     * @param callable $rolledFieldResolver
     * @return float
     */
    private function overlayPositiveField(float $baseValue, array $sourceContexts, callable $rolledFieldResolver): float
    {
        return $this->resolveFieldBreakdown('', $baseValue, $sourceContexts, $rolledFieldResolver)->effective();
    }

    /**
     * Overlay the personal negative progression bonus on every Monster
     * combat effect field, using only the single source that actually wins
     * Monster-effect precedence (Location over Map).
     *
     * @param ResolvedAreaGemMonsterEffects $base
     * @param array $sourceContexts
     * @return ResolvedAreaGemMonsterEffects
     */
    private function overlayMonsterEffects(ResolvedAreaGemMonsterEffects $base, array $sourceContexts): ResolvedAreaGemMonsterEffects
    {
        $winningContext = $this->resolveWinningMonsterContext($sourceContexts);

        if (is_null($winningContext)) {
            return $base;
        }

        $personalLevel = $winningContext->personalLevel;

        return new ResolvedAreaGemMonsterEffects(
            $this->gemProgressionEffectService->effectiveNegativeValue($base->effect(AreaGemMonsterEffect::ENEMY_STRENGTH_INCREASE), $personalLevel),
            $this->gemProgressionEffectService->effectiveNegativeValue($base->effect(AreaGemMonsterEffect::ENEMY_HEALING_INCREASE), $personalLevel),
            $this->gemProgressionEffectService->effectiveNegativeValue($base->effect(AreaGemMonsterEffect::ENEMY_SPELL_EVASION), $personalLevel),
            $this->gemProgressionEffectService->effectiveNegativeValue($base->effect(AreaGemMonsterEffect::ENEMY_AFFIX_RESISTANCE), $personalLevel),
            $this->gemProgressionEffectService->effectiveNegativeValue($base->effect(AreaGemMonsterEffect::ENEMY_ENTRANCING_CHANCE), $personalLevel),
            $this->gemProgressionEffectService->effectiveNegativeValue($base->effect(AreaGemMonsterEffect::ENEMY_DEVOURING_LIGHT_CHANCE), $personalLevel),
            $this->gemProgressionEffectService->effectiveNegativeValue($base->effect(AreaGemMonsterEffect::ENEMY_DEVOURING_DARKNESS_CHANCE), $personalLevel),
            $this->gemProgressionEffectService->effectiveNegativeValue($base->effect(AreaGemMonsterEffect::ENEMY_AMBUSH_CHANCE), $personalLevel),
            $this->gemProgressionEffectService->effectiveNegativeValue($base->effect(AreaGemMonsterEffect::ENEMY_AMBUSH_RESISTANCE), $personalLevel),
            $this->gemProgressionEffectService->effectiveNegativeValue($base->effect(AreaGemMonsterEffect::ENEMY_COUNTER_CHANCE), $personalLevel),
            $this->gemProgressionEffectService->effectiveNegativeValue($base->effect(AreaGemMonsterEffect::ENEMY_COUNTER_RESISTANCE), $personalLevel),
            $this->overlayAtonement($base, $personalLevel),
        );
    }

    /**
     * Overlay the personal negative progression bonus on the resolved
     * Monster atonement amount, when an atonement is actually active.
     *
     * @param ResolvedAreaGemMonsterEffects $base
     * @param int $personalLevel
     * @return ResolvedAreaGemAtonement
     */
    private function overlayAtonement(ResolvedAreaGemMonsterEffects $base, int $personalLevel): ResolvedAreaGemAtonement
    {
        $atonement = $base->atonement();

        if (! $atonement->hasEffect()) {
            return $atonement;
        }

        return new ResolvedAreaGemAtonement(
            $atonement->type(),
            $this->gemProgressionEffectService->effectiveNegativeValue($atonement->amount(), $personalLevel),
        );
    }

    /**
     * Resolve the single source context that actually wins Monster-effect
     * precedence: the Location Gem source when one is present, otherwise the
     * Map Gem source.
     *
     * @param array $sourceContexts
     * @return ?SourceProgressionContext
     */
    private function resolveWinningMonsterContext(array $sourceContexts): ?SourceProgressionContext
    {
        foreach ($sourceContexts as $context) {
            if ($context->source->type() === GemSourceType::LOCATION_GEM) {
                return $context;
            }
        }

        foreach ($sourceContexts as $context) {
            if ($context->source->type() === GemSourceType::MAP_GEM) {
                return $context;
            }
        }

        return null;
    }

    /**
     * Overlay the personal negative progression bonus on the Map-owned
     * Character power reduction, only when the Map source actually
     * contributes a reduction.
     *
     * @param float $baseReduction
     * @param array $sourceContexts
     * @return float
     */
    private function overlayCharacterPowerReduction(float $baseReduction, array $sourceContexts): float
    {
        if ($baseReduction <= 0.0) {
            return $baseReduction;
        }

        foreach ($sourceContexts as $context) {
            if ($context->source->type() !== GemSourceType::MAP_GEM) {
                continue;
            }

            if (($context->source->reductionMultiplier() ?? 0.0) <= 0.0) {
                continue;
            }

            return $this->gemProgressionEffectService->effectiveNegativeValue($baseReduction, $context->personalLevel);
        }

        return $baseReduction;
    }
}
