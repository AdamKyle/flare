<?php

namespace App\Admin\LocationGems\Imports\Sheets;

use App\Flare\Models\GameLocationGemParamter;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Location;
use App\Game\Gems\Values\GemTypeValue;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use RuntimeException;

class LocationGemsSheet implements ToCollection
{
    /**
     * Import Location Gem profile rows from the uploaded spreadsheet and persist them.
     *
     * Every meaningful row is normalized and validated first; the workbook is written only when
     * every meaningful row resolves successfully, so an invalid later row cannot leave an earlier
     * row's write applied.
     *
     * @param  Collection<int, Collection<int, mixed>>  $rows  Imported workbook rows.
     * @return void Location Gem profiles are created or updated in place.
     */
    public function collection(Collection $rows): void
    {
        $validatedRows = $this->normalizeAndValidateRows($rows);

        foreach ($validatedRows as $profileData) {
            $existingProfile = GameLocationGemParamter::where('name', $profileData['name'])->first();

            if (! is_null($existingProfile)) {
                $existingProfile->update($profileData);

                continue;
            }

            GameLocationGemParamter::create($profileData);
        }
    }

    /**
     * Normalize and validate every meaningful Location Gem profile row before any row is written.
     *
     * @param  Collection<int, Collection<int, mixed>>  $rows  Imported workbook rows.
     * @return array<int, array<string, mixed>> Validated Location Gem profile payloads.
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
            $name = $this->resolveRequiredString($rawRow['name'] ?? null, $rowNumber, 'name', allowEndOfWorkbook: true);

            if (is_null($name)) {
                break;
            }

            $validatedRows[] = $this->normalizeRow($rawRow, $name, $rowNumber);
        }

        return $validatedRows;
    }

    /**
     * Normalize a single raw Location Gem profile row, resolving its Map/Location, crafting
     * Skills, and atonement.
     *
     * @param  array<string, mixed>  $rawRow  Raw spreadsheet row keyed by header.
     * @param  string  $name  Resolved profile name.
     * @param  int  $rowNumber  One-based workbook row number, for error context.
     * @return array<string, mixed> Normalized Location Gem profile attributes.
     */
    private function normalizeRow(array $rawRow, string $name, int $rowNumber): array
    {
        $gameMapName = $this->resolveRequiredString($rawRow['game_map_name'] ?? null, $rowNumber, 'game_map_name');
        $locationName = $this->resolveRequiredString($rawRow['location_name'] ?? null, $rowNumber, 'location_name');

        $location = Location::eligibleForLocationGems()
            ->whereHas('map', fn ($mapQuery) => $mapQuery->where('name', $gameMapName)->whereNull('generated_map_type'))
            ->where('name', $locationName)
            ->first();

        if (is_null($location)) {
            throw new RuntimeException('Row '.$rowNumber.': unknown or ineligible Location "'.$locationName.'" on Map "'.$gameMapName.'".');
        }

        $fallbackRange = $this->resolveRange($rawRow['unique_mythic_cosmic_item_drop_chance_increase_range'] ?? null, $rowNumber, 'unique_mythic_cosmic_item_drop_chance_increase_range');

        return [
            'location_id' => $location->id,
            'name' => $name,
            'description' => $this->resolveNullableString($rawRow['description'] ?? null, $rowNumber, 'description'),
            'crafting_skill_ids' => $this->resolveCraftingSkillIds($rawRow['crafting_skill_names'] ?? null, $rowNumber),
            'character_xp_bonus_range' => $this->resolveRange($rawRow['character_xp_bonus_range'] ?? null, $rowNumber, 'character_xp_bonus_range'),
            'character_class_rank_xp_bonus_range' => $this->resolveRange($rawRow['character_class_rank_xp_bonus_range'] ?? null, $rowNumber, 'character_class_rank_xp_bonus_range'),
            'kingdom_passive_training_reduction_range' => $this->resolveRange($rawRow['kingdom_passive_training_reduction_range'] ?? null, $rowNumber, 'kingdom_passive_training_reduction_range'),
            'gold_gain_range' => $this->resolveRange($rawRow['gold_gain_range'] ?? null, $rowNumber, 'gold_gain_range'),
            'gold_dust_gain_range' => $this->resolveRange($rawRow['gold_dust_gain_range'] ?? null, $rowNumber, 'gold_dust_gain_range'),
            'shards_gain_range' => $this->resolveRange($rawRow['shards_gain_range'] ?? null, $rowNumber, 'shards_gain_range'),
            'copper_coin_gain_range' => $this->resolveRange($rawRow['copper_coin_gain_range'] ?? null, $rowNumber, 'copper_coin_gain_range'),
            'character_class_specialty_xp_gain_range' => $this->resolveRange($rawRow['character_class_specialty_xp_gain_range'] ?? null, $rowNumber, 'character_class_specialty_xp_gain_range'),
            'crafting_skill_bonus_range' => $this->resolveRange($rawRow['crafting_skill_bonus_range'] ?? null, $rowNumber, 'crafting_skill_bonus_range'),
            'item_drop_chance_increase_range' => $this->resolveRange($rawRow['item_drop_chance_increase_range'] ?? null, $rowNumber, 'item_drop_chance_increase_range'),
            'unique_item_drop_chance_increase_range' => $this->resolveRange($rawRow['unique_item_drop_chance_increase_range'] ?? null, $rowNumber, 'unique_item_drop_chance_increase_range') ?? $fallbackRange,
            'mythic_item_drop_chance_increase_range' => $this->resolveRange($rawRow['mythic_item_drop_chance_increase_range'] ?? null, $rowNumber, 'mythic_item_drop_chance_increase_range') ?? $fallbackRange,
            'cosmic_item_drop_chance_increase_range' => $this->resolveRange($rawRow['cosmic_item_drop_chance_increase_range'] ?? null, $rowNumber, 'cosmic_item_drop_chance_increase_range') ?? $fallbackRange,
            'enemy_strength_increase_range' => $this->resolveRange($rawRow['enemy_strength_increase_range'] ?? null, $rowNumber, 'enemy_strength_increase_range'),
            'enemy_healing_increase_range' => $this->resolveRange($rawRow['enemy_healing_increase_range'] ?? null, $rowNumber, 'enemy_healing_increase_range'),
            'enemy_spell_evasion_range' => $this->resolveRange($rawRow['enemy_spell_evasion_range'] ?? null, $rowNumber, 'enemy_spell_evasion_range'),
            'enemy_affix_resistance_range' => $this->resolveRange($rawRow['enemy_affix_resistance_range'] ?? null, $rowNumber, 'enemy_affix_resistance_range'),
            'enemy_entrancing_chance_range' => $this->resolveRange($rawRow['enemy_entrancing_chance_range'] ?? null, $rowNumber, 'enemy_entrancing_chance_range'),
            'enemy_devouring_light_chance_range' => $this->resolveRange($rawRow['enemy_devouring_light_chance_range'] ?? null, $rowNumber, 'enemy_devouring_light_chance_range'),
            'enemy_devouring_darkness_chance_range' => $this->resolveRange($rawRow['enemy_devouring_darkness_chance_range'] ?? null, $rowNumber, 'enemy_devouring_darkness_chance_range'),
            'enemy_ambush_chance_range' => $this->resolveRange($rawRow['enemy_ambush_chance_range'] ?? null, $rowNumber, 'enemy_ambush_chance_range'),
            'enemy_ambush_resistance_range' => $this->resolveRange($rawRow['enemy_ambush_resistance_range'] ?? null, $rowNumber, 'enemy_ambush_resistance_range'),
            'enemy_counter_chance_range' => $this->resolveRange($rawRow['enemy_counter_chance_range'] ?? null, $rowNumber, 'enemy_counter_chance_range'),
            'enemy_counter_resistance_range' => $this->resolveRange($rawRow['enemy_counter_resistance_range'] ?? null, $rowNumber, 'enemy_counter_resistance_range'),
            'enemy_quest_item_drop_chance_increase_range' => $this->resolveRange($rawRow['enemy_quest_item_drop_chance_increase_range'] ?? null, $rowNumber, 'enemy_quest_item_drop_chance_increase_range'),
            'monster_xp_increase_range' => $this->resolveRange($rawRow['monster_xp_increase_range'] ?? null, $rowNumber, 'monster_xp_increase_range'),
            'monster_gold_drop_increase_range' => $this->resolveRange($rawRow['monster_gold_drop_increase_range'] ?? null, $rowNumber, 'monster_gold_drop_increase_range'),
            'monster_atonement' => $this->resolveAtonement($rawRow['monster_atonement'] ?? null, $rowNumber),
            'monster_atonement_range' => $this->resolveRange($rawRow['monster_atonement_range'] ?? null, $rowNumber, 'monster_atonement_range'),
        ];
    }

