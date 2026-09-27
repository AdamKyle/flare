<?php

namespace App\Admin\Kingdoms\Services;

use App\Admin\Kingdoms\Requests\BuildingIndexRequest;
use App\Admin\Kingdoms\Requests\StoreBuildingRequest;
use App\Admin\Kingdoms\Requests\UpdateBuildingRequest;
use App\Admin\Services\UpdateKingdomsService;
use App\Flare\Models\GameBuilding;
use App\Flare\Models\GameBuildingUnit;
use App\Flare\Models\GameUnit;
use App\Flare\Models\PassiveSkill;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class BuildingAdminService
{
    /**
     * @param BuildingUnitAssignmentService $buildingUnitAssignmentService
     * @param UpdateKingdomsService $updateKingdomsService
     */
    public function __construct(
        private readonly BuildingUnitAssignmentService $buildingUnitAssignmentService,
        private readonly UpdateKingdomsService $updateKingdomsService,
    ) {}

    /**
     * Paginate the Kingdom Buildings list for the validated Admin index request.
     *
     * @param BuildingIndexRequest $request
     * @return LengthAwarePaginator
     */
    public function paginate(BuildingIndexRequest $request): LengthAwarePaginator
    {
        $searchText = $request->validated('search_text');

        return GameBuilding::query()
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
     * Build the internal Admin Kingdom Building form option data.
     *
     * @return array
     */
    public function formOptions(): array
    {
        return [
            'passive_skills' => PassiveSkill::orderBy('name')->orderBy('id')->get(['id', 'name']),
            'units' => GameUnit::orderBy('name')->orderBy('id')->get(['id', 'name']),
        ];
    }

    /**
     * Return the Building's recruitable Unit relationships in recruitment order.
     *
     * @param GameBuilding $gameBuilding
     * @return Collection
     */
    public function recruitableUnits(GameBuilding $gameBuilding): Collection
    {
        return GameBuildingUnit::where('game_building_id', $gameBuilding->id)
            ->with('gameUnit')
            ->orderBy('required_level')
            ->orderBy('id')
            ->get();
    }

    /**
     * Create a Kingdom Building, assign its recruitable Units, and give it to existing player Kingdoms.
     *
     * @param StoreBuildingRequest $request
     * @return GameBuilding
     */
    public function create(StoreBuildingRequest $request): GameBuilding
    {
        $buildingData = $this->normalizeBuildingData($request->validated());

        $gameBuilding = GameBuilding::create(Arr::except($buildingData, ['unit_ids']))->refresh();

        $this->buildingUnitAssignmentService->sync($gameBuilding, $buildingData['unit_ids'], $gameBuilding->units_per_level, $gameBuilding->only_at_level);

        $this->updateKingdomsService->assignNewBuildingsToCharacters($gameBuilding);

        return $gameBuilding;
    }

    /**
     * Update a Kingdom Building, resynchronize its recruitable Units, and refresh existing player Kingdom Buildings.
     *
     * @param GameBuilding $gameBuilding
     * @param UpdateBuildingRequest $request
     * @return GameBuilding
     */
    public function update(GameBuilding $gameBuilding, UpdateBuildingRequest $request): GameBuilding
    {
        $buildingData = $this->normalizeBuildingData($request->validated());

        $gameBuilding->update(Arr::except($buildingData, ['unit_ids']));

        $gameBuilding = $gameBuilding->refresh();

        $this->buildingUnitAssignmentService->sync($gameBuilding, $buildingData['unit_ids'], $gameBuilding->units_per_level, $gameBuilding->only_at_level);

        $this->updateKingdomsService->assignNewBuildingsToCharacters($gameBuilding);
        $this->updateKingdomsService->updateKingdomBuildings($gameBuilding);

        return $gameBuilding;
    }

    /**
     * Normalize the validated Building data so its recruitment and resource flags match its configuration.
     *
     * @param array $buildingData
     * @return array
     */
    private function normalizeBuildingData(array $buildingData): array
    {
        return $this->normalizeResourceBuilding($this->normalizeUnitRecruitment($buildingData));
    }

    /**
     * Clear the Unit selection and recruitment schedule of a Building that does not train Units.
     *
     * @param array $buildingData
     * @return array
     */
    private function normalizeUnitRecruitment(array $buildingData): array
    {
        if ($buildingData['trains_units']) {
            return $buildingData;
        }

        return array_merge($buildingData, [
            'unit_ids' => [],
            'units_per_level' => null,
            'only_at_level' => null,
        ]);
    }

    /**
     * Only keep a Building flagged as a resource Building when it increases at least one resource.
     *
     * @param array $buildingData
     * @return array
     */
    private function normalizeResourceBuilding(array $buildingData): array
    {
        $increasesResources = collect(['increase_wood_amount', 'increase_clay_amount', 'increase_stone_amount', 'increase_iron_amount'])
            ->contains(fn (string $attribute): bool => $buildingData[$attribute] > 0);

        return array_merge($buildingData, [
            'is_resource_building' => $buildingData['is_resource_building'] && $increasesResources,
        ]);
    }
}
