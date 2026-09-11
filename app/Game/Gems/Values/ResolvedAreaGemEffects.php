<?php

namespace App\Game\Gems\Values;

class ResolvedAreaGemEffects
{
    /**
     * @param ResolvedAreaGemMonsterEffects $monsterEffects
     * @param ResolvedAreaGemRewardEffects $rewardEffects
     * @param float $characterPowerReduction
     * @param array $craftingSkillBonuses
     * @param ResolvedAreaGemRarityEffects $rarityEffects
     * @param array $sources
     * @param array $progressionLevelsBySource
     * @param ?AreaGemContext $contextType
     * @param ?string $contextLabel
     * @param ?int $sourceGameMapId
     * @param ?int $currentGameMapId
     * @param ?string $currentGameMapName
     * @param ?int $locationId
     * @param ?string $locationName
     */
    public function __construct(
        private readonly ResolvedAreaGemMonsterEffects $monsterEffects,
        private readonly ResolvedAreaGemRewardEffects $rewardEffects,
        private readonly float $characterPowerReduction,
        private readonly array $craftingSkillBonuses = [],
        private readonly ResolvedAreaGemRarityEffects $rarityEffects = new ResolvedAreaGemRarityEffects(0.0, 0.0, 0.0),
        private readonly array $sources = [],
        private readonly array $progressionLevelsBySource = [],
        private readonly ?AreaGemContext $contextType = null,
        private readonly ?string $contextLabel = null,
        private readonly ?int $sourceGameMapId = null,
        private readonly ?int $currentGameMapId = null,
        private readonly ?string $currentGameMapName = null,
        private readonly ?int $locationId = null,
        private readonly ?string $locationName = null,
    ) {}

    /**
     * Build a no-effect resolved result for Gem-neutral contexts and Monsters.
     *
     * @return self
     */
    public static function none(): self
    {
        return new self(ResolvedAreaGemMonsterEffects::none(), ResolvedAreaGemRewardEffects::none(), 0.0);
    }

    /**
     * The resolved crafting bonus contributed for the given GameSkill id.
     *
     * @param int $gameSkillId
     * @return float
     */
    public function craftingSkillBonusFor(int $gameSkillId): float
    {
        return $this->craftingSkillBonuses[$gameSkillId] ?? 0.0;
    }

    /**
     * The already-resolved crafting bonus keyed by GameSkill id.
     *
     * @return array
     */
    public function craftingSkillBonuses(): array
    {
        return $this->craftingSkillBonuses;
    }

    /**
     * The resolved Unique/Mythic/Cosmic rarity modifiers for this context.
     *
     * @return ResolvedAreaGemRarityEffects
     */
    public function rarityEffects(): ResolvedAreaGemRarityEffects
    {
        return $this->rarityEffects;
    }

    /**
     * Resolve the value for the given closed Monster combat effect for this context.
     *
     * @param AreaGemMonsterEffect $effect
     * @return float
     */
    public function monsterEffect(AreaGemMonsterEffect $effect): float
    {
        return $this->monsterEffects->effect($effect);
    }

    /**
     * Resolve the value for the given closed player/reward effect for this context.
     *
     * @param AreaGemRewardEffect $effect
     * @return float
     */
    public function rewardEffect(AreaGemRewardEffect $effect): float
    {
        return $this->rewardEffects->effect($effect);
    }

    /**
     * The resolved Monster atonement for this context.
     *
     * @return ResolvedAreaGemAtonement
     */
    public function monsterAtonement(): ResolvedAreaGemAtonement
    {
        return $this->monsterEffects->atonement();
    }

    /**
     * The typed resolved Monster combat effects and atonement for this context.
     *
     * @return ResolvedAreaGemMonsterEffects
     */
    public function monsterEffects(): ResolvedAreaGemMonsterEffects
    {
        return $this->monsterEffects;
    }

    /**
     * The typed resolved player/reward effects for this context.
     *
     * @return ResolvedAreaGemRewardEffects
     */
    public function rewardEffects(): ResolvedAreaGemRewardEffects
    {
        return $this->rewardEffects;
    }

    /**
     * The combined Character power reduction for this context.
     *
     * @return float
     */
    public function characterPowerReduction(): float
    {
        return $this->characterPowerReduction;
    }

    /**
     * The source metadata for each contributing Gem.
     *
     * @return array
     */
    public function sources(): array
    {
        return $this->sources;
    }

    /**
     * Resolve the already-resolved personal progression level for the given source.
     *
     * @param ResolvedAreaGemSource $source
     * @return int
     */
    public function personalLevelForSource(ResolvedAreaGemSource $source): int
    {
        return $this->progressionLevelsBySource[$this->sourceKey($source)]['personal'] ?? 1;
    }

    /**
     * Resolve the already-resolved global progression level for the given source, or null when that source cannot affect a Monster-transformed reward field.
     *
     * @param ResolvedAreaGemSource $source
     * @return ?int
     */
    public function globalLevelForSource(ResolvedAreaGemSource $source): ?int
    {
        return $this->progressionLevelsBySource[$this->sourceKey($source)]['global'] ?? null;
    }

    /**
     * Build the progression-levels-by-source lookup key for the given source.
     *
     * @param ResolvedAreaGemSource $source
     * @return string
     */
    private function sourceKey(ResolvedAreaGemSource $source): string
    {
        return ($source->type() === GemSourceType::MAP_GEM ? 'map-' : 'location-').$source->profileId();
    }

    /**
     * Determine whether this context contributes any Monster combat effects.
     *
     * @return bool
     */
    public function hasMonsterEffects(): bool
    {
        return $this->monsterEffects->hasAny();
    }

    /**
     * Determine whether this context contributes any player/reward effects.
     *
     * @return bool
     */
    public function hasRewardEffects(): bool
    {
        if ($this->rewardEffects->hasAny()) {
            return true;
        }

        foreach ($this->craftingSkillBonuses as $bonus) {
            if ($bonus > 0.0) {
                return true;
            }
        }

        return $this->rarityEffects->unique() > 0.0
            || $this->rarityEffects->mythic() > 0.0
            || $this->rarityEffects->cosmic() > 0.0;
    }

    /**
     * Determine whether this context contributes any Gem effect at all.
     *
     * @return bool
     */
    public function hasAnyEffects(): bool
    {
        return $this->hasMonsterEffects() || $this->hasRewardEffects() || $this->characterPowerReduction > 0.0;
    }

    /**
     * The resolved context type for cache/detail metadata.
     *
     * @return ?AreaGemContext
     */
    public function contextType(): ?AreaGemContext
    {
        return $this->contextType;
    }

    /**
     * The resolved human readable context label for cache/detail metadata.
     *
     * @return ?string
     */
    public function contextLabel(): ?string
    {
        return $this->contextLabel;
    }

    /**
     * The Game Map id whose persisted Monster population is used for this context.
     *
     * @return ?int
     */
    public function sourceGameMapId(): ?int
    {
        return $this->sourceGameMapId;
    }

    /**
     * The actual/effective Game Map id for this context.
     *
     * @return ?int
     */
    public function currentGameMapId(): ?int
    {
        return $this->currentGameMapId;
    }

    /**
     * The actual/effective Game Map name for this context.
     *
     * @return ?string
     */
    public function currentGameMapName(): ?string
    {
        return $this->currentGameMapName;
    }

    /**
     * The Location id for this context, when applicable.
     *
     * @return ?int
     */
    public function locationId(): ?int
    {
        return $this->locationId;
    }

    /**
     * The Location name for this context, when applicable.
     *
     * @return ?string
     */
    public function locationName(): ?string
    {
        return $this->locationName;
    }
}
