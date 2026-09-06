<?php

namespace App\Game\Gems\Values;

/**
 * Immutable resolved Map/Location Gem effects for a single Game Map/Location
 * gameplay context (normal Map, normal Location, Map Gem World, or Location
 * Gem World).
 */
class ResolvedAreaGemEffects
{
    /**
     * @param  ResolvedAreaGemMonsterEffects  $monsterEffects  Combined Monster combat effects and resolved atonement.
     * @param  ResolvedAreaGemRewardEffects  $rewardEffects  Combined player/reward effects.
     * @param  float  $characterPowerReduction  Combined Character power reduction contributed by Map Gems only.
     * @param  array<int, float>  $craftingSkillBonuses  Combined crafting bonus keyed by GameSkill id.
     * @param  ResolvedAreaGemRarityEffects  $rarityEffects  Resolved source-specific Unique/Mythic/Cosmic rarity modifiers.
     * @param  array<int, ResolvedAreaGemSource>  $sources  Source metadata for each contributing Gem.
     * @param  AreaGemContext|null  $contextType  The resolved Area Gem gameplay context.
     * @param  string|null  $contextLabel  Human readable label for the resolved context.
     * @param  int|null  $sourceGameMapId  The Game Map id whose persisted Monster population is used for this context.
     * @param  int|null  $currentGameMapId  The actual/effective Game Map id the Character/cache entry belongs to.
     * @param  string|null  $currentGameMapName  The actual/effective Game Map name.
     * @param  int|null  $locationId  The Location id, when the context is Location-based.
     * @param  string|null  $locationName  The Location name, when the context is Location-based.
     */
    public function __construct(
        private readonly ResolvedAreaGemMonsterEffects $monsterEffects,
        private readonly ResolvedAreaGemRewardEffects $rewardEffects,
        private readonly float $characterPowerReduction,
        private readonly array $craftingSkillBonuses = [],
        private readonly ResolvedAreaGemRarityEffects $rarityEffects = new ResolvedAreaGemRarityEffects(0.0, 0.0, 0.0),
        private readonly array $sources = [],
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
     */
    public static function none(): self
    {
        return new self(ResolvedAreaGemMonsterEffects::none(), ResolvedAreaGemRewardEffects::none(), 0.0);
    }

    /**
     * The resolved crafting bonus contributed for the given GameSkill id.
     */
    public function craftingSkillBonusFor(int $gameSkillId): float
    {
        return $this->craftingSkillBonuses[$gameSkillId] ?? 0.0;
    }

    /**
     * The resolved source-specific Unique/Mythic/Cosmic rarity modifiers for this context.
     */
    public function rarityEffects(): ResolvedAreaGemRarityEffects
    {
        return $this->rarityEffects;
    }

    /**
     * Resolve the value for the given closed Monster combat effect for this context.
     */
    public function monsterEffect(AreaGemMonsterEffect $effect): float
    {
        return $this->monsterEffects->effect($effect);
    }

    /**
     * Resolve the value for the given closed player/reward effect for this context.
     */
    public function rewardEffect(AreaGemRewardEffect $effect): float
    {
        return $this->rewardEffects->effect($effect);
    }

    /**
     * The resolved Monster atonement for this context.
     */
    public function monsterAtonement(): ResolvedAreaGemAtonement
    {
        return $this->monsterEffects->atonement();
    }

    /**
     * The typed resolved Monster combat effects and atonement for this context.
     */
    public function monsterEffects(): ResolvedAreaGemMonsterEffects
    {
        return $this->monsterEffects;
    }

    /**
     * The typed resolved player/reward effects for this context.
     */
    public function rewardEffects(): ResolvedAreaGemRewardEffects
    {
        return $this->rewardEffects;
    }

    /**
     * The combined Character power reduction for this context.
     */
    public function characterPowerReduction(): float
    {
        return $this->characterPowerReduction;
    }

    /**
     * The source metadata for each contributing Gem.
     *
     * @return array<int, ResolvedAreaGemSource>
     */
    public function sources(): array
    {
        return $this->sources;
    }

    /**
     * Determine whether this context contributes any Monster combat effects.
     */
    public function hasMonsterEffects(): bool
    {
        return $this->monsterEffects->hasAny();
    }

    /**
     * Determine whether this context contributes any player/reward effects.
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
     */
    public function hasAnyEffects(): bool
    {
        return $this->hasMonsterEffects() || $this->hasRewardEffects() || $this->characterPowerReduction > 0.0;
    }

    /**
     * The resolved context type for cache/detail metadata.
     */
    public function contextType(): ?AreaGemContext
    {
        return $this->contextType;
    }

    /**
     * The resolved human readable context label for cache/detail metadata.
     */
    public function contextLabel(): ?string
    {
        return $this->contextLabel;
    }

    /**
     * The Game Map id whose persisted Monster population is used for this context.
     */
    public function sourceGameMapId(): ?int
    {
        return $this->sourceGameMapId;
    }

    /**
     * The actual/effective Game Map id for this context.
     */
    public function currentGameMapId(): ?int
    {
        return $this->currentGameMapId;
    }

    /**
     * The actual/effective Game Map name for this context.
     */
    public function currentGameMapName(): ?string
    {
        return $this->currentGameMapName;
    }

    /**
     * The Location id for this context, when applicable.
     */
    public function locationId(): ?int
    {
        return $this->locationId;
    }

    /**
     * The Location name for this context, when applicable.
     */
    public function locationName(): ?string
    {
        return $this->locationName;
    }
}
