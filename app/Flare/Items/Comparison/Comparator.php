<?php

namespace App\Flare\Items\Comparison;

use App\Flare\Items\Enricher\Manifest\Concerns\ManifestSchema;
use App\Flare\Models\Item;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

/**
 * Compare two *already-enriched* Item models using an injected ManifestSchema.
 *
 * The schema controls:
 *  - which attributes to include/exclude
 *  - how to map flat attributes to dot-paths
 *  - what comparison strategy to use per field ('delta'|'flag-diff'|'noop')
 *  - how to diff collections (e.g., skill_summary) by a logical key
 *
 * # Example
 * $comparator = app(\App\Flare\Items\Comparison\Comparator::class);
 * $result = $comparator->compare($enrichedToEquip, $enrichedEquipped);
 *
 * $result['comparison']['adjustments'] contains flat "*_adjustment" keys:
 *  - total_damage_adjustment
 *  - base_damage_mod_adjustment
 *  - devouring_light_adjustment
 *  - stackable_adjustment, non_stacking_adjustment, irresistible_adjustment
 *  - skill_summary: array of rows each with key + "*_adjustment" fields
 */
class Comparator
{
    public function __construct(private readonly ManifestSchema $schema) {}

    /**
     * Compare two enriched items and return flattened adjustments plus skill-summary deltas.
     *
     * @param  Item $itemToEquip
     * @param  Item $itemEquipped
     * @return array{
     *   item_to_equip: Item,
     *   item_equipped: Item,
     *   comparison: array{
     *     name: ?string,
     *     description: ?string,
     *     adjustments: array<string, mixed|array<int, array<string, mixed>>>
     *   }
     * }
     */
    public function compare(Item $itemToEquip, Item $itemEquipped): array
    {
        $leftMapped   = $this->mapItemToPaths($itemToEquip);
        $rightMapped  = $this->mapItemToPaths($itemEquipped);

        $mergedFieldMeta   = $leftMapped['fields'] + $rightMapped['fields'];
        $mergedCollections = $leftMapped['collections'] + $rightMapped['collections'];

        $fieldAdjustments = $this->buildFieldAdjustments(
            $mergedFieldMeta,
            $leftMapped['data'],
            $rightMapped['data']
        );

        $skillSummaryAdjustments = $this->buildCollectionAdjustments(
            $mergedCollections,
            $leftMapped['data'],
            $rightMapped['data']
        );

        $adjustments = $fieldAdjustments;
        $adjustments['skill_summary'] = $skillSummaryAdjustments;

        return [
            'item_to_equip' => $itemToEquip,
            'item_equipped' => $itemEquipped,
            'comparison'    => [
                'name'        => $itemToEquip->name ?? null,
                'description' => $itemToEquip->description ?? null,
                'adjustments' => $adjustments,
            ],
        ];
    }

    /**
     * Build a mapped data bag for an enriched item using the schema.
     *
     * @param  Item $item
     * @return array{
     *   data: array<string, mixed>,
     *   fields: array<string, array{type:?string, compare:?string}>,
     *   collections: array<string, array{key:string, fields:array<string,string>}>
     * }
     */
    private function mapItemToPaths(Item $item): array
    {
        $mappedData  = [];
        $fieldMeta   = [];

        $attributes      = $item->getAttributes();
        $includePatterns = $this->schema->includes();
        $excludePatterns = $this->schema->excludes();

        foreach (array_keys($attributes) as $propertyName) {
            $isIncluded = $this->matchesAny($propertyName, $includePatterns);
            if ($isIncluded === false) {
                continue;
            }

            $isExcluded = $this->matchesAny($propertyName, $excludePatterns);
            if ($isExcluded === true) {
                continue;
            }

            $mappedPath = $this->schema->map($propertyName);
            if ($mappedPath === null) {
                continue;
            }

            $value = $attributes[$propertyName] ?? null;

            $this->setDot($mappedData, $mappedPath, $value);

            $logicalType = $this->schema->typeFor($propertyName, $value);

            $strategy = $this->schema->compareFor($mappedPath, (string) $logicalType);
            if ($strategy === null || $logicalType === null) {
                $strategy = $this->defaultStrategyFor($logicalType);
            }

            $fieldMeta[$mappedPath] = [
                'type'    => $logicalType,
                'compare' => $strategy,
            ];
        }

        $collectionsMeta       = [];
        $collectionDescriptors = $this->schema->collections();

        foreach ($collectionDescriptors as $descriptor) {
            $collectionPath = $descriptor['path'] ?? null;
            if ($collectionPath === null) {
                continue;
            }

            $collectionProp = $descriptor['prop'] ?? $collectionPath;

            // IMPORTANT: do NOT coerce to [] here. Let bad types flow through so
            // indexRowsByKey() can exercise its early-return guard.
            $rows = $item->{$collectionProp} ?? null;
            $mappedData[$collectionPath] = $rows;

            $keyName   = $descriptor['key']    ?? 'id';
            $fieldsMap = $descriptor['fields'] ?? [];

            $collectionsMeta[$collectionPath] = [
                'key'    => $keyName,
                'fields' => $fieldsMap,
            ];
        }

        return [
            'data'        => $mappedData,
            'fields'      => $fieldMeta,
            'collections' => $collectionsMeta,
        ];
    }

