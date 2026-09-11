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
use Illuminate\Support\Facades\DB;

class CharacterGemScrollService
{
    use ResponseBuilder;

    /**
     * @param GemWorldProfileResolver $gemWorldProfileResolver
     * @param GemScrollEffectService $gemScrollEffectService
     * @param GemProgressionBroadcastService $gemProgressionBroadcastService
     */
    public function __construct(
        private readonly GemWorldProfileResolver $gemWorldProfileResolver,
        private readonly GemScrollEffectService $gemScrollEffectService,
        private readonly GemProgressionBroadcastService $gemProgressionBroadcastService,
    ) {}

    /**
     * Activate an owned Alchemy Bag Gem Scroll for the Character's current generated Gem World.
     *
     * @param Character $character
     * @param AlchemyBagSlot $slot
     * @return array
     */
    public function useScroll(Character $character, AlchemyBagSlot $slot): array
    {
        $result = DB::transaction(function () use ($character, $slot): array {
            $lockedCharacter = Character::where('id', $character->id)->lockForUpdate()->first();
            $lockedSlot = AlchemyBagSlot::where('id', $slot->id)->lockForUpdate()->first();

            if (is_null($lockedSlot)) {
                return $this->errorResult('No. Not yours!');
            }

            $ownershipError = $this->validateOwnedScrollSlot($lockedCharacter, $lockedSlot);

            if (! is_null($ownershipError)) {
                return $ownershipError;
            }

            $resolvedProfile = $this->gemWorldProfileResolver->resolveForCharacter($lockedCharacter);

            if (is_null($resolvedProfile)) {
                return $this->errorResult('You must be inside a generated Gem World to use a Gem Scroll.');
            }

            $aggregate = $this->resolveAggregate($lockedCharacter, $resolvedProfile, forUpdate: true);
            $bonus = $lockedSlot->item->gem_scroll_bonus ?? 0.0;

            if (! $aggregate->canActivate($bonus)) {
                return $this->errorResult('Activating this Scroll would exceed your 2000% active bonus cap for this Gem. Remaining capacity: '.$this->formatPercent($aggregate->remainingCapacity()).'.');
            }

            $this->createActiveScroll($lockedCharacter, $resolvedProfile, $lockedSlot->item_id, $lockedSlot->item->lasts_for);
            $lockedSlot->delete();

            return $this->successResult(['message' => 'Gem Scroll activated.', 'profile' => $resolvedProfile]);
        });

        return $this->afterSuccessfulMutation($character, $result);
    }

    /**
     * Extend an owned active Map Gem Scroll's duration using an owned Alchemy Bag fill Scroll.
     *
     * @param Character $character
     * @param CharacterGameMapGemScroll $activeScroll
     * @param AlchemyBagSlot $fillSlot
     * @return array
     */
    public function fillMapScroll(Character $character, CharacterGameMapGemScroll $activeScroll, AlchemyBagSlot $fillSlot): array
    {
        $result = DB::transaction(function () use ($character, $activeScroll, $fillSlot): array {
            $lockedActiveScroll = CharacterGameMapGemScroll::where('id', $activeScroll->id)->lockForUpdate()->first();
            $lockedFillSlot = AlchemyBagSlot::where('id', $fillSlot->id)->lockForUpdate()->first();

            if (is_null($lockedActiveScroll) || $lockedActiveScroll->character_id !== $character->id) {
                return $this->errorResult('No. Not yours!');
            }

            $resolvedProfile = $this->gemWorldProfileResolver->resolveForCharacter($character);

            if (is_null($resolvedProfile) || ! $resolvedProfile->isMapProfile() || $resolvedProfile->profileId() !== $lockedActiveScroll->game_map_gem_paramter_id) {
                return $this->errorResult('You must be inside the exact Gem World this Scroll is active for to fill it up.');
            }

            $filled = $this->fillActiveScroll($character, $lockedActiveScroll, $lockedFillSlot);

            if (! is_null($filled)) {
                return $filled;
            }

            return $this->successResult(['message' => 'Gem Scroll filled up.', 'profile' => $resolvedProfile]);
        });

        return $this->afterSuccessfulMutation($character, $result);
    }

