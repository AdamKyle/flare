<?php

namespace App\Game\BattleRewardProcessing\Values;

use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestSourceType;
use App\Game\Gems\Values\ResolvedAreaGemEffects;
use App\Game\Maps\Values\LocationType;

class BattleRewardSharedContext
{
    /**
     * @param ResolvedAreaGemEffects $resolvedAreaGemEffects
     * @param BattleRewardRequestSourceType $sourceType
     * @param int $killCount
     * @param bool $isWeeklyMonster
     * @param bool $isGeneratedGemWorld
     * @param ?int $explorationLogId
     * @param ?int $locationId
     * @param ?LocationType $locationType
     * @param ?string $locationName
     * @param ?int $locationX
     * @param ?int $locationY
     * @param ?int $locationGameMapId
     */
    public function __construct(
        private readonly ResolvedAreaGemEffects $resolvedAreaGemEffects,
        private readonly BattleRewardRequestSourceType $sourceType,
        private readonly int $killCount,
        private readonly bool $isWeeklyMonster,
        private readonly bool $isGeneratedGemWorld,
        private readonly ?int $explorationLogId = null,
        private readonly ?int $locationId = null,
        private readonly ?LocationType $locationType = null,
        private readonly ?string $locationName = null,
        private readonly ?int $locationX = null,
        private readonly ?int $locationY = null,
        private readonly ?int $locationGameMapId = null,
    ) {}

    /**
     * The pre-Gem-World resolved area Gem effects for the request's Character.
     *
     * @return ResolvedAreaGemEffects
     */
    public function resolvedAreaGemEffects(): ResolvedAreaGemEffects
    {
        return $this->resolvedAreaGemEffects;
    }

    /**
     * The request's source type.
     *
     * @return BattleRewardRequestSourceType
     */
    public function sourceType(): BattleRewardRequestSourceType
    {
        return $this->sourceType;
    }

    /**
     * The normalized positive kill count for this request.
     *
     * @return int
     */
    public function killCount(): int
    {
        return $this->killCount;
    }

    /**
     * Whether the Monster being fought is a weekly monster.
     *
     * @return bool
     */
    public function isWeeklyMonster(): bool
    {
        return $this->isWeeklyMonster;
    }

    /**
     * Whether the Character's current Game Map is a generated Gem World.
     *
     * @return bool
     */
    public function isGeneratedGemWorld(): bool
    {
        return $this->isGeneratedGemWorld;
    }

    /**
     * The Exploration log id for this request, when applicable.
     *
     * @return ?int
     */
    public function explorationLogId(): ?int
    {
        return $this->explorationLogId;
    }

    /**
     * The Character's currently resolved Location id, when applicable.
     *
     * @return ?int
     */
    public function locationId(): ?int
    {
        return $this->locationId;
    }

    /**
     * The Character's currently resolved Location type, when applicable.
     *
     * @return ?LocationType
     */
    public function locationType(): ?LocationType
    {
        return $this->locationType;
    }

    /**
     * The Character's currently resolved Location name, when applicable.
     *
     * @return ?string
     */
    public function locationName(): ?string
    {
        return $this->locationName;
    }

    /**
     * The Character's currently resolved Location x coordinate, when applicable.
     *
     * @return ?int
     */
    public function locationX(): ?int
    {
        return $this->locationX;
    }

    /**
     * The Character's currently resolved Location y coordinate, when applicable.
     *
     * @return ?int
     */
    public function locationY(): ?int
    {
        return $this->locationY;
    }

    /**
     * The Character's currently resolved Location Game Map id, when applicable.
     *
     * @return ?int
     */
    public function locationGameMapId(): ?int
    {
        return $this->locationGameMapId;
    }
}
