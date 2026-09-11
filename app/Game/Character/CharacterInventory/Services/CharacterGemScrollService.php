<?php

namespace App\Game\Character\CharacterInventory\Services;

use App\Flare\Models\AlchemyBagSlot;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterGameLocationGemProgression;
use App\Flare\Models\CharacterGameLocationGemScroll;
use App\Flare\Models\CharacterGameMapGemProgression;
use App\Flare\Models\CharacterGameMapGemScroll;
use App\Flare\Models\Item;
use App\Game\Core\Events\UpdateCharacterInventoryCountEvent;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Gems\Progression\Services\GemProgressionBroadcastService;
use App\Game\Gems\Progression\Services\GemScrollEffectService;
use App\Game\Gems\Progression\Services\GemWorldProfileResolver;
use App\Game\Gems\Progression\Values\GemScrollAggregate;
use App\Game\Gems\Progression\Values\ResolvedGemWorldProfile;

/**
 * Applies/fills/removes a Character's Gem Scrolls. Unused Scroll Items live
 * physically in the existing Alchemy Bag; this service is the only mutation
 * path for a Gem Scroll and never uses `UseItemService` or the ordinary
 * Character Boon system.
 */
class CharacterGemScrollService
{
    use ResponseBuilder;

    public function __construct(
        private readonly GemWorldProfileResolver $gemWorldProfileResolver,
        private readonly GemScrollEffectService $gemScrollEffectService,
        private readonly GemProgressionBroadcastService $gemProgressionBroadcastService,
    ) {}

    /**
     * Activate an owned Alchemy Bag Gem Scroll for the Character's current generated Gem World.
     */
    public function useScroll(Character $character, AlchemyBagSlot $slot): array
    {
        $ownershipError = $this->validateOwnedScrollSlot($character, $slot);

        if (! is_null($ownershipError)) {
            return $ownershipError;
        }

        $resolvedProfile = $this->gemWorldProfileResolver->resolveForCharacter($character);

        if (is_null($resolvedProfile)) {
            return $this->errorResult('You must be inside a generated Gem World to use a Gem Scroll.');
        }

        $aggregate = $this->resolveAggregate($character, $resolvedProfile);
        $bonus = $slot->item->gem_scroll_bonus ?? 0.0;

        if (! $aggregate->canActivate($bonus)) {
            return $this->errorResult('Activating this Scroll would exceed your 2000% active bonus cap for this Gem. Remaining capacity: '.$this->formatPercent($aggregate->remainingCapacity()).'.');
        }

        $startedAt = now();
        $expiresAt = $startedAt->clone()->addMinutes($slot->item->lasts_for);

        if ($resolvedProfile->isMapProfile()) {
            CharacterGameMapGemScroll::create([
                'character_id' => $character->id,
                'game_map_gem_paramter_id' => $resolvedProfile->profileId(),
                'item_id' => $slot->item_id,
                'started_at' => $startedAt,
                'expires_at' => $expiresAt,
            ]);
        } else {
            CharacterGameLocationGemScroll::create([
                'character_id' => $character->id,
                'game_location_gem_paramter_id' => $resolvedProfile->profileId(),
                'item_id' => $slot->item_id,
                'started_at' => $startedAt,
                'expires_at' => $expiresAt,
            ]);
        }

        $slot->delete();

        event(new UpdateCharacterInventoryCountEvent($character));

        $this->broadcastProgress($character, $resolvedProfile);

        return $this->successResult(['message' => 'Gem Scroll activated.']);
    }

    /**
     * Extend an owned active Map Gem Scroll's duration using an owned Alchemy Bag fill Scroll.
     */
    public function fillMapScroll(Character $character, CharacterGameMapGemScroll $activeScroll, AlchemyBagSlot $fillSlot): array
    {
        if ($activeScroll->character_id !== $character->id) {
            return $this->errorResult('No. Not yours!');
        }

        $resolvedProfile = $this->gemWorldProfileResolver->resolveForCharacter($character);

        if (is_null($resolvedProfile) || ! $resolvedProfile->isMapProfile() || $resolvedProfile->profileId() !== $activeScroll->game_map_gem_paramter_id) {
            return $this->errorResult('You must be inside the exact Gem World this Scroll is active for to fill it up.');
        }

        $fillValidationError = $this->validateFillSlot($character, $fillSlot, $activeScroll->item);

        if (! is_null($fillValidationError)) {
            return $fillValidationError;
        }

        if ($activeScroll->expires_at->isPast()) {
            return $this->errorResult('This Scroll has already expired. Use a new Scroll instead.');
        }

        $activeScroll->update([
            'expires_at' => $activeScroll->expires_at->clone()->addMinutes($fillSlot->item->lasts_for),
        ]);

        $fillSlot->delete();

        event(new UpdateCharacterInventoryCountEvent($character));

        $this->broadcastProgress($character, $resolvedProfile);

        return $this->successResult(['message' => 'Gem Scroll filled up.']);
    }

