<?php

namespace App\Admin\Monsters\Imports\Sheets;

use App\Admin\Monsters\Requests\StoreMonsterRequest;
use App\Admin\Monsters\Services\MonsterService;
use App\Flare\Models\GameMap;
use App\Flare\Models\Item;
use App\Flare\Models\Monster;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;

class MonstersSheet implements ToCollection
{
    private bool $successful = false;

    private ?string $validationError = null;

    public function __construct(
        private readonly MonsterService $monsterService = new MonsterService,
    ) {}

    /**
     * Import Monster rows from the uploaded spreadsheet and persist them.
     */
    public function collection(Collection $rows): void
    {
        $rawRows = $this->extractMeaningfulRows($rows);
        $resolvedRows = [];

        foreach ($rawRows as $rawRow) {
            $monsterData = $this->resolveRow($rawRow);

            if (is_null($monsterData)) {
                $this->fail("Unable to import Monster \"{$rawRow['name']}\": one or more referenced records or values could not be resolved.");

                return;
            }

            $resolvedRows[] = $monsterData;
        }

        foreach ($resolvedRows as $monsterData) {
            $existingMonster = Monster::where('name', $monsterData['name'])->first();

            if (! is_null($existingMonster)) {
                $existingMonster->update($monsterData);

                continue;
            }

            Monster::create($monsterData);
        }

        $this->successful = true;
    }

    /**
     * Determine whether the import completed and wrote every workbook row.
     */
    public function wasSuccessful(): bool
    {
        return $this->successful;
    }

    /**
     * Resolve the human-facing validation error for a failed import, when one occurred.
     */
    public function validationError(): ?string
    {
        return $this->validationError;
    }

    /**
     * Record why the import failed. Zero rows are written once this is called.
     */
    private function fail(string $message): void
    {
        $this->validationError = $message;
    }

    /**
     * Extract every meaningful row (up to the first blank Monster name) from the uploaded workbook.
     */
    private function extractMeaningfulRows(Collection $rows): array
    {
        $headers = $rows[0]->toArray();
        $rawRows = [];

        foreach ($rows as $index => $row) {
            if ($index === 0) {
                continue;
            }

            $rawRow = array_combine($headers, $row->toArray());

            if (is_null($rawRow['name'] ?? null) || $rawRow['name'] === '') {
                break;
            }

            $rawRows[] = $rawRow;
        }

        return $rawRows;
    }

    /**
     * Resolve a single raw Monster row into normalized, validated Monster attributes.
     */
    private function resolveRow(array $rawRow): ?array
    {
        $data = $this->normalizeBlanks($rawRow);
        $data = $this->normalizeBooleans($data);

        $data['game_map_id'] = $this->resolveByName(GameMap::class, 'name', $data['game_map_id'] ?? null);
        $data['quest_item_id'] = $this->resolveByName(Item::class, 'name', $data['quest_item_id'] ?? null);

        unset($data['id']);

        $data = $this->monsterService->normalize($data);

        if (! $this->hasValidManagedFields($data)) {
            return null;
        }

        if ($data['is_raid_monster'] && $data['is_raid_boss']) {
            return null;
        }

        return $data;
    }

    /**
     * Validate a resolved Monster row against the complete current managed field contract.
     */
    private function hasValidManagedFields(array $data): bool
    {
        $validator = Validator::make($data, (new StoreMonsterRequest)->rules());

        return ! $validator->fails();
    }

    /**
     * Resolve a related record's id by its display-name column.
     */
    private function resolveByName(string $modelClass, string $nameColumn, ?string $value): int|false|null
    {
        if (is_null($value)) {
            return null;
        }

        $record = $modelClass::where($nameColumn, $value)->first();

        if (is_null($record)) {
            return false;
        }

        return $record->id;
    }

    /**
     * Normalize blank Monster workbook cells to null.
     */
    private function normalizeBlanks(array $rawRow): array
    {
        foreach ($rawRow as $key => $value) {
            if ($key === 'name') {
                continue;
            }

            if (is_string($value) && trim($value) === '') {
                $rawRow[$key] = null;
            }
        }

        return $rawRow;
    }

    /**
     * Normalize Monster workbook boolean columns to booleans.
     */
    private function normalizeBooleans(array $rawRow): array
    {
        foreach (['can_cast', 'is_celestial_entity', 'is_raid_monster', 'is_raid_boss'] as $key) {
            $rawRow[$key] = filter_var($rawRow[$key] ?? false, FILTER_VALIDATE_BOOLEAN);
        }

        return $rawRow;
    }
}
