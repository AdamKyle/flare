<?php

namespace App\Admin\Npcs\Imports\Sheets;

use App\Flare\Models\GameMap;
use App\Flare\Models\Npc;
use App\Game\Npcs\Values\NpcType;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class NpcsSheet implements ToCollection
{
    /**
     * Import NPC rows from the uploaded spreadsheet and persist them.
     *
     * Every meaningful row is normalized and validated first; the workbook is written only when
     * every meaningful row resolves successfully, so an invalid later row cannot leave an earlier
     * row's write applied.
     *
     * @param  Collection<int, Collection<int, mixed>>  $rows  Imported workbook rows.
     * @return void NPCs are created or updated in place.
     */
    public function collection(Collection $rows): void
    {
        $validatedRows = $this->normalizeAndValidateRows($rows);

        if (is_null($validatedRows)) {
            return;
        }

        foreach ($validatedRows as $npcData) {
            Npc::updateOrCreate(['id' => $npcData['id'] ?? null], $npcData);
        }
    }

    /**
     * Normalize and validate every meaningful NPC row before any row is written.
     *
     * A blank `real_name` marks the end of the workbook's meaningful data. Any other row that
     * cannot be fully resolved (an unrecognized Game Map or NPC type) invalidates the entire import.
     *
     * @param  Collection<int, Collection<int, mixed>>  $rows  Imported workbook rows.
     * @return array<int, array<string, mixed>>|null Validated NPC payloads, or null when any row is invalid.
     */
    private function normalizeAndValidateRows(Collection $rows): ?array
    {
        $headers = $rows[0]->toArray();
        $validatedRows = [];

        foreach ($rows as $index => $row) {
            if ($index === 0) {
                continue;
            }

            $rawRow = array_combine($headers, $row->toArray());

            if (is_null($rawRow['real_name'] ?? null)) {
                break;
            }

            $npcData = $this->normalizeRow($rawRow);

            if (is_null($npcData)) {
                return null;
            }

            $validatedRows[] = $npcData;
        }

        return $validatedRows;
    }

    /**
     * Normalize a single raw NPC row, resolving the Game Map relationship by name.
     *
     * @param  array<string, mixed>  $rawRow  Raw spreadsheet row keyed by header.
     * @return array<string, mixed>|null Normalized NPC attributes, or null when the row is invalid.
     */
    private function normalizeRow(array $rawRow): ?array
    {
        if (! is_null($rawRow['type'] ?? null) && is_null(NpcType::tryFrom($rawRow['type']))) {
            return null;
        }

        $cleanData = [];

        foreach ($rawRow as $key => $value) {
            if (is_null($value)) {
                continue;
            }

            if ($key === 'game_map_id') {
                $gameMap = GameMap::where('name', $value)->first();

                if (is_null($gameMap)) {
                    return null;
                }

                $value = $gameMap->id;
            }

            $cleanData[$key] = $value;
        }

        return $cleanData;
    }
}
