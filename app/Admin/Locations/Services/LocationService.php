<?php

namespace App\Admin\Locations\Services;

use App\Admin\Locations\Requests\LocationIndexRequest;
use App\Admin\Locations\Requests\LocationQuestItemIndexRequest;
use App\Admin\Locations\Requests\MoveLocationRequest;
use App\Admin\Locations\Requests\StoreLocationRequest;
use App\Flare\Models\GameMap;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Game\Core\Items\Values\ItemCatalogType;
use App\Game\Maps\Contracts\CoordinatesQuery;
use App\Game\Maps\Values\LocationPin;
use App\Game\Maps\Values\LocationType;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class LocationService
{
    public function __construct(
        private readonly CoordinatesQuery $coordinatesQuery,
    ) {}

    /**
     * Paginate the standalone Locations list for the validated Admin index request.
     */
    public function paginate(LocationIndexRequest $request): LengthAwarePaginator
    {
        $searchText = $request->validated('search_text');
        $sortKey = $request->validated('sort_key');
        $sortDirection = $request->validated('sort_direction');
        $filters = $request->validated('filters') ?? [];

        $query = Location::query()->with('map');

        if (! empty($searchText)) {
            $query->where('name', 'LIKE', '%'.$searchText.'%');
        }

        if (! empty($filters['game_map_id'])) {
            $query->where('game_map_id', $filters['game_map_id']);
        }

        if (array_key_exists('type', $filters) && ! is_null($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        $query->orderBy($sortKey, $sortDirection)
            ->orderBy('id');

        return $query->paginate(
            $request->validated('per_page'),
            ['*'],
            'page',
            $request->validated('page')
        );
    }

    /**
     * Build the internal Admin detail data for the given Location.
     */
    public function detailData(Location $location): array
    {
        return [
            'location' => $location,
            'quest_item_drop_count' => $location->questItemDrops()->count(),
        ];
    }

    /**
     * Paginate the quest Items dropped at the given Location.
     */
    public function paginateQuestItemDrops(Location $location, LocationQuestItemIndexRequest $request): LengthAwarePaginator
    {
        $searchText = $request->validated('search_text');

        $query = $location->questItemDrops();

        if (! empty($searchText)) {
            $query->where('name', 'LIKE', '%'.$searchText.'%');
        }

        $query->orderBy('name')->orderBy('id');

        return $query->paginate(
            $request->validated('per_page'),
            ['*'],
            'page',
            $request->validated('page')
        );
    }

    /**
     * Build the internal Admin Location form option data for the given Game Map.
     */
    public function formOptions(GameMap $gameMap): array
    {
        return [
            'game_map' => $gameMap,
            'quest_items' => Item::where('type', ItemCatalogType::QUEST->value)
                ->orderBy('name')
                ->orderBy('id')
                ->get(),
            'location_types' => LocationType::cases(),
            'special_pins' => LocationPin::cases(),
            'coordinates' => $this->coordinatesQuery->get(),
        ];
    }

    /**
     * Resolve the given Location, aborting when it does not belong to the given Game Map.
     */
    public function findOnMap(GameMap $gameMap, Location $location): Location
    {
        if ($location->game_map_id !== $gameMap->id) {
            abort(404);
        }

        return $location;
    }

    /**
     * Create a new Location on the given Game Map.
     */
    public function create(GameMap $gameMap, StoreLocationRequest $request): Location
    {
        $validated = $request->validated();

        $this->assertValidCoordinates($validated['x'], $validated['y']);

        return Location::create([
            ...$validated,
            'game_map_id' => $gameMap->id,
        ]);
    }

    /**
     * Update an existing Location on the given Game Map.
     */
    public function update(GameMap $gameMap, Location $location, StoreLocationRequest $request): Location
    {
        $location = $this->findOnMap($gameMap, $location);
        $validated = $request->validated();

        $this->assertValidCoordinates($validated['x'], $validated['y']);

        $location->update($validated);

        return $location->refresh();
    }

    /**
     * Move an existing Location on the given Game Map to a new X/Y coordinate.
     */
    public function move(GameMap $gameMap, Location $location, MoveLocationRequest $request): Location
    {
        $location = $this->findOnMap($gameMap, $location);
        $validated = $request->validated();

        $this->assertValidCoordinates($validated['x'], $validated['y']);

        $location->update([
            'x' => $validated['x'],
            'y' => $validated['y'],
        ]);

        return $location->refresh();
    }

    /**
     * Assert the given X/Y coordinate exists within the game world's coordinate grid.
     */
    private function assertValidCoordinates(int $x, int $y): void
    {
        $coordinates = $this->coordinatesQuery->get();

        if (! in_array($x, $coordinates->x, true)) {
            throw ValidationException::withMessages([
                'x' => 'The selected X coordinate is not part of the map grid.',
            ]);
        }

        if (! in_array($y, $coordinates->y, true)) {
            throw ValidationException::withMessages([
                'y' => 'The selected Y coordinate is not part of the map grid.',
            ]);
        }
    }
}