    /**
     * Build adjustments for scalar fields.
     *
     * @param  array<string, array{type:?string, compare:?string}> $fieldMeta
     * @param  array<string, mixed> $leftData
     * @param  array<string, mixed> $rightData
     * @return array<string, mixed>
     */
    private function buildFieldAdjustments(array $fieldMeta, array $leftData, array $rightData): array
    {
        return collect($fieldMeta)
            ->mapWithKeys(function (array $rules, string $path) use ($leftData, $rightData): array {
                $strategy   = $rules['compare']; // non-null due to mapItemToPaths()
                $leftValue  = Arr::get($leftData, $path);
                $rightValue = Arr::get($rightData, $path);

                $adjustedValue = $this->compute($strategy, $leftValue, $rightValue);
                $adjustedKey   = $this->adjustmentKeyFromPath($path);

                return [$adjustedKey => $adjustedValue];
            })
            ->all();
    }

    /**
     * Build adjustments for collection rows (e.g., skill_summary).
     *
     * @param  array<string, array{key:string, fields:array<string,string>}> $collectionsMeta
     * @param  array<string, mixed> $leftData
     * @param  array<string, mixed> $rightData
     * @return array<int, array<string, mixed>>
     */
    private function buildCollectionAdjustments(array $collectionsMeta, array $leftData, array $rightData): array
    {
        if (empty($collectionsMeta)) {
            return [];
        }

        return collect($collectionsMeta)
            ->flatMap(function (array $rules, string $path) use ($leftData, $rightData): Collection {
                $keyName         = $rules['key'] ?? 'id';
                $fieldStrategies = (array) Arr::get($rules, 'fields', []);

                $leftRowsIndexed  = $this->indexRowsByKey(Arr::get($leftData, $path), $keyName);
                $rightRowsIndexed = $this->indexRowsByKey(Arr::get($rightData, $path), $keyName);

                $allRowKeys = $leftRowsIndexed->keys()
                    ->merge($rightRowsIndexed->keys())
                    ->unique()
                    ->sort()
                    ->values();

                return $allRowKeys->map(function (string $rowKey) use ($leftRowsIndexed, $rightRowsIndexed, $keyName, $fieldStrategies): array {
                    $leftRow  = $leftRowsIndexed->get($rowKey, []);
                    $rightRow = $rightRowsIndexed->get($rowKey, []);

                    return $this->buildCollectionRow($rowKey, $leftRow, $rightRow, $keyName, $fieldStrategies);
                });
            })
            ->values()
            ->all();
    }

    /**
     * Build a single collection row with key + per-field "*_adjustment".
     *
     * @param  string               $rowKey
     * @param  array<string,mixed>  $leftRow
     * @param  array<string,mixed>  $rightRow
     * @param  string               $keyName
     * @param  array<string,string> $fieldStrategies
     * @return array<string,mixed>
     */
    private function buildCollectionRow(
        string $rowKey,
        array $leftRow,
        array $rightRow,
        string $keyName,
        array $fieldStrategies
    ): array {
        $row = [$keyName => $rowKey];

        collect($fieldStrategies)->each(function (string $strategy, string $field) use (&$row, $leftRow, $rightRow): void {
            $leftValue  = array_key_exists($field, $leftRow) ? $leftRow[$field] : null;
            $rightValue = array_key_exists($field, $rightRow) ? $rightRow[$field] : null;

            $row[$field . '_adjustment'] = $this->compute($strategy, $leftValue, $rightValue);
        });

        return $row;
    }

