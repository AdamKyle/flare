<?php

namespace App\Admin\Classes\Imports\Sheets;

use App\Flare\Models\GameClass;
use App\Game\ClassRanks\Values\ClassRankValue;
use App\Game\Core\Values\CoreStatType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToCollection;
use RuntimeException;

class ClassesSheet implements ToCollection
{
    /**
     * Import Class rows from the uploaded spreadsheet and persist them.
     */
    public function collection(Collection $rows): void
    {
        $validatedRows = $this->normalizeAndValidateRows($rows);

        $this->persistInDependencyOrder($validatedRows);
    }

    /**
     * Normalize and validate every meaningful Class row before any row is written.
     */
    private function normalizeAndValidateRows(Collection $rows): array
    {
        $headers = $rows[0]->toArray();
        $seenNames = [];
        $normalizedRows = [];

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
                throw new RuntimeException('Row '.$rowNumber.': duplicate Class name "'.$name.'" in workbook.');
            }

            $seenNames[] = $name;
            $normalizedRows[] = $this->normalizeRow($rawRow, $name, $rowNumber);
        }

        foreach ($normalizedRows as $classData) {
            $this->validateUnlockRequirements($classData, $seenNames);
        }

        return $normalizedRows;
    }

    /**
     * Resolve the required Class name cell, distinguishing a blank end-of-workbook row from an invalid non-string value.
     */
    private function resolveRowName(mixed $name, int $rowNumber): ?string
    {
        if (is_null($name)) {
            return null;
        }

        if (! is_string($name)) {
            throw new RuntimeException('Row '.$rowNumber.': Class name must be text.');
        }

        $name = trim($name);

        return $name === '' ? null : $name;
    }

    /**
     * Normalize and validate one Class workbook row.
     */
    private function normalizeRow(array $rawRow, string $name, int $rowNumber): array
    {
        $existingClass = GameClass::where('name', $name)->first();

        $classData = [
            'row_number' => $rowNumber,
            'name' => $name,
            'damage_stat' => $rawRow['damage_stat'] ?? null,
            'to_hit_stat' => $rawRow['to_hit_stat'] ?? null,
            'str_mod' => $rawRow['str_mod'] ?? 0,
            'dur_mod' => $rawRow['dur_mod'] ?? 0,
            'dex_mod' => $rawRow['dex_mod'] ?? 0,
            'chr_mod' => $rawRow['chr_mod'] ?? 0,
            'int_mod' => $rawRow['int_mod'] ?? 0,
            'agi_mod' => $rawRow['agi_mod'] ?? 0,
            'focus_mod' => $rawRow['focus_mod'] ?? 0,
            'accuracy_mod' => $rawRow['accuracy_mod'] ?? 0,
            'dodge_mod' => $rawRow['dodge_mod'] ?? 0,
            'defense_mod' => $rawRow['defense_mod'] ?? 0,
            'looting_mod' => $rawRow['looting_mod'] ?? 0,
        ];

        $this->validateFieldContract($classData, $rowNumber);

        $classData['primary_required_class_name'] = $this->resolvePrerequisiteName($rawRow['primary_required_class_id'] ?? null, $rowNumber);
        $classData['secondary_required_class_name'] = $this->resolvePrerequisiteName($rawRow['secondary_required_class_id'] ?? null, $rowNumber);
        $classData['primary_required_class_level'] = $this->resolveLevel($rawRow['primary_required_class_level'] ?? null, $rowNumber);
        $classData['secondary_required_class_level'] = $this->resolveLevel($rawRow['secondary_required_class_level'] ?? null, $rowNumber);
        $classData['description'] = $this->resolveDescription($rawRow, $existingClass, $rowNumber);

        return $classData;
    }

    /**
     * Validate the Class stat/modifier field contract for one row using the same rules the Store request enforces.
     */
    private function validateFieldContract(array $classData, int $rowNumber): void
    {
        $validator = Validator::make($classData, [
            'damage_stat' => ['required', 'string', Rule::enum(CoreStatType::class)],
            'to_hit_stat' => ['required', 'string', Rule::enum(CoreStatType::class)],
            'str_mod' => 'required|integer',
            'dur_mod' => 'required|integer',
            'dex_mod' => 'required|integer',
            'chr_mod' => 'required|integer',
            'int_mod' => 'required|integer',
            'agi_mod' => 'required|integer',
            'focus_mod' => 'required|integer',
            'accuracy_mod' => 'required|numeric',
            'dodge_mod' => 'required|numeric',
            'defense_mod' => 'required|numeric',
            'looting_mod' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            throw new RuntimeException('Row '.$rowNumber.': '.$validator->errors()->first());
        }
    }

    /**
     * Resolve a prerequisite Class name cell into its trimmed name, without resolving it to an id.
     */
    private function resolvePrerequisiteName(mixed $name, int $rowNumber): ?string
    {
        if (is_null($name) || (is_string($name) && trim($name) === '')) {
            return null;
        }

        if (! is_string($name)) {
            throw new RuntimeException('Row '.$rowNumber.': prerequisite Class name must be text.');
        }

        return trim($name);
    }

    /**
     * Resolve a required prerequisite Class rank level.
     */
    private function resolveLevel(mixed $level, int $rowNumber): ?int
    {
        if (is_null($level) || $level === '') {
            return null;
        }

        $validator = Validator::make(['level' => $level], [
            'level' => ['integer', 'min:1', 'max:'.ClassRankValue::MAX_LEVEL],
        ]);

        if ($validator->fails()) {
            throw new RuntimeException('Row '.$rowNumber.': '.$validator->errors()->first());
        }

        return $level;
    }

    /**
     * Validate the Class unlock-requirement combination and referenced Class names.
     */
    private function validateUnlockRequirements(array $classData, array $workbookNames): void
    {
        $rowNumber = $classData['row_number'];

        $fields = [
            'primary_required_class_name',
            'secondary_required_class_name',
            'primary_required_class_level',
            'secondary_required_class_level',
        ];

        $populatedCount = collect($fields)->filter(fn (string $field) => ! is_null($classData[$field]))->count();

        if ($populatedCount > 0 && $populatedCount < count($fields)) {
            throw new RuntimeException('Row '.$rowNumber.': a special Class requires both prerequisite Classes and both required levels, or none of them.');
        }

        $primaryName = $classData['primary_required_class_name'];
        $secondaryName = $classData['secondary_required_class_name'];

        if (is_null($primaryName) && is_null($secondaryName)) {
            return;
        }

        if ($primaryName === $secondaryName) {
            throw new RuntimeException('Row '.$rowNumber.': the primary and secondary prerequisite Classes must be different.');
        }

        if ($primaryName === $classData['name'] || $secondaryName === $classData['name']) {
            throw new RuntimeException('Row '.$rowNumber.': a Class cannot require itself.');
        }

        foreach ([$primaryName, $secondaryName] as $prerequisiteName) {
            if (is_null($prerequisiteName)) {
                continue;
            }

            $resolvableInDatabase = GameClass::where('name', $prerequisiteName)->exists();
            $resolvableInWorkbook = in_array($prerequisiteName, $workbookNames, true);

            if (! $resolvableInDatabase && ! $resolvableInWorkbook) {
                throw new RuntimeException('Row '.$rowNumber.': unresolved prerequisite Class "'.$prerequisiteName.'" (not found in the database or this workbook).');
            }
        }
    }

    /**
     * Resolve the Class description while preserving older-workbook compatibility.
     */
    private function resolveDescription(array $rawRow, ?GameClass $existingClass, int $rowNumber): ?string
    {
        if (! array_key_exists('description', $rawRow) || is_null($rawRow['description']) || $rawRow['description'] === '') {
            return $existingClass?->description;
        }

        if (! is_string($rawRow['description'])) {
            throw new RuntimeException('Row '.$rowNumber.': description must be text.');
        }

        return $rawRow['description'];
    }

    /**
     * Persist Class rows in prerequisite-safe dependency order.
     */
    private function persistInDependencyOrder(array $validatedRows): void
    {
        $pendingRows = $validatedRows;
        $persistedIdsByName = [];

        while (count($pendingRows) > 0) {
            $stillPendingRows = [];
            $madeProgress = false;

            foreach ($pendingRows as $classData) {
                $primaryName = $classData['primary_required_class_name'];
                $secondaryName = $classData['secondary_required_class_name'];

                if (! $this->isNameResolvable($primaryName, $persistedIdsByName) || ! $this->isNameResolvable($secondaryName, $persistedIdsByName)) {
                    $stillPendingRows[] = $classData;

                    continue;
                }

                $persistedIdsByName[$classData['name']] = $this->persistClass($classData, $primaryName, $secondaryName, $persistedIdsByName)->id;
                $madeProgress = true;
            }

            if (! $madeProgress) {
                $unresolvedNames = collect($stillPendingRows)->pluck('name')->implode(', ');

                throw new RuntimeException('Unable to resolve prerequisite dependency for Class(es): '.$unresolvedNames.'. The prerequisite reference is missing or cyclic.');
            }

            $pendingRows = $stillPendingRows;
        }
    }

    /**
     * Determine whether a prerequisite Class name can currently resolve to an id.
     */
    private function isNameResolvable(?string $name, array $persistedIdsByName): bool
    {
        if (is_null($name)) {
            return true;
        }

        if (array_key_exists($name, $persistedIdsByName)) {
            return true;
        }

        return GameClass::where('name', $name)->exists();
    }

    /**
     * Resolve a prerequisite Class name to its persisted id.
     */
    private function resolveNameToId(?string $name, array $persistedIdsByName): ?int
    {
        if (is_null($name)) {
            return null;
        }

        if (array_key_exists($name, $persistedIdsByName)) {
            return $persistedIdsByName[$name];
        }

        return GameClass::where('name', $name)->value('id');
    }

    /**
     * Create or update the Class for one validated row, resolving its prerequisite names to ids.
     */
    private function persistClass(array $classData, ?string $primaryName, ?string $secondaryName, array $persistedIdsByName): GameClass
    {
        unset($classData['row_number'], $classData['primary_required_class_name'], $classData['secondary_required_class_name']);

        $classData['primary_required_class_id'] = $this->resolveNameToId($primaryName, $persistedIdsByName);
        $classData['secondary_required_class_id'] = $this->resolveNameToId($secondaryName, $persistedIdsByName);

        $existingClass = GameClass::where('name', $classData['name'])->first();

        if (! is_null($existingClass)) {
            $existingClass->update($classData);

            return $existingClass;
        }

        return GameClass::create($classData);
    }
}