    /**
     * Extend an owned active Location Gem Scroll's duration using an owned Alchemy Bag fill Scroll.
     */
    public function fillLocationScroll(Character $character, CharacterGameLocationGemScroll $activeScroll, AlchemyBagSlot $fillSlot): array
    {
        if ($activeScroll->character_id !== $character->id) {
            return $this->errorResult('No. Not yours!');
        }

        $resolvedProfile = $this->gemWorldProfileResolver->resolveForCharacter($character);

        if (is_null($resolvedProfile) || ! $resolvedProfile->isLocationProfile() || $resolvedProfile->profileId() !== $activeScroll->game_location_gem_paramter_id) {
            return $this->errorResult('You must be inside the exact Gem World this Scroll is active for to fill it up.');
        }

        $fillValidationError = $this->validateFillSlot($character, $fillSlot, $activeScroll->item);

        if (! is_null($fillValidationError)) {
            return $fillValidationError;
        }

        if ($activeScroll->expires_at->isPast()) {
            return $this->errorResult('This Scroll has already expired. Use a new Scroll instead.');
        }

        $activeScroll->update([
            'expires_at' => $activeScroll->expires_at->clone()->addMinutes($fillSlot->item->lasts_for),
        ]);

        $fillSlot->delete();

        event(new UpdateCharacterInventoryCountEvent($character));

        $this->broadcastProgress($character, $resolvedProfile);

        return $this->successResult(['message' => 'Gem Scroll filled up.']);
    }

    /**
     * Remove an owned active Map Gem Scroll without refunding its duration or Item.
     */
    public function removeMapScroll(Character $character, CharacterGameMapGemScroll $activeScroll): array
    {
        if ($activeScroll->character_id !== $character->id) {
            return $this->errorResult('No. Not yours!');
        }

        $profile = $activeScroll->gameMapGemParamter;

        $activeScroll->delete();

        if (! is_null($profile) && ! is_null($profile->generatedMap)) {
            $this->broadcastProgress($character, ResolvedGemWorldProfile::forMapProfile($profile, $profile->generatedMap));
        }

        return $this->successResult(['message' => 'Gem Scroll removed.']);
    }

    /**
     * Remove an owned active Location Gem Scroll without refunding its duration or Item.
     */
    public function removeLocationScroll(Character $character, CharacterGameLocationGemScroll $activeScroll): array
    {
        if ($activeScroll->character_id !== $character->id) {
            return $this->errorResult('No. Not yours!');
        }

        $profile = $activeScroll->gameLocationGemParamter;

        $activeScroll->delete();

        if (! is_null($profile) && ! is_null($profile->generatedMap)) {
            $this->broadcastProgress($character, ResolvedGemWorldProfile::forLocationProfile($profile, $profile->generatedMap));
        }

        return $this->successResult(['message' => 'Gem Scroll removed.']);
    }

    /**
     * Broadcast the Character's current compact Gem progression summary for the given profile.
     */
    private function broadcastProgress(Character $character, ResolvedGemWorldProfile $resolvedProfile): void
    {
        if ($resolvedProfile->isMapProfile()) {
            $progression = CharacterGameMapGemProgression::where('character_id', $character->id)
                ->where('game_map_gem_paramter_id', $resolvedProfile->profileId())
                ->first();
            $globalProgression = $resolvedProfile->mapProfile()->progression;
        } else {
            $progression = CharacterGameLocationGemProgression::where('character_id', $character->id)
                ->where('game_location_gem_paramter_id', $resolvedProfile->profileId())
                ->first();
            $globalProgression = $resolvedProfile->locationProfile()->progression;
        }

        $this->gemProgressionBroadcastService->broadcastForProfile(
            $character,
            $resolvedProfile,
            $globalProgression?->level ?? 1,
            $globalProgression?->xp ?? 0,
            $progression?->level ?? 1,
            $progression?->xp ?? 0,
        );
    }

    /**
     * Validate that the given Alchemy Bag slot belongs to the Character and holds a usable Gem Scroll Item.
     */
    private function validateOwnedScrollSlot(Character $character, AlchemyBagSlot $slot): ?array
    {
        $alchemyBag = $character->alchemyBag;

        if (is_null($alchemyBag) || $slot->alchemy_bag_id !== $alchemyBag->id || $slot->character_id !== $character->id) {
            return $this->errorResult('No. Not yours!');
        }

        if (! $slot->item->randomly_generated || is_null($slot->item->gem_scroll_type)) {
            return $this->errorResult('That Alchemy Item is not a Gem Scroll.');
        }

        if (! $slot->item->usable) {
            return $this->errorResult('This Gem Scroll cannot be used right now.');
        }

        return null;
    }

    /**
     * Validate that the given Alchemy Bag fill slot belongs to the Character and matches the active Scroll's family/currency.
     */
    private function validateFillSlot(Character $character, AlchemyBagSlot $fillSlot, Item $activeScrollItem): ?array
    {
        $ownershipError = $this->validateOwnedScrollSlot($character, $fillSlot);

        if (! is_null($ownershipError)) {
            return $ownershipError;
        }

        if ($fillSlot->item->gem_scroll_type !== $activeScrollItem->gem_scroll_type) {
            return $this->errorResult('The fill Scroll must be the same Gem Scroll family as the active Scroll.');
        }

        if (! is_null($activeScrollItem->gem_scroll_currency_type) && $fillSlot->item->gem_scroll_currency_type !== $activeScrollItem->gem_scroll_currency_type) {
            return $this->errorResult('The fill Scroll must target the same currency as the active Scroll.');
        }

        return null;
    }

    /**
     * Resolve the active Gem Scroll aggregate for the Character's current exact profile.
     */
    private function resolveAggregate(Character $character, ResolvedGemWorldProfile $resolvedProfile): GemScrollAggregate
    {
        if ($resolvedProfile->isMapProfile()) {
            return $this->gemScrollEffectService->aggregateForMapProfile($character, $resolvedProfile->mapProfile());
        }

        return $this->gemScrollEffectService->aggregateForLocationProfile($character, $resolvedProfile->locationProfile());
    }

    /**
     * Format a ratio as a factual whole-percent string.
     */
    private function formatPercent(float $ratio): string
    {
        return round($ratio * 100, 2).'%';
    }
}
