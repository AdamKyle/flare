<?php

namespace App\Admin\Kingdoms\Services;

use App\Admin\Kingdoms\Requests\StoreUnitRequest;
use App\Admin\Kingdoms\Requests\UnitIndexRequest;
use App\Admin\Kingdoms\Requests\UpdateUnitRequest;
use App\Flare\Models\GameBuildingUnit;
use App\Flare\Models\GameUnit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class UnitAdminService
{
    /**
     * Paginate the Kingdom Units list for the validated Admin index request.
     *
     * @param UnitIndexRequest $request
     * @return LengthAwarePaginator
     */
    public function paginate(UnitIndexRequest $request): LengthAwarePaginator
    {
        $searchText = $request->validated('search_text');

        return GameUnit::query()
            ->when(! empty($searchText), function (Builder $query) use ($searchText): void {
                $query->where(function (Builder $searchQuery) use ($searchText): void {
                    $searchQuery->where('name', 'LIKE', '%'.$searchText.'%')
                        ->orWhere('description', 'LIKE', '%'.$searchText.'%');
                });
            })
            ->orderBy($request->validated('sort_key'), $request->validated('sort_direction'))
            ->orderBy('id')
            ->paginate(
                $request->validated('per_page'),
                ['*'],
                'page',
                $request->validated('page')
            );
    }

    /**
     * Return every Building relationship that recruits the Unit, in required-level order.
     *
     * @param GameUnit $gameUnit
     * @return Collection
     */
    public function recruitingBuildings(GameUnit $gameUnit): Collection
    {
        return GameBuildingUnit::where('game_unit_id', $gameUnit->id)
            ->with('gameBuilding')
            ->orderBy('required_level')
            ->orderBy('id')
            ->get();
    }

    /**
     * Create a Kingdom Unit from the validated form data.
     *
     * @param StoreUnitRequest $request
     * @return GameUnit
     */
    public function create(StoreUnitRequest $request): GameUnit
    {
        return GameUnit::create($request->validated())->refresh();
    }

    /**
     * Update a Kingdom Unit from the validated form data.
     *
     * @param GameUnit $gameUnit
     * @param UpdateUnitRequest $request
     * @return GameUnit
     */
    public function update(GameUnit $gameUnit, UpdateUnitRequest $request): GameUnit
    {
        $gameUnit->update($request->validated());

        return $gameUnit->refresh();
    }
}
