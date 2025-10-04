<?php

namespace App\Flare\Items\Enricher\Manifest;

use App\Flare\Items\Enricher\Manifest\Concerns\ManifestSchema;

abstract class BaseManifest implements ManifestSchema
{
    /**
     * {@inheritdoc}
     *
     * By default, no properties are explicitly included. Override in subclasses
     * to provide one or more PCRE patterns (e.g., '/^total_.+$/').
     *
     * @return array<int, string>
     */
    public function includes(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     *
     * By default, no properties are explicitly excluded. Override in subclasses
     * to provide one or more PCRE patterns (e.g., '/_id$/').
     *
     * @return array<int, string>
     */
    public function excludes(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     *
     * Default behavior keeps the original flat property name. Override to group
     * properties into dot-paths (e.g., "total_damage" -> "totals.damage").
     *
     * @param  string  $prop  The concrete property name on the item.
     * @return string|null The mapped dot-path or null to skip this property.
     */
    public function map(string $prop): ?string
    {
        return $prop;
    }

    /**
     * {@inheritdoc}
     *
     * Infers a logical type from the PHP value to drive default comparison behavior.
     * Returns:
     *  - 'number'  for integers/floats
     *  - 'boolean' for booleans
     *  - 'string'  for strings
     *  - null      if the type cannot be determined
     *
     * @return 'number'|'boolean'|'string'|null
     */
    public function typeFor(string $prop, mixed $value): ?string
    {
        if (is_int($value)) {
            return 'number';
        }

        if (is_float($value)) {
            return 'number';
        }

        if (is_bool($value)) {
            return 'boolean';
        }

        if (is_string($value)) {
            return 'string';
        }

        return null;
    }

    /**
     * {@inheritdoc}
     *
     * Provides default comparison strategies based on the logical type:
     *  - 'number'  => 'delta'     (numeric difference)
     *  - 'boolean' => 'flag-diff' (boolean change)
     *  - otherwise => 'noop'      (informational only)
     *
     * Subclasses may override to specialize by path (e.g., per-field rules).
     *
     * @param  string  $path  Dot-path for the field (post-mapping).
     * @param  string  $type  Logical type returned by {@see typeFor()} or the builder.
     * @return 'delta'|'flag-diff'|'noop'|null
     */
    public function compareFor(string $path, string $type): ?string
    {
        if ($type === 'number') {
            return 'delta';
        }

        if ($type === 'boolean') {
            return 'flag-diff';
        }

        return 'noop';
    }

    /**
     * {@inheritdoc}
     *
     * By default, no structured collections are defined. Override to declare
     * collections the comparator should diff by key.
     *
     * Each entry should be:
     *  [
     *    'path'   => string,                            // dot-path in the data bag
     *    'prop'   => string,                            // property on the item
     *    'key'    => string,                            // join key inside each row
     *    'fields' => array<string, 'delta'|'flag-diff'|'noop'> // per-field strategies
     *  ]
     *
     * @return array<int, array{
     *     path: string,
     *     prop: string,
     *     key: string,
     *     fields: array<string, string>
     * }>
     */
    public function collections(): array
    {
        return [];
    }
}
