<?php

namespace App\Admin\Items\Imports\Sheets;

use App\Admin\Items\Services\ItemService;
use App\Flare\Models\GameClass;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Item;
use App\Flare\Models\ItemSkill;
use App\Flare\Models\Location;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class ItemsSheet implements ToCollection
{
    public function __construct(
        private readonly ItemService $itemService,
    ) {}

    /**
     * Import catalog Item rows from the uploaded spreadsheet and persist them.
     */
    public function collection(Collection $rows): void
    {
        $validatedRows = $this->normalizeAndValidateRows($rows);

        if (is_null($validatedRows)) {
            return;
        }

        foreach ($validatedRows as $itemData) {
            $existingItem = Item::where('name', $itemData['name'])
                ->whereNull('item_suffix_id')
                ->whereNull('item_prefix_id')
                ->whereNull('parent_id')
                ->first();

            if (! is_null($existingItem)) {
                $existingItem->update($itemData);

                continue;
            }

            Item::create($itemData);
        }
    }

    /**
     * Normalize and validate every meaningful Item row before any row is written.
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

            $itemData = $this->normalizeRow($rawRow);

            if (is_null($itemData)) {
                return null;
            }

            $validatedRows[] = $itemData;
        }

        return $validatedRows;
    }

    /**
     * Normalize a single raw Item row, resolving related records by name.
     */
    private function normalizeRow(array $rawRow): ?array
    {
        if (is_null($rawRow['type'] ?? null)) {
            return null;
        }

        $rawRow = $this->applyBooleanDefaults($rawRow);
        $resolvedClassId = $this->resolveUnlocksClassId($rawRow);

        if ($resolvedClassId === false) {
            return null;
        }

        $cleanData = $this->resolveRowRelationships($rawRow);

        if (is_null($cleanData)) {
            return null;
        }

        if (! is_null($resolvedClassId)) {
            $cleanData['unlocks_class_id'] = $resolvedClassId;
        }

        return $this->itemService->normalize($cleanData);
    }

    /**
     * Resolve the unlocked Class referenced by name, validating its paired Skill when given.
     */
    private function resolveUnlocksClassId(array $rawRow): int|null|false
    {
        $unlocksClassName = $rawRow['unlocks_class_id'] ?? null;

        if (is_null($unlocksClassName)) {
            return null;
        }

        $skillName = $rawRow['skill_name'] ?? null;
        $gameClass = GameClass::where('name', $unlocksClassName)->first();

        if (is_null($gameClass)) {
            return false;
        }

        if (! is_null($skillName) && is_null(GameSkill::where('name', $skillName)->first())) {
            return false;
        }

        return $gameClass->id;
    }

    /**
     * Default the boolean columns the current Item catalog form always sends when a workbook omits them.
     */
    private function applyBooleanDefaults(array $rawRow): array
    {
        $booleanKeys = [
            'can_drop', 'market_sellable', 'usable', 'damages_kingdoms', 'stat_increase',
            'can_craft', 'craft_only', 'can_resurrect', 'ignores_caps',
        ];

        foreach ($booleanKeys as $key) {
            if (! isset($rawRow[$key])) {
                $rawRow[$key] = false;
            }
        }

        if (! isset($rawRow['can_use_on_other_items'])) {
            $rawRow['can_use_on_other_items'] = false;
            $rawRow['holy_level'] = null;
        }

        return $rawRow;
    }

    /**
     * Resolve the Item Skill and drop Location relationships referenced by name.
     */
    private function resolveRowRelationships(array $rawRow): ?array
    {
        $cleanData = [];

        foreach ($rawRow as $key => $value) {
            if (is_null($value)) {
                continue;
            }

            if ($key === 'drop_location_id') {
                $location = Location::where('name', $value)->first();

                if (is_null($location)) {
                    return null;
                }

                $value = $location->id;
            }

            if ($key === 'item_skill_id') {
                $itemSkill = ItemSkill::where('name', $value)->first();

                if (is_null($itemSkill)) {
                    return null;
                }

                $value = $itemSkill->id;
            }

            $cleanData[$key] = $value;
        }

        return $cleanData;
    }
}
