<?php

namespace App\Game\Gems\Progression\Services;

use App\Flare\Models\Character;
use App\Game\Gems\Progression\Events\GemProgressionUpdateBroadcastEvent;
use App\Game\Gems\Progression\Values\ResolvedGemWorldProfile;

/**
 * Builds and dispatches the one compact Gem progression/active-Scroll
 * broadcast update shared by the Gem World reward pipeline and the Gem
 * Scroll use/fill/remove actions.
 */
class GemProgressionBroadcastService
{
    public function __construct(
        private readonly GemWorldProfileResolver $gemWorldProfileResolver,
        private readonly GemScrollEffectService $gemScrollEffectService,
    ) {}

    /**
     * Build the compact Gem progression summary for the given Character/profile and broadcast it.
     */
    public function broadcastForProfile(Character $character, ResolvedGemWorldProfile $resolvedProfile, int $globalLevel, int $globalXp, int $personalLevel, int $personalXp): void
    {
        $scrollAggregate = $resolvedProfile->isMapProfile()
            ? $this->gemScrollEffectService->aggregateForMapProfile($character, $resolvedProfile->mapProfile())
            : $this->gemScrollEffectService->aggregateForLocationProfile($character, $resolvedProfile->locationProfile());

        $currentlyInThisGemWorld = $this->isCharacterCurrentlyInThisProfile($character, $resolvedProfile);

        event(new GemProgressionUpdateBroadcastEvent([
            'profile_type' => $resolvedProfile->type()->value,
            'profile_id' => $resolvedProfile->profileId(),
            'global_level' => $globalLevel,
            'global_xp' => $globalXp,
            'personal_level' => $personalLevel,
            'personal_xp' => $personalXp,
            'active_scroll_count' => $scrollAggregate->activeCount(),
            'active_scroll_total_bonus' => $scrollAggregate->totalPrimaryBonus(),
            'currently_in_this_gem_world' => $currentlyInThisGemWorld,
        ], $character->user));
    }

    /**
     * Determine whether the Character's current generated Gem World profile matches the given resolved profile.
     */
    private function isCharacterCurrentlyInThisProfile(Character $character, ResolvedGemWorldProfile $resolvedProfile): bool
    {
        $currentProfile = $this->gemWorldProfileResolver->resolveForCharacter($character);

        if (is_null($currentProfile)) {
            return false;
        }

        return $currentProfile->type() === $resolvedProfile->type() && $currentProfile->profileId() === $resolvedProfile->profileId();
    }
}
