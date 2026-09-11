<?php

namespace App\Game\Gems\Progression\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterGameLocationGemScroll;
use App\Flare\Models\CharacterGameMapGemScroll;
use App\Flare\Pagination\Pagination;
use App\Game\Gems\Progression\Values\ResolvedGemWorldProfile;
use Illuminate\Support\Collection;

class GemScrollReadService
{
    private const int DEFAULT_PER_PAGE = 10;

    /**
     * @param GemWorldProfileResolver $gemWorldProfileResolver
     * @param Pagination $pagination
     */
    public function __construct(
        private readonly GemWorldProfileResolver $gemWorldProfileResolver,
        private readonly Pagination $pagination,
    ) {}

    /**
     * Paginate the Character's active Gem Scrolls for their current generated Gem World profile.
     *
     * @param Character $character
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function currentProfileActiveScrolls(Character $character, int $page = 1, int $perPage = self::DEFAULT_PER_PAGE): array
    {
        $resolvedProfile = $this->gemWorldProfileResolver->resolveForCharacter($character);

        if (is_null($resolvedProfile)) {
            return $this->pagination->paginateCollectionResponse(collect(), $perPage, $page);
        }

        $rows = $resolvedProfile->isMapProfile()
            ? $this->activeMapScrolls($character, $resolvedProfile->profileId(), $resolvedProfile)
            : $this->activeLocationScrolls($character, $resolvedProfile->profileId(), $resolvedProfile);

        return $this->pagination->paginateCollectionResponse($rows, $perPage, $page);
    }

    /**
     * Paginate every unexpired active Gem Scroll the Character owns across every Map/Location profile.
     *
     * @param Character $character
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function allActiveScrolls(Character $character, int $page = 1, int $perPage = self::DEFAULT_PER_PAGE): array
    {
        $currentProfile = $this->gemWorldProfileResolver->resolveForCharacter($character);

        $rows = $this->activeMapScrolls($character, null, $currentProfile)
            ->merge($this->activeLocationScrolls($character, null, $currentProfile))
            ->sortByDesc('started_at')
            ->values();

        return $this->pagination->paginateCollectionResponse($rows, $perPage, $page);
    }

    /**
     * Resolve this Character's active Map Gem Scroll rows, optionally scoped to one exact profile.
     *
     * @param Character $character
     * @param ?int $profileId
     * @param ?ResolvedGemWorldProfile $currentProfile
     * @return Collection
     */
    private function activeMapScrolls(Character $character, ?int $profileId, ?ResolvedGemWorldProfile $currentProfile = null): Collection
    {
        $query = CharacterGameMapGemScroll::with(['item', 'gameMapGemParamter.gameMap', 'gameMapGemParamter.generatedMap'])
            ->where('character_id', $character->id)
            ->active();

        if (! is_null($profileId)) {
            $query->where('game_map_gem_paramter_id', $profileId);
        }

        return $query->get()->map(fn (CharacterGameMapGemScroll $row): array => $this->formatRow(
            $row,
            isMapRow: true,
            profileId: $row->game_map_gem_paramter_id,
            profileName: $row->gameMapGemParamter?->name,
            mapName: $row->gameMapGemParamter?->gameMap?->name,
            generatedGameMapName: $row->gameMapGemParamter?->generatedMap?->name,
            currentProfile: $currentProfile,
        ));
    }

    /**
     * Resolve this Character's active Location Gem Scroll rows, optionally scoped to one exact profile.
     *
     * @param Character $character
     * @param ?int $profileId
     * @param ?ResolvedGemWorldProfile $currentProfile
     * @return Collection
     */
    private function activeLocationScrolls(Character $character, ?int $profileId, ?ResolvedGemWorldProfile $currentProfile = null): Collection
    {
        $query = CharacterGameLocationGemScroll::with(['item', 'gameLocationGemParamter.location', 'gameLocationGemParamter.generatedMap'])
            ->where('character_id', $character->id)
            ->active();

        if (! is_null($profileId)) {
            $query->where('game_location_gem_paramter_id', $profileId);
        }

        return $query->get()->map(fn (CharacterGameLocationGemScroll $row): array => $this->formatRow(
            $row,
            isMapRow: false,
            profileId: $row->game_location_gem_paramter_id,
            profileName: $row->gameLocationGemParamter?->name,
            mapName: $row->gameLocationGemParamter?->location?->name,
            generatedGameMapName: $row->gameLocationGemParamter?->generatedMap?->name,
            currentProfile: $currentProfile,
        ));
    }

    /**
     * Format one active Gem Scroll row into the factual shape the Fill/Remove UI needs.
     *
     * @param CharacterGameMapGemScroll|CharacterGameLocationGemScroll $row
     * @param bool $isMapRow
     * @param int $profileId
     * @param ?string $profileName
     * @param ?string $mapName
     * @param ?string $generatedGameMapName
     * @param ?ResolvedGemWorldProfile $currentProfile
     * @return array
     */
    private function formatRow(
        CharacterGameMapGemScroll|CharacterGameLocationGemScroll $row,
        bool $isMapRow,
        int $profileId,
        ?string $profileName,
        ?string $mapName,
        ?string $generatedGameMapName,
        ?ResolvedGemWorldProfile $currentProfile,
    ): array {
        return [
            'id' => $row->id,
            'is_map_scroll' => $isMapRow,
            'profile_id' => $profileId,
            'profile_name' => $profileName,
            'map_name' => $mapName,
            'generated_game_map_name' => $generatedGameMapName,
            'item_id' => $row->item_id,
            'item_name' => $row->item->name,
            'gem_scroll_type' => $row->item->gem_scroll_type,
            'gem_scroll_currency_type' => $row->item->gem_scroll_currency_type,
            'gem_scroll_bonus' => $row->item->gem_scroll_bonus,
            'gem_scroll_socket_chance' => $row->item->gem_scroll_socket_chance,
            'gem_scroll_pre_gem_chance' => $row->item->gem_scroll_pre_gem_chance,
            'started_at' => $row->started_at,
            'expires_at' => $row->expires_at,
            'is_current_profile' => ! is_null($currentProfile)
                && $currentProfile->type()->value === ($isMapRow ? 'map_gem' : 'location_gem')
                && $currentProfile->profileId() === $profileId,
        ];
    }
}
