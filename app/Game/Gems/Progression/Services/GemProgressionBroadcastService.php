<?php

namespace App\Game\Gems\Progression\Services;

use App\Flare\Models\Character;
use App\Game\Gems\Progression\Events\GemProfileProgressionUpdateBroadcastEvent;
use App\Game\Gems\Progression\Events\GemProgressionUpdateBroadcastEvent;
use App\Game\Gems\Progression\Values\ResolvedGemWorldProfile;

class GemProgressionBroadcastService
{
    /**
     * @param GemWorldProfileResolver $gemWorldProfileResolver
     * @param GemProgressionReadService $gemProgressionReadService
     */
    public function __construct(
        private readonly GemWorldProfileResolver $gemWorldProfileResolver,
        private readonly GemProgressionReadService $gemProgressionReadService,
    ) {}

    /**
     * Build the compact Gem progression summary for the given Character/profile and broadcast the private and shared updates.
     *
     * @param Character $character
     * @param ResolvedGemWorldProfile $resolvedProfile
     * @param int $globalLevel
     * @param int $globalXp
     * @param int $personalLevel
     * @param int $personalXp
     */
    public function broadcastForProfile(Character $character, ResolvedGemWorldProfile $resolvedProfile, int $globalLevel, int $globalXp, int $personalLevel, int $personalXp): void
    {
        $payload = $this->gemProgressionReadService->compactStatusPayload($character, $resolvedProfile, $globalLevel, $globalXp, $personalLevel, $personalXp);
        $payload['currently_in_this_gem_world'] = $this->isCharacterCurrentlyInThisProfile($character, $resolvedProfile);

        event(new GemProgressionUpdateBroadcastEvent($payload, $character->user));

        event(new GemProfileProgressionUpdateBroadcastEvent(
            $resolvedProfile->type(),
            $resolvedProfile->profileId(),
            $payload['global'],
        ));
    }

    /**
     * Determine whether the Character's current generated Gem World profile matches the given resolved profile.
     *
     * @param Character $character
     * @param ResolvedGemWorldProfile $resolvedProfile
     * @return bool
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