    /**
     * Turn a schema path into a flattened "*_adjustment" key.
     *
     * - totals.{leaf}      → total_{leaf}_adjustment
     * - mods.base.{leaf}   → base_{leaf}_adjustment
     * - devouring.{leaf}   → devouring_{leaf}_adjustment
     * - affix_damage.{x}   → {x}_adjustment
     * - fallback a.b.c     → c_adjustment
     */
    private function adjustmentKeyFromPath(string $path): string
    {
        if (preg_match('/^totals\.(\w+)$/', $path, $match) === 1) {
            return 'total_' . $match[1] . '_adjustment';
        }

        if (preg_match('/^mods\.base\.(\w+)$/', $path, $match) === 1) {
            return 'base_' . $match[1] . '_adjustment';
        }

        if (preg_match('/^devouring\.(\w+)$/', $path, $match) === 1) {
            return 'devouring_' . $match[1] . '_adjustment';
        }

        if (preg_match('/^affix_damage\.(\w+)$/', $path, $match) === 1) {
            return $match[1] . '_adjustment';
        }

        $segments = explode('.', $path);
        $leaf = end($segments);

        return $leaf . '_adjustment';
    }

    /**
     * Default comparison strategy by logical type.
     *
     * @param  string|null $logicalType
     * @return string 'delta'|'flag-diff'|'noop'
     */
    private function defaultStrategyFor(?string $logicalType): string
    {
        if ($logicalType === 'number') {
            return 'delta';
        }

        if ($logicalType === 'boolean') {
            return 'flag-diff';
        }

        return 'noop';
    }

    /**
     * Compute an adjustment using a named strategy.
     *
     * @param  string $strategy 'delta'|'flag-diff'|'noop'
     * @param  mixed  $leftValue
     * @param  mixed  $rightValue
     * @return mixed
     */
    private function compute(string $strategy, mixed $leftValue, mixed $rightValue): mixed
    {
        if ($strategy === 'delta') {
            return $this->computeDelta($leftValue, $rightValue);
        }

        if ($strategy === 'flag-diff') {
            return $this->computeFlagDiff($leftValue, $rightValue);
        }

        return null; // 'noop'
    }

    /**
     * Numeric difference: (float)$leftValue - (float)$rightValue, nulls treated as 0.
     */
    private function computeDelta(mixed $leftValue, mixed $rightValue): float
    {
        $normalizedLeft  = $leftValue  ?? 0;
        $normalizedRight = $rightValue ?? 0;

        $leftFloat  = (float) $normalizedLeft;
        $rightFloat = (float) $normalizedRight;

        return $leftFloat - $rightFloat;
    }

    /**
     * Boolean inequality after (bool) cast.
     */
    private function computeFlagDiff(mixed $leftValue, mixed $rightValue): bool
    {
        $leftBool  = (bool) $leftValue;
        $rightBool = (bool) $rightValue;

        return $leftBool !== $rightBool;
    }

    /**
     * Does the given value match any of the PCRE patterns?
     */
    private function matchesAny(string $value, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            $matched = @preg_match($pattern, $value);
            if ($matched === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Set a value at a dot-path inside an array (creating arrays as needed).
     */
    private function setDot(array &$target, string $path, mixed $value): void
    {
        $segments = explode('.', $path);
        $reference =& $target;

        foreach ($segments as $segment) {
            if (!array_key_exists($segment, $reference) || !is_array($reference[$segment])) {
                $reference[$segment] = [];
            }

            $reference =& $reference[$segment];
        }

        $reference = $value;
    }

    /**
     * Convert a list of rows into a key-indexed collection by $keyName.
     *
     * @return Collection<string, array<string,mixed>>
     */
    private function indexRowsByKey(mixed $rows, string $keyName): Collection
    {
        $indexed = collect();

        if (!is_array($rows)) {
            return $indexed;
        }

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            if (!array_key_exists($keyName, $row)) {
                continue;
            }

            $rowKey = (string) $row[$keyName];
            $indexed->put($rowKey, $row);
        }

        return $indexed;
    }
}