    /**
     * Resolve an optional Gem range cell, requiring the exact CRUD nonnegative range syntax
     * when populated: two nonnegative numeric values separated by exactly one hyphen. A
     * zero-only numeric/string scalar (for example 0, "0", or "0.00") or a zero-only range
     * (for example "0-0") normalizes to null because it represents no effect, not a range to
     * roll.
     *
     * @param  mixed  $value  Raw spreadsheet range cell value.
     * @param  int  $rowNumber  One-based workbook row number, for error context.
     * @param  string  $column  Spreadsheet column name.
     * @return string|null Validated range string, or null when absent/zero-only.
     */
    private function resolveRange(mixed $value, int $rowNumber, string $column): ?string
    {
        if ($value === 0 || $value === 0.0) {
            return null;
        }

        $value = $this->resolveNullableString($value, $rowNumber, $column);

        if (is_null($value)) {
            return null;
        }

        if ($this->isZeroOnlyScalar($value)) {
            return null;
        }

        if (preg_match('/^\d+(?:\.\d+)?-\d+(?:\.\d+)?$/', $value) !== 1) {
            throw new RuntimeException('Row '.$rowNumber.': "'.$column.'" must contain exactly two nonnegative numeric values separated by a hyphen.');
        }

        [$firstValue, $secondValue] = explode('-', $value, 2);

        if ($this->isZeroOnlyScalar($firstValue) && $this->isZeroOnlyScalar($secondValue)) {
            return null;
        }

        return $value;
    }

