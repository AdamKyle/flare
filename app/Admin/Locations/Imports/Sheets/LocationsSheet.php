<?php

namespace App\Admin\Locations\Imports\Sheets;

use App\Flare\Models\GameMap;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Game\Maps\Values\LocationType;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class LocationsSheet implements ToCollection
{
    /**
     * Import Location rows from the uploaded spreadsheet and persist them.
     */
    public function collection(Collection $rows): void
    {
        $validatedRows = $this->normalizeAndValidateRows($rows);

        if (is_null($validatedRows)) {
            return;
        }

        foreach ($validatedRows as $locationData) {
            Location::updateOrCreate(['name' => $locationData['name']], $locationData);
        }
    }

    /**
     * Normalize and validate every meaningful Location row before any row is written.
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

            if (is_null($rawRow['name'] ?? null)) {
                break;
            }

            $locationData = $this->normalizeRow($rawRow);

            if (is_null($locationData)) {
                return null;
            }

            $validatedRows[] = $locationData;
        }

        return $validatedRows;
    }

    /**
     * Normalize a single raw Location row, resolving related records by name.
     */
    private function normalizeRow(array $rawRow): ?array
    {
        if (! is_null($rawRow['type'] ?? null) && is_null(LocationType::tryFrom($rawRow['type']))) {
            return null;
        }

        $cleanData = $this->resolveRowRelationships($rawRow);

        if (is_null($cleanData)) {
            return null;
        }

        if (! isset($cleanData['can_players_enter'])) {
            $cleanData['can_players_enter'] = false;
        }

        if (! isset($cleanData['can_auto_battle'])) {
            $cleanData['can_auto_battle'] = false;
        }

        if (isset($cleanData['enemy_strength_type']) && ! isset($cleanData['type'])) {
            $cleanData['type'] = LocationType::SPECIAL->value;
        }

        unset(
            $cleanData['enemy_strength_type'],
            $cleanData['enemy_strength_increase'],
            $cleanData['delve_enemy_strength_increase'],
        );

        return $cleanData;
    }

    /**
     * Resolve the Game Map and quest Item relationships referenced by name.
     */
    private function resolveRowRelationships(array $rawRow): ?array
    {
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

            if ($key === 'quest_reward_item_id' || $key === 'required_quest_item_id') {
                $item = Item::where('name', $value)->first();

                if (is_null($item)) {
                    return null;
                }

                $value = $item->id;
            }

            $cleanData[$key] = $value;
        }

        return $cleanData;
    }
}
