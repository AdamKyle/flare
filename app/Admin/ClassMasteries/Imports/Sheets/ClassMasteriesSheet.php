<?php

namespace App\Admin\ClassMasteries\Imports\Sheets;

use App\Flare\Models\GameClassSpecial;
use App\Game\ClassRanks\Values\ClassRankValue;
use App\Game\Core\Combat\Values\AttackType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToCollection;
use RuntimeException;

class ClassMasteriesSheet implements ToCollection
{
    /**
     * Import Class Mastery rows from the uploaded spreadsheet and persist them.
     *
     * Every meaningful row is normalized and validated first; the workbook is written only when
     * every meaningful row resolves successfully, so an invalid later row cannot leave an earlier
     * row's write applied.
     *
     * @param  Collection<int, Collection<int, mixed>>  $rows  Imported workbook rows.
     * @return void Class Masteries are created or updated in place.
     */
    public function collection(Collection $rows): void
    {
        $validatedRows = $this->normalizeAndValidateRows($rows);

        foreach ($validatedRows as $masteryData) {
            $existingMastery = GameClassSpecial::where('name', $masteryData['name'])->first();

            if (! is_null($existingMastery)) {
                $existingMastery->update($masteryData);

                continue;
            }

            GameClassSpecial::create($masteryData);
        }
    }

    /**
     * Normalize and validate every meaningful Class Mastery row before any row is written.
     *
     * @param  Collection<int, Collection<int, mixed>>  $rows  Imported workbook rows.
     * @return array<int, array<string, mixed>> Validated Class Mastery payloads.
     */
    private function normalizeAndValidateRows(Collection $rows): array
    {
        $headers = $rows[0]->toArray();
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

            $validatedRows[] = $this->normalizeRow($rawRow, $name, $rowNumber);
        }

