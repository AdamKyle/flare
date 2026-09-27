<?php

namespace App\Admin\Kingdoms\Imports\Sheets;

use App\Flare\Models\GameBuilding;
use App\Flare\Models\PassiveSkill;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class BuildingsSheet implements ToCollection
{
    private array $importedBuildingIds = [];

    /**
     * Import kingdom building rows from the uploaded spreadsheet and persist them.
     *
     * @param Collection $rows
     * @return void
     */
    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        $headers = $rows->first()->toArray();

        $rows->slice(1)->each(function (Collection $row) use ($headers): void {
            $this->importBuilding(array_combine($headers, $row->toArray()));
        });
    }

    /**
     * Return the ids of every Building this sheet defined.
     *
     * @return array
     */
    public function importedBuildingIds(): array
    {
        return $this->importedBuildingIds;
    }

    /**
     * Create or update the Building described by a single spreadsheet row.
     *
     * @param array $buildingData
     * @return void
     */
    private function importBuilding(array $buildingData): void
    {
        $cleanData = array_filter($buildingData, fn (mixed $value): bool => ! is_null($value));
        $cleanData['is_locked'] = $cleanData['is_locked'] ?? false;

        $gameBuilding = GameBuilding::updateOrCreate(['id' => $cleanData['id'] ?? null], $this->resolvePassiveSkill($cleanData));

        $this->importedBuildingIds[] = $gameBuilding->id;
    }

    /**
     * Replace the row's Passive Skill name with its id, dropping the passive requirement when the name is unknown.
     *
     * @param array $buildingData
     * @return array
     */
    private function resolvePassiveSkill(array $buildingData): array
    {
        if (! isset($buildingData['passive_skill_id'])) {
            return $buildingData;
        }

        $passiveSkillId = PassiveSkill::where('name', $buildingData['passive_skill_id'])->value('id');

        if (is_null($passiveSkillId)) {
            unset($buildingData['passive_skill_id'], $buildingData['level_required']);

            return $buildingData;
        }

        $buildingData['passive_skill_id'] = $passiveSkillId;

        return $buildingData;
    }
}