    /**
     * Extend an owned active Location Gem Scroll's duration using an owned Alchemy Bag fill Scroll.
     *
     * @param Character $character
     * @param CharacterGameLocationGemScroll $activeScroll
     * @param AlchemyBagSlot $fillSlot
     * @return array
     */
    public function fillLocationScroll(Character $character, CharacterGameLocationGemScroll $activeScroll, AlchemyBagSlot $fillSlot): array
    {
        $result = DB::transaction(function () use ($character, $activeScroll, $fillSlot): array {
            $lockedActiveScroll = CharacterGameLocationGemScroll::where('id', $activeScroll->id)->lockForUpdate()->first();
            $lockedFillSlot = AlchemyBagSlot::where('id', $fillSlot->id)->lockForUpdate()->first();

            if (is_null($lockedActiveScroll) || $lockedActiveScroll->character_id !== $character->id) {
                return $this->errorResult('No. Not yours!');
            }

            $resolvedProfile = $this->gemWorldProfileResolver->resolveForCharacter($character);

            if (is_null($resolvedProfile) || ! $resolvedProfile->isLocationProfile() || $resolvedProfile->profileId() !== $lockedActiveScroll->game_location_gem_paramter_id) {
                return $this->errorResult('You must be inside the exact Gem World this Scroll is active for to fill it up.');
            }

            $filled = $this->fillActiveScroll($character, $lockedActiveScroll, $lockedFillSlot);

            if (! is_null($filled)) {
                return $filled;
            }

            return $this->successResult(['message' => 'Gem Scroll filled up.', 'profile' => $resolvedProfile]);
        });

        return $this->afterSuccessfulMutation($character, $result);
    }

    /**
     * Remove an owned active Map Gem Scroll without refunding its duration or Item.
     *
     * @param Character $character
     * @param CharacterGameMapGemScroll $activeScroll
     * @return array
     */
    public function removeMapScroll(Character $character, CharacterGameMapGemScroll $activeScroll): array
    {
        $result = DB::transaction(function () use ($character, $activeScroll): array {
            $lockedActiveScroll = CharacterGameMapGemScroll::where('id', $activeScroll->id)->lockForUpdate()->first();

            if (is_null($lockedActiveScroll) || $lockedActiveScroll->character_id !== $character->id) {
                return $this->errorResult('No. Not yours!');
            }

            $profile = $lockedActiveScroll->gameMapGemParamter;
            $lockedActiveScroll->delete();

            $resolvedProfile = (! is_null($profile) && ! is_null($profile->generatedMap))
                ? ResolvedGemWorldProfile::forMapProfile($profile, $profile->generatedMap)
                : null;

            return $this->successResult(['message' => 'Gem Scroll removed.', 'profile' => $resolvedProfile]);
        });

        return $this->afterSuccessfulMutation($character, $result, broadcastOnly: true);
    }

    /**
     * Remove an owned active Location Gem Scroll without refunding its duration or Item.
     *
     * @param Character $character
     * @param CharacterGameLocationGemScroll $activeScroll
     * @return array
     */
    public function removeLocationScroll(Character $character, CharacterGameLocationGemScroll $activeScroll): array
    {
        $result = DB::transaction(function () use ($character, $activeScroll): array {
            $lockedActiveScroll = CharacterGameLocationGemScroll::where('id', $activeScroll->id)->lockForUpdate()->first();

            if (is_null($lockedActiveScroll) || $lockedActiveScroll->character_id !== $character->id) {
                return $this->errorResult('No. Not yours!');
            }

            $profile = $lockedActiveScroll->gameLocationGemParamter;
            $lockedActiveScroll->delete();

            $resolvedProfile = (! is_null($profile) && ! is_null($profile->generatedMap))
                ? ResolvedGemWorldProfile::forLocationProfile($profile, $profile->generatedMap)
                : null;

            return $this->successResult(['message' => 'Gem Scroll removed.', 'profile' => $resolvedProfile]);
        });

        return $this->afterSuccessfulMutation($character, $result, broadcastOnly: true);
    }