        return $validatedRows;
    }

    /**
     * Resolve the required Class Mastery name cell, distinguishing a blank end-of-workbook row
     * from an invalid non-string value.
     *
     * @param  mixed  $name  Raw Class Mastery name cell.
     * @param  int  $rowNumber  One-based workbook row number, for error context.
     * @return string|null Trimmed Class Mastery name, or null when the workbook has no more data.
     */
    private function resolveRowName(mixed $name, int $rowNumber): ?string
    {
        if (is_null($name)) {
            return null;
        }

        if (! is_string($name)) {
            throw new RuntimeException('Row '.$rowNumber.': Class Mastery name must be text.');
        }

        $name = trim($name);

        return $name === '' ? null : $name;
    }

    /**
     * Normalize a single raw Class Mastery row, validating its referenced Class id and field contract.
     *
     * @param  array<string, mixed>  $rawRow  Raw spreadsheet row keyed by header.
     * @param  string  $name  Resolved Class Mastery name.
     * @param  int  $rowNumber  One-based workbook row number, for error context.
     * @return array<string, mixed> Normalized Class Mastery attributes.
     */
    private function normalizeRow(array $rawRow, string $name, int $rowNumber): array
    {
        $existingMastery = GameClassSpecial::where('name', $name)->first();

        $masteryData = [
            'game_class_id' => $rawRow['game_class_id'] ?? null,
            'name' => $name,
            'requires_class_rank_level' => $rawRow['requires_class_rank_level'] ?? 0,
            'specialty_damage' => $rawRow['specialty_damage'] ?? null,
            'increase_specialty_damage_per_level' => $rawRow['increase_specialty_damage_per_level'] ?? null,
            'specialty_damage_uses_damage_stat_amount' => $rawRow['specialty_damage_uses_damage_stat_amount'] ?? null,
            'attack_type_required' => $this->resolveAttackType($rawRow['attack_type_required'] ?? null, $rowNumber),
            'base_damage_mod' => $rawRow['base_damage_mod'] ?? null,
            'base_ac_mod' => $rawRow['base_ac_mod'] ?? null,
            'base_healing_mod' => $rawRow['base_healing_mod'] ?? null,
            'base_spell_damage_mod' => $rawRow['base_spell_damage_mod'] ?? null,
            'health_mod' => $rawRow['health_mod'] ?? null,
            'base_damage_stat_increase' => $rawRow['base_damage_stat_increase'] ?? null,
            'spell_evasion' => $rawRow['spell_evasion'] ?? null,
            'affix_damage_reduction' => $rawRow['affix_damage_reduction'] ?? null,
            'healing_reduction' => $rawRow['healing_reduction'] ?? null,
            'skill_reduction' => $rawRow['skill_reduction'] ?? null,
            'resistance_reduction' => $rawRow['resistance_reduction'] ?? null,
        ];

        $this->validateFieldContract($masteryData, $rowNumber);

        $masteryData['description'] = $this->resolveDescription($rawRow, $existingMastery, $rowNumber);

        return $masteryData;
    }

    /**
     * Validate the Class Mastery field contract for one row.
     *
     * @param  array<string, mixed>  $masteryData  Normalized Class Mastery data resolved so far.
     * @param  int  $rowNumber  Workbook row number used for validation context.
     * @return void No direct return value; validation either completes or raises the existing import failure.
     */
    private function validateFieldContract(array $masteryData, int $rowNumber): void
    {
        $validator = Validator::make($masteryData, [
            'game_class_id' => ['required', 'integer', Rule::exists('game_classes', 'id')],
            'requires_class_rank_level' => ['required', 'integer', 'min:0', 'max:'.ClassRankValue::MAX_LEVEL],
            'specialty_damage' => 'nullable|integer',
            'increase_specialty_damage_per_level' => 'nullable|integer',
            'specialty_damage_uses_damage_stat_amount' => 'nullable|numeric',
            'base_damage_mod' => 'nullable|numeric',
            'base_ac_mod' => 'nullable|numeric',
            'base_healing_mod' => 'nullable|numeric',
            'base_spell_damage_mod' => 'nullable|numeric',
            'health_mod' => 'nullable|numeric',
            'base_damage_stat_increase' => 'nullable|numeric',
            'spell_evasion' => 'nullable|numeric',
            'affix_damage_reduction' => 'nullable|numeric',
            'healing_reduction' => 'nullable|numeric',
            'skill_reduction' => 'nullable|numeric',
            'resistance_reduction' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            throw new RuntimeException('Row '.$rowNumber.': '.$validator->errors()->first());
        }
    }

    /**
     * Resolve the optional attack type, permitting either an existing AttackType value or the
     * literal `any` domain value.
     *
     * @param  mixed  $attackType  Raw attack type cell.
     * @param  int  $rowNumber  One-based workbook row number, for error context.
     * @return string|null Resolved attack type value, or null when omitted.
     */
    private function resolveAttackType(mixed $attackType, int $rowNumber): ?string
    {
        if (is_null($attackType) || $attackType === '') {
            return null;
        }

        if (! is_string($attackType)) {
            throw new RuntimeException('Row '.$rowNumber.': attack_type_required must be text.');
        }

        $allowedValues = array_map(fn (AttackType $type): string => $type->value, AttackType::cases());
        $allowedValues[] = 'any';

        if (! in_array($attackType, $allowedValues, true)) {
            throw new RuntimeException('Row '.$rowNumber.': unknown attack_type_required "'.$attackType.'".');
        }

        return $attackType;
    }

    /**
     * Resolve the Class Mastery description, preserving the existing value when an older workbook
     * omits the `description` column or leaves it blank, and never writing null to the non-null
     * `description` column for a new Class Mastery.
     *
     * @param  array<string, mixed>  $rawRow  Raw spreadsheet row keyed by header.
     * @param  GameClassSpecial|null  $existingMastery  Existing Class Mastery being updated, when one exists.
     * @param  int  $rowNumber  One-based workbook row number, for error context.
     * @return string Resolved Class Mastery description.
     */
    private function resolveDescription(array $rawRow, ?GameClassSpecial $existingMastery, int $rowNumber): string
    {
        if (! array_key_exists('description', $rawRow) || is_null($rawRow['description']) || $rawRow['description'] === '') {
            return $existingMastery?->description ?? '';
        }

        if (! is_string($rawRow['description'])) {
            throw new RuntimeException('Row '.$rowNumber.': description must be text.');
        }

        return $rawRow['description'];
    }
}