    /**
     * Determine whether a scalar string represents only zero, such as "0", "0.0", or "0.00",
     * with no other digits.
     *
     * @param  string  $value  Scalar string to check.
     * @return bool Whether the value is a zero-only representation.
     */
    private function isZeroOnlyScalar(string $value): bool
    {
        return preg_match('/^0+(?:\.0+)?$/', $value) === 1;
    }

    /**
     * Resolve a required text cell, optionally treating a blank cell as the end of the workbook.
     *
     * @param  mixed  $value  Raw spreadsheet cell value.
     * @param  int  $rowNumber  One-based workbook row number, for error context.
     * @param  string  $column  Spreadsheet column name.
     * @param  bool  $allowEndOfWorkbook  Whether a blank cell should return null instead of failing.
     * @return string|null Trimmed required string, or null when the workbook has no more data.
     */
    private function resolveRequiredString(mixed $value, int $rowNumber, string $column, bool $allowEndOfWorkbook = false): ?string
    {
        if (is_null($value)) {
            if ($allowEndOfWorkbook) {
                return null;
            }

            throw new RuntimeException('Row '.$rowNumber.': "'.$column.'" is required.');
        }

        if (! is_string($value)) {
            throw new RuntimeException('Row '.$rowNumber.': "'.$column.'" must be text.');
        }

        $value = trim($value);

        if ($value === '') {
            if ($allowEndOfWorkbook) {
                return null;
            }

            throw new RuntimeException('Row '.$rowNumber.': "'.$column.'" is required.');
        }

        return $value;
    }

    /**
     * Resolve an optional text cell.
     *
     * @param  mixed  $value  Raw spreadsheet cell value.
     * @param  int  $rowNumber  One-based workbook row number, for error context.
     * @param  string  $column  Spreadsheet column name.
     * @return string|null Trimmed optional string, or null when absent.
     */
    private function resolveNullableString(mixed $value, int $rowNumber, string $column): ?string
    {
        if (is_null($value) || $value === '') {
            return null;
        }

        if (! is_string($value)) {
            throw new RuntimeException('Row '.$rowNumber.': "'.$column.'" must be text.');
        }

        return $value;
    }

    /**
     * Resolve a comma-separated crafting Skill name list into Skill ids.
     *
     * @param  mixed  $craftingSkillNames  Comma-separated crafting Skill names.
     * @param  int  $rowNumber  One-based workbook row number, for error context.
     * @return array<int, int> Resolved crafting Skill ids.
     */
    private function resolveCraftingSkillIds(mixed $craftingSkillNames, int $rowNumber): array
    {
        $craftingSkillNames = $this->resolveNullableString($craftingSkillNames, $rowNumber, 'crafting_skill_names');

        if (is_null($craftingSkillNames)) {
            return [];
        }

        $names = array_filter(array_map('trim', explode(',', $craftingSkillNames)));
        $ids = [];

        foreach ($names as $name) {
            $gameSkill = GameSkill::where('name', $name)->where('can_train', false)->first();

            if (is_null($gameSkill)) {
                throw new RuntimeException('Row '.$rowNumber.': unknown or trainable crafting Skill "'.$name.'".');
            }

            $ids[] = $gameSkill->id;
        }

        return $ids;
    }

    /**
     * Resolve a monster atonement Gem type label into its integer value.
     *
     * @param  mixed  $atonementLabel  Monster atonement Gem type label.
     * @param  int  $rowNumber  One-based workbook row number, for error context.
     * @return int|null Resolved Gem type value, or null when no atonement was given.
     */
    private function resolveAtonement(mixed $atonementLabel, int $rowNumber): ?int
    {
        $atonementLabel = $this->resolveNullableString($atonementLabel, $rowNumber, 'monster_atonement');

        if (is_null($atonementLabel)) {
            return null;
        }

        $atonementValue = array_search($atonementLabel, GemTypeValue::getNames(), true);

        if ($atonementValue === false) {
            throw new RuntimeException('Row '.$rowNumber.': unknown monster atonement "'.$atonementLabel.'".');
        }

        return $atonementValue;
    }
}
