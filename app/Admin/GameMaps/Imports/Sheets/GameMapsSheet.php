<?php

namespace App\Admin\GameMaps\Imports\Sheets;

use App\Flare\Models\GameMap;
use App\Flare\Models\Location;
use App\Game\Events\Values\EventType;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;

class GameMapsSheet implements ToCollection
{
    /**
     * Validate all workbook rows before updating existing Game Maps.
     */
    public function collection(Collection $rows): void
    {
        $header = $rows->shift();

        if (is_null($header) || $header->toArray() !== $this->expectedHeader()) {
            throw ValidationException::withMessages([
                'game_maps_import' => 'Row 1 must contain the exact Game Maps import headers in the required order.',
            ]);
        }

        $normalizedRows = [];
        $seenGameMapNames = [];

        foreach ($rows->values() as $index => $row) {
            $rowNumber = $index + 2;
            $normalizedRow = $this->normalizeRow($row, $rowNumber);
            $gameMapName = $normalizedRow['name'];

            if (isset($seenGameMapNames[$gameMapName])) {
                throw ValidationException::withMessages([
                    'game_maps_import' => "Row {$rowNumber} duplicates Game Map '{$gameMapName}'. Each Game Map may appear only once.",
                ]);
            }

            $seenGameMapNames[$gameMapName] = true;
            $normalizedRows[] = $normalizedRow;
        }

        foreach ($normalizedRows as $normalizedRow) {
            $gameMap = GameMap::where('name', $normalizedRow['name'])->first();

            if (is_null($gameMap) || $gameMap->name !== $normalizedRow['name']) {
                throw ValidationException::withMessages([
                    'game_maps_import' => "Unknown Game Map '{$normalizedRow['name']}'. Game Maps must already exist before import.",
                ]);
            }

            $gameMap->update($this->supportedUpdates($normalizedRow));
        }
    }

    /**
     * Return the exact supported workbook header sequence.
     */
    private function expectedHeader(): array
    {
        return [
            'name',
            'description',
            'default',
            'kingdom_color',
            'xp_bonus',
            'skill_training_bonus',
            'drop_chance_bonus',
            'enemy_stat_bonus',
            'character_attack_reduction',
            'required_location',
            'only_during_event_type',
            'can_traverse',
        ];
    }

    /**
     * Normalize and validate one Game Map workbook row.
     */
    private function normalizeRow(Collection $row, int $rowNumber): array
    {
        if ($row->count() !== count($this->expectedHeader())) {
            throw ValidationException::withMessages([
                'game_maps_import' => "Row {$rowNumber} must contain exactly 12 columns.",
            ]);
        }

        $data = array_combine($this->expectedHeader(), $row->toArray());
        $name = $this->normalizeRequiredString($data['name'], $rowNumber, 'name');

        $gameMap = GameMap::where('name', $name)->first();

        if (is_null($gameMap) || $gameMap->name !== $name) {
            throw ValidationException::withMessages([
                'game_maps_import' => "Row {$rowNumber} references unknown Game Map '{$name}'. Game Maps must already exist before import.",
            ]);
        }

        return [
            'name' => $name,
            'description' => $this->normalizeNullableString($data['description'], $rowNumber, 'description'),
            'default' => $this->normalizeBoolean($data['default'], $rowNumber, 'default'),
            'kingdom_color' => $this->normalizeKingdomColor($data['kingdom_color'], $rowNumber),
            'xp_bonus' => $this->normalizeNumeric($data['xp_bonus'], $rowNumber, 'xp_bonus'),
            'skill_training_bonus' => $this->normalizeNumeric($data['skill_training_bonus'], $rowNumber, 'skill_training_bonus'),
            'drop_chance_bonus' => $this->normalizeNumeric($data['drop_chance_bonus'], $rowNumber, 'drop_chance_bonus'),
            'enemy_stat_bonus' => $this->normalizeNumeric($data['enemy_stat_bonus'], $rowNumber, 'enemy_stat_bonus'),
            'character_attack_reduction' => $this->normalizeNumeric($data['character_attack_reduction'], $rowNumber, 'character_attack_reduction'),
            'required_location_id' => $this->resolveRequiredLocationId($data['required_location'], $rowNumber),
            'only_during_event_type' => $this->normalizeEventType($data['only_during_event_type'], $rowNumber),
            'can_traverse' => $this->normalizeBoolean($data['can_traverse'], $rowNumber, 'can_traverse'),
        ];
    }

