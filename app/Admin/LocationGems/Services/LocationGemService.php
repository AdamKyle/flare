<?php

namespace App\Admin\LocationGems\Services;

use App\Admin\LocationGems\Requests\LocationGemIndexRequest;
use App\Admin\LocationGems\Requests\StoreLocationGemRequest;
use App\Admin\LocationGems\Requests\UpdateLocationGemRequest;
use App\Admin\Services\AdminGemRollService;
use App\Admin\Transformers\AdminGemRollTransformer;
use App\Flare\Models\GameLocationGemParamter;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Gem;
use App\Flare\Models\Location;
use App\Flare\Models\User;
use App\Game\Maps\Values\MapName;
use App\Game\Monsters\Services\BuildMonsterCacheService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class LocationGemService
{
    /**
     * @param  AdminGemRollService  $adminGemRollService  Existing Gem roll service.
     * @param  AdminGemRollTransformer  $adminGemRollTransformer  Shared Admin Gem roll transformer.
     * @param  BuildMonsterCacheService  $buildMonsterCacheService  Monster cache invalidation service.
     */
    public function __construct(
        private readonly AdminGemRollService $adminGemRollService,
        private readonly AdminGemRollTransformer $adminGemRollTransformer,
        private readonly BuildMonsterCacheService $buildMonsterCacheService,
    ) {}

    /**
     * Paginate the Location Gems list for the validated Admin index request.
     *
     * @param  LocationGemIndexRequest  $request  Validated Location Gem list request.
     * @return LengthAwarePaginator Paginated Location Gem records.
     */
    public function paginate(LocationGemIndexRequest $request): LengthAwarePaginator
    {
        $searchText = $request->validated('search_text');
        $filters = $request->validated('filters') ?? [];
        $gameMapId = $filters['game_map_id'] ?? null;
        $locationId = $filters['location_id'] ?? null;
        $sortKey = $request->validated('sort_key');
        $sortDirection = $request->validated('sort_direction');

        $query = GameLocationGemParamter::with(['location.map', 'rolledGem']);

        if (! empty($searchText)) {
            $query->where(function ($searchQuery) use ($searchText) {
                $searchQuery->where('name', 'LIKE', '%'.$searchText.'%')
                    ->orWhere('description', 'LIKE', '%'.$searchText.'%')
                    ->orWhereHas('location', fn ($locationQuery) => $locationQuery->where('name', 'LIKE', '%'.$searchText.'%'));
            });
        }

        if (! is_null($locationId)) {
            $query->where('location_id', $locationId);
        }

        if (! is_null($gameMapId)) {
            $query->whereHas('location', fn ($locationQuery) => $locationQuery->where('game_map_id', $gameMapId));
        }

        $query->orderBy($sortKey, $sortDirection)->orderBy('id');

        return $query->paginate(
            $request->validated('per_page'),
            ['*'],
            'page',
            $request->validated('page')
        );
    }

    /**
     * Build the internal Admin Location Gem form option data.
     *
     * @return array{locations: Collection<int, Location>, crafting_skills: Collection<int, GameSkill>} Internal Location Gem form option data.
     */
    public function formOptions(): array
    {
        return [
            'locations' => $this->eligibleLocations(),
            'crafting_skills' => GameSkill::where('can_train', false)->orderBy('name')->orderBy('id')->get(),
        ];
    }

    /**
     * Resolve the Locations eligible to receive a Location Gem profile: eligible Locations whose
     * parent Map is not a generated Gem World, ordered by special/normal, then plane, then name.
     *
     * @return Collection<int, Location> Eligible Locations, ordered for selection.
     */
    public function eligibleLocations(): Collection
    {
        $planeOrder = [
            MapName::SURFACE->value => 0,
            MapName::LABYRINTH->value => 1,
            MapName::DUNGEONS->value => 2,
            MapName::SHADOW_PLANE->value => 3,
            MapName::HELL->value => 4,
            MapName::PURGATORY->value => 5,
            MapName::TWISTED_MEMORIES->value => 6,
            MapName::ICE_PLANE->value => 7,
            MapName::DELUSIONAL_MEMORIES->value => 8,
        ];

        return Location::with('map')
            ->eligibleForLocationGems()
            ->whereHas('map', fn ($mapQuery) => $mapQuery->whereNull('generated_map_type'))
            ->get()
            ->sort(function (Location $a, Location $b) use ($planeOrder) {
                $aSpecial = is_null($a->type) ? 0 : 1;
                $bSpecial = is_null($b->type) ? 0 : 1;

                if ($aSpecial !== $bSpecial) {
                    return $aSpecial - $bSpecial;
                }

                $aPlane = $planeOrder[$a->map->name ?? ''] ?? 999;
                $bPlane = $planeOrder[$b->map->name ?? ''] ?? 999;

                if ($aPlane !== $bPlane) {
                    return $aPlane - $bPlane;
                }

                return strcmp($a->name, $b->name);
            })
            ->values();
    }

    /**
     * Create a new Location Gem profile from the validated form data.
     *
     * @param  StoreLocationGemRequest  $request  Validated Location Gem creation request.
     * @return GameLocationGemParamter Created Location Gem profile.
     */
    public function create(StoreLocationGemRequest $request): GameLocationGemParamter
    {
        return GameLocationGemParamter::create($request->validated());
    }

    /**
     * Update an existing Location Gem profile from the validated form data.
     *
     * @param  GameLocationGemParamter  $gameLocationGemParamter  Location Gem profile to update.
     * @param  UpdateLocationGemRequest  $request  Validated Location Gem update request.
     * @return GameLocationGemParamter Updated Location Gem profile.
     */
    public function update(GameLocationGemParamter $gameLocationGemParamter, UpdateLocationGemRequest $request): GameLocationGemParamter
    {
        $gameLocationGemParamter->update($request->validated());

        return $gameLocationGemParamter->refresh();
    }

    /**
     * Roll a new Gem for the given Location Gem profile using the existing Gem roll service.
     *
     * @param  GameLocationGemParamter  $gameLocationGemParamter  Location Gem profile to roll.
     * @param  User  $admin  Admin performing the roll.
     * @return Gem Rolled Gem.
     */
    public function roll(GameLocationGemParamter $gameLocationGemParamter, User $admin): Gem
    {
        $gem = $this->adminGemRollService->rollLocationGem($gameLocationGemParamter, $admin);

        $this->buildMonsterCacheService->invalidateGemAffectedCaches();

        return $gem;
    }

    /**
     * Roll a new Gem for every Location Gem profile, regardless of any previously rolled Gem.
     *
     * @param  User  $admin  Admin performing the bulk roll.
     * @return array{rolled_count: int, rolled: array<int, array<string, mixed>>} Bulk roll result.
     */
    public function rollAll(User $admin): array
    {
        $profiles = GameLocationGemParamter::with('location')
            ->orderBy('name')
            ->orderBy('id')
            ->get();

        $rolled = [];

        foreach ($profiles as $profile) {
            $gem = $this->adminGemRollService->rollLocationGem($profile, $admin);

            $rolled[] = $this->bulkRollRow($profile, $gem);
        }

        if (count($rolled) > 0) {
            $this->buildMonsterCacheService->invalidateGemAffectedCaches();
        }

        return [
            'rolled_count' => count($rolled),
            'rolled' => $rolled,
        ];
    }

    /**
     * Make an existing historical Gem roll the profile's currently active roll, without changing
     * the roll count or regenerating the Gem World.
     *
     * @param  GameLocationGemParamter  $gameLocationGemParamter  Location Gem profile whose active roll is changing.
     * @param  Gem  $gem  Gem roll to activate.
     * @return bool Whether the Gem belongs to this profile and was made/kept active.
     */
    public function activateRoll(GameLocationGemParamter $gameLocationGemParamter, Gem $gem): bool
    {
        if ($gem->domain !== Gem::DOMAIN_LOCATION || $gem->game_location_gem_paramters_id !== $gameLocationGemParamter->id) {
            return false;
        }

        if ($gameLocationGemParamter->rolled_gem_id === $gem->id) {
            return true;
        }

        $gameLocationGemParamter->update(['rolled_gem_id' => $gem->id]);

        $this->buildMonsterCacheService->invalidateGemAffectedCaches();

        return true;
    }

    /**
     * Build one Bulk Roll result row for a Location Gem profile.
     *
     * @param  GameLocationGemParamter  $profile  Location Gem profile the row describes.
     * @param  Gem  $gem  Gem this bulk action rolled for the profile.
     * @return array<string, mixed> Bulk roll result row.
     */
    private function bulkRollRow(GameLocationGemParamter $profile, Gem $gem): array
    {
        return [
            'profile_id' => $profile->id,
            'profile_name' => $profile->name,
            'source_name' => $profile->location->name,
            'rolled_gem' => $this->adminGemRollTransformer->transform($gem, true),
        ];
    }
}
