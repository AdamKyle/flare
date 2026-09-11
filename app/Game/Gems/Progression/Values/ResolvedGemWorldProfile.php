<?php

namespace App\Game\Gems\Progression\Values;

use App\Flare\Models\GameLocationGemParamter;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameMapGemParamter;
use App\Game\Gems\Values\GemSourceType;

/**
 * Immutable resolved identity of the exact Map/Location Gem profile that
 * owns a generated Gem World.
 */
class ResolvedGemWorldProfile
{
    public function __construct(
        private readonly GemSourceType $type,
        private readonly GameMapGemParamter|GameLocationGemParamter $profile,
        private readonly GameMap $generatedGameMap,
    ) {}

    /**
     * Build a resolved profile owned by a Map Gem profile.
     */
    public static function forMapProfile(GameMapGemParamter $profile, GameMap $generatedGameMap): self
    {
        return new self(GemSourceType::MAP_GEM, $profile, $generatedGameMap);
    }

    /**
     * Build a resolved profile owned by a Location Gem profile.
     */
    public static function forLocationProfile(GameLocationGemParamter $profile, GameMap $generatedGameMap): self
    {
        return new self(GemSourceType::LOCATION_GEM, $profile, $generatedGameMap);
    }

    /**
     * Whether this generated Gem World is owned by a Map or Location Gem profile.
     */
    public function type(): GemSourceType
    {
        return $this->type;
    }

    /**
     * The owning Gem profile id.
     */
    public function profileId(): int
    {
        return $this->profile->id;
    }

    /**
     * Determine whether this resolved profile is a Map Gem profile.
     */
    public function isMapProfile(): bool
    {
        return $this->type === GemSourceType::MAP_GEM;
    }

    /**
     * Determine whether this resolved profile is a Location Gem profile.
     */
    public function isLocationProfile(): bool
    {
        return $this->type === GemSourceType::LOCATION_GEM;
    }

    /**
     * The owning Map Gem profile, when this is a Map Gem World.
     */
    public function mapProfile(): ?GameMapGemParamter
    {
        return $this->isMapProfile() ? $this->profile : null;
    }

    /**
     * The owning Location Gem profile, when this is a Location Gem World.
     */
    public function locationProfile(): ?GameLocationGemParamter
    {
        return $this->isLocationProfile() ? $this->profile : null;
    }

    /**
     * The generated Gem World Game Map.
     */
    public function generatedGameMap(): GameMap
    {
        return $this->generatedGameMap;
    }
}
