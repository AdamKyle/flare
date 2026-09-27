<?php

namespace App\Admin\Kingdoms\Imports\Sheets;

use App\Admin\Kingdoms\Exceptions\KingdomWorkbookException;
use App\Admin\Kingdoms\Services\BuildingUnitAssignmentService;
use App\Flare\Models\GameBuilding;
use App\Flare\Models\GameUnit;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class BuildingsUnitsSheet implements ToCollection
{
    /**
     * @param BuildingUnitAssignmentService $buildingUnitAssignmentService
     * @param BuildingsSheet $buildingsSheet
     */
    public function __construct(
        private readonly BuildingUnitAssignmentService $buildingUnitAssignmentService,
        private readonly BuildingsSheet $buildingsSheet,
    ) {}

    /**
     * Reconcile every Building the workbook defines to exactly the Units its relationship rows list.
     *
     * @param Collection $rows
     * @return void
     *
     * @throws KingdomWorkbookException
     */
    public function collection(Collection $rows): void
    {
        $relationshipsByBuilding = $rows->slice(1)
            ->reject(fn (Collection $row): bool => blank($row[1]) && blank($row[2]) && blank($row[3]))
            ->map(fn (Collection $row): array => $this->resolveRelationship($row))
            ->groupBy('building_id');

        $buildingIds = collect($this->buildingsSheet->importedBuildingIds())
            ->merge($relationshipsByBuilding->keys())
            ->unique();

        GameBuilding::whereIn('id', $buildingIds)->get()->each(function (GameBuilding $building) use ($relationshipsByBuilding): void {
            $this->syncBuilding($building, $relationshipsByBuilding->get($building->id, collect()));
        });
    }

    /**
     * Resolve a relationship row's Building name, Unit name, and required level.
     *
     * @param Collection $row
     * @return array
     *
     * @throws KingdomWorkbookException
     */
    private function resolveRelationship(Collection $row): array
    {
        $buildingName = $row[1];
        $unitName = $row[2];

        if (! is_string($buildingName) || blank($buildingName)) {
            throw new KingdomWorkbookException('The Building Units row requires a Building name.');
        }

        if (! is_string($unitName) || blank($unitName)) {
            throw new KingdomWorkbookException('The Building Units row requires a Unit name.');
        }

        $requiredLevel = filter_var($row[3], FILTER_VALIDATE_INT);

        if ($requiredLevel === false) {
            throw new KingdomWorkbookException('The Building Units row for '.$row[1].' and '.$row[2].' has an invalid Required Level.');
        }

        return [
            'building_id' => $this->resolveBuildingId($buildingName),
            'unit_id' => $this->resolveUnitId($unitName),
            'required_level' => $requiredLevel,
        ];
    }

    /**
     * Resolve the id of the Building whose name exactly matches the workbook value.
     *
     * @param string $buildingName
     * @return int
     *
     * @throws KingdomWorkbookException
     */
    private function resolveBuildingId(string $buildingName): int
    {
        $building = GameBuilding::where('name', $buildingName)->get()
            ->first(fn (GameBuilding $candidate): bool => $candidate->name === $buildingName);

        if (is_null($building)) {
            throw new KingdomWorkbookException('The Building Units sheet references an unknown Building: '.$buildingName.'.');
        }

        return $building->id;
    }

    /**
     * Resolve the id of the Unit whose name exactly matches the workbook value.
     *
     * @param string $unitName
     * @return int
     *
     * @throws KingdomWorkbookException
     */
    private function resolveUnitId(string $unitName): int
    {
        $unit = GameUnit::where('name', $unitName)->get()
            ->first(fn (GameUnit $candidate): bool => $candidate->name === $unitName);

        if (is_null($unit)) {
            throw new KingdomWorkbookException('The Building Units sheet references an unknown Unit: '.$unitName.'.');
        }

        return $unit->id;
    }

    /**
     * Synchronize one Building's Units in ascending required-level order using its configured recruitment schedule.
     *
     * @param GameBuilding $building
     * @param Collection $relationships
     * @return void
     *
     * @throws KingdomWorkbookException
     */
    private function syncBuilding(GameBuilding $building, Collection $relationships): void
    {
        if ($relationships->isNotEmpty() && ! $building->trains_units) {
            throw new KingdomWorkbookException('The Building Units sheet assigns Units to '.$building->name.', which does not train Units.');
        }

        $orderedUnitIds = $relationships->sortBy('required_level')->pluck('unit_id')->all();

        $this->buildingUnitAssignmentService->sync($building, $orderedUnitIds, $building->units_per_level, $building->only_at_level);
    }
}
