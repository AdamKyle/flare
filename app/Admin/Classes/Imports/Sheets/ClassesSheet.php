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
     *
     * Every meaningful row is normalized and validated against the complete Class field contract,
     * including workbook-level prerequisite identities, before any row is written. Validated rows
     * are then persisted in dependency-safe order so a special Class may require another Class
     * defined earlier in the very same workbook, even on a fresh database.
     *
     * @param  Collection<int, Collection<int, mixed>>  $rows  Imported workbook rows.
     * @return void Classes are created or updated in place.
     */
    public function collection(Collection $rows): void
    {
        $validatedRows = $this->normalizeAndValidateRows($rows);

        $this->persistInDependencyOrder($validatedRows);
    }

    /**
     * Normalize and validate every meaningful Class row before any row is written.
     *
     * A blank `name` marks the end of the workbook's meaningful data. A duplicate `name` within the
     * workbook fails the entire import with the offending row number in the exception message.
     *
     * @param  Collection<int, Collection<int, mixed>>  $rows  Imported workbook rows.
     * @return array<int, array<string, mixed>> Validated Class payloads, keyed by prerequisite name.
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
     * Resolve the required Class name cell, distinguishing a blank end-of-workbook row from an
     * invalid non-string value.
     *
     * @param  mixed  $name  Raw Class name cell.
     * @param  int  $rowNumber  One-based workbook row number, for error context.
     * @return string|null Trimmed Class name, or null when the workbook has no more data.
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
     * Normalize a single raw Class row, validating its field contract and resolving prerequisite
     * Class names, without yet resolving those names to ids.
     *
     * @param  array<string, mixed>  $rawRow  Raw spreadsheet row keyed by header.
     * @param  string  $name  Resolved Class name.
     * @param  int  $rowNumber  One-based workbook row number, for error context.
     * @return array<string, mixed> Normalized Class attributes, keyed by row number and prerequisite name.
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
     * Validate the Class stat/modifier field contract for one row using the same rules the Store
     * request enforces.
     *
     * @param  array<string, mixed>  $classData  Normalized Class workbook row being validated.
     * @param  int  $rowNumber  Workbook row number used for validation context.
     * @return void No direct return value; validation either completes or raises the existing import failure.
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
     *
     * @param  mixed  $name  Prerequisite Class name from the workbook.
     * @param  int  $rowNumber  One-based workbook row number, for error context.
     * @return string|null Trimmed prerequisite Class name, or null when no prerequisite was given.
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
     *
     * @param  mixed  $level  Required Class rank level from the workbook.
     * @param  int  $rowNumber  One-based workbook row number, for error context.
     * @return int|null Resolved level, or null when no level was given.
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
     * Enforce that unlock requirements are either fully absent or fully populated, that the two
     * prerequisite Class names differ, that a Class cannot require itself by name, and that every
     * populated prerequisite name resolves to an existing database Class or another unique Class
     * name in this workbook.
     *
     * @param  array<string, mixed>  $classData  Row data with unlock fields resolved to names.
     * @param  array<int, string>  $workbookNames  Normalized Class names available from the same workbook.
     * @return void No direct return value; validation either completes or raises the existing import failure.
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
     * Resolve the Class description, preserving the existing value when an older workbook omits
     * the `description` column or leaves it blank.
     *
     * @param  array<string, mixed>  $rawRow  Raw spreadsheet row keyed by header.
     * @param  GameClass|null  $existingClass  Existing Class being updated, when one exists.
     * @param  int  $rowNumber  One-based workbook row number, for error context.
     * @return string|null Resolved Class description.
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
     * Persist every validated Class row in dependency-safe order, so a row whose prerequisite
     * Class is defined later in the same workbook still resolves once that Class has been created.
     *
     * @param  array<int, array<string, mixed>>  $validatedRows  Validated Class payloads.
     * @return void Classes are created or updated in place.
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
     * Determine whether a prerequisite Class name can currently be resolved to an id: it is either
     * absent, already persisted earlier in this import run, or already exists in the database.
     *
     * @param  string|null  $name  Prerequisite Class name to check.
     * @param  array<string, int>  $persistedIdsByName  Class ids persisted so far in this import run.
     * @return bool Whether the prerequisite name can be resolved right now.
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
     * Resolve a prerequisite Class name to its id, preferring a Class already persisted earlier in
     * this import run over a database lookup.
     *
     * @param  string|null  $name  Prerequisite Class name to resolve.
     * @param  array<string, int>  $persistedIdsByName  Class ids persisted so far in this import run.
     * @return int|null Resolved Class id, or null when no prerequisite name was given.
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
     *
     * @param  array<string, mixed>  $classData  Validated Class payload, keyed by prerequisite name.
     * @param  string|null  $primaryName  Resolved primary prerequisite Class name.
     * @param  string|null  $secondaryName  Resolved secondary prerequisite Class name.
     * @param  array<string, int>  $persistedIdsByName  Class ids persisted so far in this import run.
     * @return GameClass Created or updated Class.
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
