<?php

namespace App\Game\Gems\Values;

/**
 * Immutable resolved source metadata for one Gem contributing to a resolved
 * Area Gem effect result.
 */
class ResolvedAreaGemSource
{
    /**
     * @param  GemSourceType  $type  The Gem profile source type.
     * @param  int  $profileId  The contributing Map/Location Gem profile id.
     * @param  string  $profileName  The contributing Map/Location Gem profile name.
     * @param  int  $rolledGemId  The id of the currently rolled Gem for this profile.
     * @param  string  $rolledGemName  The name of the currently rolled Gem for this profile.
     * @param  float  $monsterMultiplier  The Monster combat effect multiplier applied for this source.
     * @param  float  $rewardMultiplier  The player/reward effect multiplier applied for this source.
     * @param  float|null  $reductionMultiplier  The Character power reduction multiplier applied for this source, when applicable.
     * @param  int|null  $gameMapId  The Game Map id associated with this source, when applicable.
     * @param  string|null  $gameMapName  The Game Map name associated with this source, when applicable.
     * @param  int|null  $locationId  The Location id associated with this source, when applicable.
     * @param  string|null  $locationName  The Location name associated with this source, when applicable.
     */
    public function __construct(
        private readonly GemSourceType $type,
        private readonly int $profileId,
        private readonly string $profileName,
        private readonly int $rolledGemId,
        private readonly string $rolledGemName,
        private readonly float $monsterMultiplier,
        private readonly float $rewardMultiplier,
        private readonly ?float $reductionMultiplier,
        private readonly ?int $gameMapId,
        private readonly ?string $gameMapName,
        private readonly ?int $locationId,
        private readonly ?string $locationName,
    ) {}

    /**
     * The Gem profile source type.
     */
    public function type(): GemSourceType
    {
        return $this->type;
    }

    /**
     * The contributing Map/Location Gem profile id.
     */
    public function profileId(): int
    {
        return $this->profileId;
    }

    /**
     * The contributing Map/Location Gem profile name.
     */
    public function profileName(): string
    {
        return $this->profileName;
    }

    /**
     * The id of the currently rolled Gem for this profile.
     */
    public function rolledGemId(): int
    {
        return $this->rolledGemId;
    }

    /**
     * The name of the currently rolled Gem for this profile.
     */
    public function rolledGemName(): string
    {
        return $this->rolledGemName;
    }

    /**
     * The Monster combat effect multiplier applied for this source.
     */
    public function monsterMultiplier(): float
    {
        return $this->monsterMultiplier;
    }

    /**
     * The player/reward effect multiplier applied for this source.
     */
    public function rewardMultiplier(): float
    {
        return $this->rewardMultiplier;
    }

    /**
     * The Character power reduction multiplier applied for this source, when applicable.
     */
    public function reductionMultiplier(): ?float
    {
        return $this->reductionMultiplier;
    }

    /**
     * The Game Map id associated with this source, when applicable.
     */
    public function gameMapId(): ?int
    {
        return $this->gameMapId;
    }

    /**
     * The Game Map name associated with this source, when applicable.
     */
    public function gameMapName(): ?string
    {
        return $this->gameMapName;
    }

    /**
     * The Location id associated with this source, when applicable.
     */
    public function locationId(): ?int
    {
        return $this->locationId;
    }

    /**
     * The Location name associated with this source, when applicable.
     */
    public function locationName(): ?string
    {
        return $this->locationName;
    }

    /**
     * Serialize this source into the legacy/cache compatible field shape.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'profile_id' => $this->profileId,
            'profile_name' => $this->profileName,
            'rolled_gem_id' => $this->rolledGemId,
            'rolled_gem_name' => $this->rolledGemName,
            'monster_multiplier' => $this->monsterMultiplier,
            'reward_multiplier' => $this->rewardMultiplier,
            'reduction_multiplier' => $this->reductionMultiplier,
            'game_map_id' => $this->gameMapId,
            'game_map_name' => $this->gameMapName,
            'location_id' => $this->locationId,
            'location_name' => $this->locationName,
        ];
    }
}
