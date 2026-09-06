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
     *
     * Every meaningful row is normalized and validated first; the workbook is written only when
     * every meaningful row resolves successfully, so an invalid later row cannot leave an earlier
     * row's write applied. Obsolete racial stat/combat modifier columns from legacy workbooks are
     * ignored. A missing `description`/`image_path` column leaves the existing value untouched on
     * update; a present but blank cell explicitly clears the value.
     *
     * @param  Collection<int, Collection<int, mixed>>  $rows  Imported workbook rows.
     * @return void Races are created or updated in place.
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
     *
     * A blank `name` marks the end of the workbook's meaningful data. A duplicate `name` within the
     * workbook fails the entire import with the offending row number in the exception message.
     *
     * @param  Collection<int, Collection<int, mixed>>  $rows  Imported workbook rows.
     * @return array<int, array<string, mixed>> Validated Race payloads.
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
     * Resolve the required Race name cell, distinguishing a blank end-of-workbook row from an
     * invalid non-string value.
     *
     * @param  mixed  $name  Raw Race name cell.
     * @param  int  $rowNumber  One-based workbook row number, for error context.
     * @return string|null Trimmed Race name, or null when the workbook has no more data.
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
     *
     * @param  array<string, mixed>  $rawRow  Raw spreadsheet row keyed by header.
     * @param  string  $name  Resolved Race name.
     * @param  int  $rowNumber  One-based workbook row number, for error context.
     * @return array<string, mixed> Normalized Race attributes.
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
     * Resolve an optional text cell present in the workbook: a blank/null cell clears the value,
     * and a populated cell must be text.
     *
     * @param  mixed  $value  Raw spreadsheet cell value.
     * @param  int  $rowNumber  One-based workbook row number, for error context.
     * @param  string  $column  Spreadsheet column name.
     * @return string|null Resolved value, or null when blank.
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
