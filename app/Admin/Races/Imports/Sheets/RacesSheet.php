<?php

namespace App\Admin\Races\Imports\Sheets;

use App\Flare\Models\GameRace;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use RuntimeException;

class RacesSheet implements ToCollection
{
    /**
     * Import Race rows from the uploaded spreadsheet and persist them.
     */
    public function collection(Collection $rows): void
    {
        $validatedRows = $this->normalizeAndValidateRows($rows);

        foreach ($validatedRows as $raceData) {
            $existingRace = GameRace::where('name', $raceData['name'])->first();

            if (! is_null($existingRace)) {
                $existingRace->update($raceData);

                continue;
            }

            GameRace::create($raceData);
        }
    }

    /**
     * Normalize and validate every meaningful Race row before any row is written.
     */
    private function normalizeAndValidateRows(Collection $rows): array
    {
        $headers = $rows[0]->toArray();
        $seenNames = [];
        $validatedRows = [];

        foreach ($rows as $index => $row) {
            if ($index === 0) {
                continue;
            }

            $rowNumber = $index + 1;
            $rawRow = array_combine($headers, $row->toArray());
            $name = $this->resolveRowName($rawRow['name'] ?? null, $rowNumber);

            if (is_null($name)) {
                break;
            }

            if (in_array($name, $seenNames, true)) {
                throw new RuntimeException('Row '.$rowNumber.': duplicate Race name "'.$name.'" in workbook.');
            }

            $seenNames[] = $name;
            $validatedRows[] = $this->normalizeRow($rawRow, $name, $rowNumber);
        }

        return $validatedRows;
    }

    /**
     * Resolve the required Race name cell, distinguishing a blank end-of-workbook row from an invalid non-string value.
     */
    private function resolveRowName(mixed $name, int $rowNumber): ?string
    {
        if (is_null($name)) {
            return null;
        }

        if (! is_string($name)) {
            throw new RuntimeException('Row '.$rowNumber.': Race name must be text.');
        }

        $name = trim($name);

        return $name === '' ? null : $name;
    }

    /**
     * Normalize a single raw Race row into its validated identity, description, and image path.
     */
    private function normalizeRow(array $rawRow, string $name, int $rowNumber): array
    {
        $existingRace = GameRace::where('name', $name)->first();

        $raceData = ['name' => $name];

        if (array_key_exists('description', $rawRow)) {
            $raceData['description'] = $this->resolveOptionalString($rawRow['description'], $rowNumber, 'description');
        } else {
            $raceData['description'] = $existingRace?->description;
        }

        if (array_key_exists('image_path', $rawRow)) {
            $raceData['image_path'] = $this->resolveOptionalString($rawRow['image_path'], $rowNumber, 'image_path');
        } else {
            $raceData['image_path'] = $existingRace?->image_path;
        }

        return $raceData;
    }

    /**
     * Resolve an optional Race workbook text cell.
     */
    private function resolveOptionalString(mixed $value, int $rowNumber, string $column): ?string
    {
        if (is_null($value) || $value === '') {
            return null;
        }

        if (! is_string($value)) {
            throw new RuntimeException('Row '.$rowNumber.': "'.$column.'" must be text.');
        }

        return $value;
    }
}