    /**
     * Persist the new active Scroll row for the Character's exact resolved profile.
     *
     * @param Character $character
     * @param ResolvedGemWorldProfile $resolvedProfile
     * @param int $itemId
     * @param int $lastsForMinutes
     */
    private function createActiveScroll(Character $character, ResolvedGemWorldProfile $resolvedProfile, int $itemId, int $lastsForMinutes): void
    {
        $startedAt = now();
        $expiresAt = $startedAt->clone()->addMinutes($lastsForMinutes);

        if ($resolvedProfile->isMapProfile()) {
            CharacterGameMapGemScroll::create([
                'character_id' => $character->id,
                'game_map_gem_paramter_id' => $resolvedProfile->profileId(),
                'item_id' => $itemId,
                'started_at' => $startedAt,
                'expires_at' => $expiresAt,
            ]);

            return;
        }

        CharacterGameLocationGemScroll::create([
            'character_id' => $character->id,
            'game_location_gem_paramter_id' => $resolvedProfile->profileId(),
            'item_id' => $itemId,
            'started_at' => $startedAt,
            'expires_at' => $expiresAt,
        ]);
    }

    /**
     * Validate and apply a Fill Up mutation shared by the Map/Location active
     * Scroll rows, extending duration with no ceiling. Returns an error
     * result array on failure, or null once applied.
     *
     * @param Character $character
     * @param CharacterGameMapGemScroll|CharacterGameLocationGemScroll $activeScroll
     * @param ?AlchemyBagSlot $fillSlot
     * @return ?array
     */
    private function fillActiveScroll(Character $character, CharacterGameMapGemScroll|CharacterGameLocationGemScroll $activeScroll, ?AlchemyBagSlot $fillSlot): ?array
    {
        if (is_null($fillSlot)) {
            return $this->errorResult('No. Not yours!');
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

        return null;
    }

    /**
     * Fire the inventory update event and Gem progression broadcast after a
     * mutation transaction commits successfully, and strip the transient
     * `profile` key back out of the response payload.
     *
     * @param Character $character
     * @param array $result
     * @param bool $broadcastOnly
     * @return array
     */
    private function afterSuccessfulMutation(Character $character, array $result, bool $broadcastOnly = false): array
    {
        if (($result['status'] ?? null) !== 200) {
            return $result;
        }

        $resolvedProfile = $result['profile'] ?? null;
        unset($result['profile']);

        if (! $broadcastOnly) {
            event(new UpdateCharacterInventoryCountEvent($character));
        }

        if (! is_null($resolvedProfile)) {
            $this->broadcastProgress($character->fresh(), $resolvedProfile);
        }

        return $result;
    }

    /**
     * Broadcast the Character's current compact Gem progression summary for the given profile.
     *
     * @param Character $character
     * @param ResolvedGemWorldProfile $resolvedProfile
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
     *
     * @param Character $character
     * @param AlchemyBagSlot $slot
     * @return ?array
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
     *
     * @param Character $character
     * @param AlchemyBagSlot $fillSlot
     * @param Item $activeScrollItem
     * @return ?array
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
     *
     * @param Character $character
     * @param ResolvedGemWorldProfile $resolvedProfile
     * @param bool $forUpdate
     * @return GemScrollAggregate
     */
    private function resolveAggregate(Character $character, ResolvedGemWorldProfile $resolvedProfile, bool $forUpdate = false): GemScrollAggregate
    {
        if ($resolvedProfile->isMapProfile()) {
            return $this->gemScrollEffectService->aggregateForMapProfile($character, $resolvedProfile->mapProfile(), $forUpdate);
        }

        return $this->gemScrollEffectService->aggregateForLocationProfile($character, $resolvedProfile->locationProfile(), $forUpdate);
    }

    /**
     * Format a ratio as a factual whole-percent string.
     *
     * @param float $ratio
     * @return string
     */
    private function formatPercent(float $ratio): string
    {
        return round($ratio * 100, 2).'%';
    }
}
