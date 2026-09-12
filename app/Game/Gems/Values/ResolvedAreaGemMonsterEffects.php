<?php

namespace App\Game\Gems\Values;

/**
 * Immutable resolved Monster combat effects and atonement for a single Area
 * Gem context.
 */
class ResolvedAreaGemMonsterEffects
{
    /**
     * @param float $enemyStrengthIncrease Resolved enemy strength increase.
     * @param float $enemyHealingIncrease Resolved enemy healing increase.
     * @param float $enemySpellEvasion Resolved enemy spell evasion increase.
     * @param float $enemyAffixResistance Resolved enemy affix resistance increase.
     * @param float $enemyEntrancingChance Resolved enemy entrancing chance increase.
     * @param float $enemyDevouringLightChance Resolved enemy devouring light chance increase.
     * @param float $enemyDevouringDarknessChance Resolved enemy devouring darkness chance increase.
     * @param float $enemyAmbushChance Resolved enemy ambush chance increase.
     * @param float $enemyAmbushResistance Resolved enemy ambush resistance increase.
     * @param float $enemyCounterChance Resolved enemy counter chance increase.
     * @param float $enemyCounterResistance Resolved enemy counter resistance increase.
     * @param ResolvedAreaGemAtonement $atonement Resolved Monster elemental atonement.
     */
    public function __construct(
        private readonly float $enemyStrengthIncrease,
        private readonly float $enemyHealingIncrease,
        private readonly float $enemySpellEvasion,
        private readonly float $enemyAffixResistance,
        private readonly float $enemyEntrancingChance,
        private readonly float $enemyDevouringLightChance,
        private readonly float $enemyDevouringDarknessChance,
        private readonly float $enemyAmbushChance,
        private readonly float $enemyAmbushResistance,
        private readonly float $enemyCounterChance,
        private readonly float $enemyCounterResistance,
        private readonly ResolvedAreaGemAtonement $atonement,
    ) {}

    /**
     * Build a no-effect resolved Monster effects result.
     */
    public static function none(): self
    {
        return new self(0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, ResolvedAreaGemAtonement::none());
    }

    /**
     * Resolve the value for the given closed Monster effect.
     */
    public function effect(AreaGemMonsterEffect $effect): float
    {
        return match ($effect) {
            AreaGemMonsterEffect::ENEMY_STRENGTH_INCREASE => $this->enemyStrengthIncrease,
            AreaGemMonsterEffect::ENEMY_HEALING_INCREASE => $this->enemyHealingIncrease,
            AreaGemMonsterEffect::ENEMY_SPELL_EVASION => $this->enemySpellEvasion,
            AreaGemMonsterEffect::ENEMY_AFFIX_RESISTANCE => $this->enemyAffixResistance,
            AreaGemMonsterEffect::ENEMY_ENTRANCING_CHANCE => $this->enemyEntrancingChance,
            AreaGemMonsterEffect::ENEMY_DEVOURING_LIGHT_CHANCE => $this->enemyDevouringLightChance,
            AreaGemMonsterEffect::ENEMY_DEVOURING_DARKNESS_CHANCE => $this->enemyDevouringDarknessChance,
            AreaGemMonsterEffect::ENEMY_AMBUSH_CHANCE => $this->enemyAmbushChance,
            AreaGemMonsterEffect::ENEMY_AMBUSH_RESISTANCE => $this->enemyAmbushResistance,
            AreaGemMonsterEffect::ENEMY_COUNTER_CHANCE => $this->enemyCounterChance,
            AreaGemMonsterEffect::ENEMY_COUNTER_RESISTANCE => $this->enemyCounterResistance,
        };
    }

    /**
     * The resolved atonement for this context.
     */
    public function atonement(): ResolvedAreaGemAtonement
    {
        return $this->atonement;
    }

    /**
     * Determine whether this result contributes any positive Monster combat effect or atonement.
     */
    public function hasAny(): bool
    {
        $effects = [
            $this->enemyStrengthIncrease,
            $this->enemyHealingIncrease,
            $this->enemySpellEvasion,
            $this->enemyAffixResistance,
            $this->enemyEntrancingChance,
            $this->enemyDevouringLightChance,
            $this->enemyDevouringDarknessChance,
            $this->enemyAmbushChance,
            $this->enemyAmbushResistance,
            $this->enemyCounterChance,
            $this->enemyCounterResistance,
        ];

        foreach ($effects as $effect) {
            if ($effect > 0.0) {
                return true;
            }
        }

        return $this->atonement->hasEffect();
    }

    /**
     * Serialize this result into the legacy/cache compatible field shape.
     *
     * @return array
     */
    public function toArray(): array
    {
        return array_merge([
            AreaGemMonsterEffect::ENEMY_STRENGTH_INCREASE->value => $this->enemyStrengthIncrease,
            AreaGemMonsterEffect::ENEMY_HEALING_INCREASE->value => $this->enemyHealingIncrease,
            AreaGemMonsterEffect::ENEMY_SPELL_EVASION->value => $this->enemySpellEvasion,
            AreaGemMonsterEffect::ENEMY_AFFIX_RESISTANCE->value => $this->enemyAffixResistance,
            AreaGemMonsterEffect::ENEMY_ENTRANCING_CHANCE->value => $this->enemyEntrancingChance,
            AreaGemMonsterEffect::ENEMY_DEVOURING_LIGHT_CHANCE->value => $this->enemyDevouringLightChance,
            AreaGemMonsterEffect::ENEMY_DEVOURING_DARKNESS_CHANCE->value => $this->enemyDevouringDarknessChance,
            AreaGemMonsterEffect::ENEMY_AMBUSH_CHANCE->value => $this->enemyAmbushChance,
            AreaGemMonsterEffect::ENEMY_AMBUSH_RESISTANCE->value => $this->enemyAmbushResistance,
            AreaGemMonsterEffect::ENEMY_COUNTER_CHANCE->value => $this->enemyCounterChance,
            AreaGemMonsterEffect::ENEMY_COUNTER_RESISTANCE->value => $this->enemyCounterResistance,
        ], $this->atonement->toArray());
    }
}
