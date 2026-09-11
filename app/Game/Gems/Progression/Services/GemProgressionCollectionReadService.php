<?php

namespace App\Game\Gems\Progression\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterGameLocationGemProgression;
use App\Flare\Models\CharacterGameMapGemProgression;
use App\Flare\Pagination\Pagination;
use App\Game\Gems\Progression\Values\ResolvedGemWorldProfile;
use App\Game\Gems\Values\GemSourceType;
use Illuminate\Support\Collection;

class GemProgressionCollectionReadService
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
     * Paginate every Map/Location Gem World profile the Character has personal progression in, highest personal level first.
     *
     * @param Character $character
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function allProfileParticipation(Character $character, int $page = 1, int $perPage = self::DEFAULT_PER_PAGE): array
    {
        $currentProfile = $this->gemWorldProfileResolver->resolveForCharacter($character);

        $rows = $this->mapProfileRows($character, $currentProfile)
            ->merge($this->locationProfileRows($character, $currentProfile))
            ->sortByDesc('personal_level')
            ->values();

        return $this->pagination->paginateCollectionResponse($rows, $perPage, $page);
    }

    /**
     * Resolve this Character's personal progression rows for every Map Gem profile they have participated in.
     *
     * @param Character $character
     * @param ?ResolvedGemWorldProfile $currentProfile
     * @return Collection
     */
    private function mapProfileRows(Character $character, ?ResolvedGemWorldProfile $currentProfile): Collection
    {
        return CharacterGameMapGemProgression::with(['gameMapGemParamter.gameMap', 'gameMapGemParamter.generatedMap', 'gameMapGemParamter.progression'])
            ->where('character_id', $character->id)
            ->get()
            ->map(fn (CharacterGameMapGemProgression $row): array => $this->formatRow(
                isMapRow: true,
                profileId: $row->game_map_gem_paramter_id,
                profileName: $row->gameMapGemParamter?->name,
                mapName: $row->gameMapGemParamter?->gameMap?->name,
                generatedGameMapName: $row->gameMapGemParamter?->generatedMap?->name,
                personalLevel: $row->level,
                personalXp: $row->xp,
                globalLevel: $row->gameMapGemParamter?->progression?->level ?? 1,
                globalXp: $row->gameMapGemParamter?->progression?->xp ?? 0,
                currentProfile: $currentProfile,
            ));
    }

    /**
     * Resolve this Character's personal progression rows for every Location Gem profile they have participated in.
     *
     * @param Character $character
     * @param ?ResolvedGemWorldProfile $currentProfile
     * @return Collection
     */
    private function locationProfileRows(Character $character, ?ResolvedGemWorldProfile $currentProfile): Collection
    {
        return CharacterGameLocationGemProgression::with(['gameLocationGemParamter.location', 'gameLocationGemParamter.generatedMap', 'gameLocationGemParamter.progression'])
            ->where('character_id', $character->id)
            ->get()
            ->map(fn (CharacterGameLocationGemProgression $row): array => $this->formatRow(
                isMapRow: false,
                profileId: $row->game_location_gem_paramter_id,
                profileName: $row->gameLocationGemParamter?->name,
                mapName: $row->gameLocationGemParamter?->location?->name,
                generatedGameMapName: $row->gameLocationGemParamter?->generatedMap?->name,
                personalLevel: $row->level,
                personalXp: $row->xp,
                globalLevel: $row->gameLocationGemParamter?->progression?->level ?? 1,
                globalXp: $row->gameLocationGemParamter?->progression?->xp ?? 0,
                currentProfile: $currentProfile,
            ));
    }

    /**
     * Format one Gem World profile participation row into the shape the history browser needs.
     *
     * @param bool $isMapRow
     * @param int $profileId
     * @param ?string $profileName
     * @param ?string $mapName
     * @param ?string $generatedGameMapName
     * @param int $personalLevel
     * @param int $personalXp
     * @param int $globalLevel
     * @param int $globalXp
     * @param ?ResolvedGemWorldProfile $currentProfile
     * @return array
     */
    private function formatRow(
        bool $isMapRow,
        int $profileId,
        ?string $profileName,
        ?string $mapName,
        ?string $generatedGameMapName,
        int $personalLevel,
        int $personalXp,
        int $globalLevel,
        int $globalXp,
        ?ResolvedGemWorldProfile $currentProfile,
    ): array {
        return [
            'is_map_profile' => $isMapRow,
            'profile_id' => $profileId,
            'profile_name' => $profileName,
            'map_name' => $mapName,
            'generated_game_map_name' => $generatedGameMapName,
            'personal_level' => $personalLevel,
            'personal_xp' => $personalXp,
            'global_level' => $globalLevel,
            'global_xp' => $globalXp,
            'is_current_profile' => ! is_null($currentProfile)
                && $currentProfile->type() === ($isMapRow ? GemSourceType::MAP_GEM : GemSourceType::LOCATION_GEM)
                && $currentProfile->profileId() === $profileId,
        ];
    }
}
