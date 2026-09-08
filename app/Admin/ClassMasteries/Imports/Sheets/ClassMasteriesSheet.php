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
     * Resolve the required Class Mastery name cell.
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
     * Resolve the optional attack type, permitting either an existing AttackType value or the literal `any` domain value.
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
     * Resolve the Class Mastery description while preserving older-workbook compatibility.
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
