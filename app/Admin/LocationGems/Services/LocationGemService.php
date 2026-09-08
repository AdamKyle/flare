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
    public function __construct(
        private readonly AdminGemRollService $adminGemRollService,
        private readonly AdminGemRollTransformer $adminGemRollTransformer,
        private readonly BuildMonsterCacheService $buildMonsterCacheService,
    ) {}

    /**
     * Paginate the Location Gems list for the validated Admin index request.
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
     */
    public function formOptions(): array
    {
        return [
            'locations' => $this->eligibleLocations(),
            'crafting_skills' => GameSkill::where('can_train', false)->orderBy('name')->orderBy('id')->get(),
        ];
    }

    /**
     * Resolve Locations eligible to receive a Location Gem profile.
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
     */
    public function create(StoreLocationGemRequest $request): GameLocationGemParamter
    {
        return GameLocationGemParamter::create($request->validated());
    }

    /**
     * Update an existing Location Gem profile from the validated form data.
     */
    public function update(GameLocationGemParamter $gameLocationGemParamter, UpdateLocationGemRequest $request): GameLocationGemParamter
    {
        $gameLocationGemParamter->update($request->validated());

        return $gameLocationGemParamter->refresh();
    }

    /**
     * Roll a new Gem for the given Location Gem profile using the existing Gem roll service.
     */
    public function roll(GameLocationGemParamter $gameLocationGemParamter, User $admin): Gem
    {
        $gem = $this->adminGemRollService->rollLocationGem($gameLocationGemParamter, $admin);

        $this->buildMonsterCacheService->invalidateGemAffectedCaches();

        return $gem;
    }

    /**
     * Roll a new Gem for every Location Gem profile, regardless of any previously rolled Gem.
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
     * Activate an existing Location Gem roll without regenerating its Gem World.
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