    /**
     * Normalize an explicitly supported spreadsheet boolean value.
     */
    private function normalizeBoolean(mixed $value, int $rowNumber, string $column): bool
    {
        if (is_null($value) || $value === '') {
            throw ValidationException::withMessages([
                'game_maps_import' => "Row {$rowNumber} column '{$column}' must be a valid boolean value.",
            ]);
        }

        $normalized = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if (is_null($normalized)) {
            throw ValidationException::withMessages([
                'game_maps_import' => "Row {$rowNumber} column '{$column}' must be a valid boolean value.",
            ]);
        }

        return $normalized;
    }

    /**
     * Normalize a required string cell.
     */
    private function normalizeRequiredString(mixed $value, int $rowNumber, string $column): string
    {
        if (! is_string($value) || trim($value) === '') {
            throw ValidationException::withMessages([
                'game_maps_import' => "Row {$rowNumber} column '{$column}' must contain a value.",
            ]);
        }

        return $value;
    }

    /**
     * Normalize optional Markdown text without changing its source formatting.
     */
    private function normalizeNullableString(mixed $value, int $rowNumber, string $column): ?string
    {
        if (is_null($value) || $value === '') {
            return null;
        }

        if (! is_string($value)) {
            throw ValidationException::withMessages([
                'game_maps_import' => "Row {$rowNumber} column '{$column}' must contain text or be empty.",
            ]);
        }

        return $value;
    }

    /**
     * Normalize a Kingdom color that uses exact hexadecimal syntax.
     */
    private function normalizeKingdomColor(mixed $value, int $rowNumber): string
    {
        if (! is_string($value) || preg_match('/^#[0-9A-Fa-f]{6}$/', $value) !== 1) {
            throw ValidationException::withMessages([
                'game_maps_import' => "Row {$rowNumber} column 'kingdom_color' must use a # followed by exactly six hexadecimal characters.",
            ]);
        }

        return $value;
    }

    /**
     * Normalize a numeric bonus cell without accepting non-numeric text.
     */
    private function normalizeNumeric(mixed $value, int $rowNumber, string $column): int|float
    {
        if (! is_numeric($value)) {
            throw ValidationException::withMessages([
                'game_maps_import' => "Row {$rowNumber} column '{$column}' must be numeric.",
            ]);
        }

        return $value + 0;
    }

    /**
     * Resolve an optional exact Location name to its identifier.
     */
    private function resolveRequiredLocationId(mixed $value, int $rowNumber): ?int
    {
        if (is_null($value) || $value === '') {
            return null;
        }

        if (! is_string($value)) {
            throw ValidationException::withMessages([
                'game_maps_import' => "Row {$rowNumber} column 'required_location' must contain an exact Location name or be empty.",
            ]);
        }

        $location = Location::where('name', $value)->first();

        if (is_null($location) || $location->name !== $value) {
            throw ValidationException::withMessages([
                'game_maps_import' => "Row {$rowNumber} references unknown Location '{$value}'.",
            ]);
        }

        return $location->id;
    }

    /**
     * Normalize an optional event type against the existing EventType values.
     */
    private function normalizeEventType(mixed $value, int $rowNumber): ?int
    {
        if (is_null($value) || $value === '') {
            return null;
        }

        $eventType = filter_var($value, FILTER_VALIDATE_INT);

        if ($eventType === false || ! array_key_exists($eventType, EventType::getOptionsForSelect())) {
            throw ValidationException::withMessages([
                'game_maps_import' => "Row {$rowNumber} column 'only_during_event_type' contains an invalid event type.",
            ]);
        }

        return $eventType;
    }

    /**
     * Return only fields the Game Maps workbook is permitted to update.
     */
    private function supportedUpdates(array $normalizedRow): array
    {
        return [
            'description' => $normalizedRow['description'],
            'default' => $normalizedRow['default'],
            'kingdom_color' => $normalizedRow['kingdom_color'],
            'xp_bonus' => $normalizedRow['xp_bonus'],
            'skill_training_bonus' => $normalizedRow['skill_training_bonus'],
            'drop_chance_bonus' => $normalizedRow['drop_chance_bonus'],
            'enemy_stat_bonus' => $normalizedRow['enemy_stat_bonus'],
            'character_attack_reduction' => $normalizedRow['character_attack_reduction'],
            'required_location_id' => $normalizedRow['required_location_id'],
            'only_during_event_type' => $normalizedRow['only_during_event_type'],
            'can_traverse' => $normalizedRow['can_traverse'],
        ];
    }
}
