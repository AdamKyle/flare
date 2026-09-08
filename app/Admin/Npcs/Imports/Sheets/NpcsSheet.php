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
