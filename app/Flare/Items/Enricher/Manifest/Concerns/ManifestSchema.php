<?php

declare(strict_types=1);

namespace App\Flare\Items\Enricher\Manifest\Concerns;

/**
 * Contract for describing how to extract and compare enriched item data.
 *
 * A ManifestSchema encapsulates:
 * - Which mutated properties are eligible for comparison (includes/excludes).
 * - How to map flat PHP properties to dot-path keys for grouping (map()).
 * - How to determine logical type and comparison strategy (typeFor()/compareFor()).
 * - What collections (lists) exist and how to diff them (collections()).
 *
 * The ManifestBuilder will:
 * - Snapshot $item before/after enrichment,
 * - Use this schema to select/mapping properties,
 * - Attach `enriched = ['data' => array, 'manifest' => array]` as a relation on the item.
 */
interface ManifestSchema
{
    /**
     * Return a list of PCRE regex patterns that a mutated property name must match
     * to be considered for inclusion in the manifest.
     *
     * @return array<int, string> PCRE regex strings (e.g. '/^total_.+$/')
     *
     * @example
     *  return [
     *      '/^total_.+$/',
     *      '/^base_.+_mod$/',
     *  ];
     */
    public function includes(): array;

    /**
     * Return a list of PCRE regex patterns that, if matched, will exclude a mutated
     * property from the manifest (evaluated after includes()).
     *
     * @return array<int, string> PCRE regex strings (e.g. '/_id$/')
     *
     * @example
     *  return [
     *      '/^id$/',
     *      '/_id$/',
     *  ];
     */
    public function excludes(): array;

    /**
     * Map a flat property name into a dot-path used in the manifest and data bag.
     * Return null to skip this property.
     *
     * @param  string $prop The concrete property name on the item (e.g. "total_damage").
     * @return string|null  Dot-path (e.g. "totals.damage") or null to exclude.
     *
     * @example
     *  if (preg_match('/^total_(damage|defence|healing)$/', $prop, $m)) {
     *      return "totals.$m[1]";
     *  }
     *  return $prop; // fallback: keep as-is
     */
    public function map(string $prop): ?string;

    /**
     * Determine the logical type for a property value, used to select a default
     * comparison strategy if compareFor() returns null.
     *
     * Return one of:
     *  - 'number'  for ints/floats
     *  - 'boolean' for bools
     *  - 'string'  for strings
     *  - null      to let the builder infer or skip unknowns
     *
     * @param  string $prop  The concrete property name (pre-mapping).
     * @param  mixed  $value The PHP value taken from the item.
     * @return 'number'|'boolean'|'string'|null
     *
     * @example
     *  return match (true) {
     *      is_int($value), is_float($value) => 'number',
     *      is_bool($value)                  => 'boolean',
     *      is_string($value)                => 'string',
     *      default                          => null,
     *  };
     */
    public function typeFor(string $prop, mixed $value): ?string;

    /**
     * Select the comparison strategy for the mapped dot-path.
     * Return one of:
     *  - 'delta'     (numeric difference: toEquip - equipped)
     *  - 'flag-diff' (boolean change)
     *  - 'noop'      (no comparison; informational only)
     *  - null        (builder will pick a default based on type)
     *
     * @param  string $path Dot-path (post-mapping), e.g. "totals.damage".
     * @param  string $type The logical type decided by typeFor() or the builder.
     * @return 'delta'|'flag-diff'|'noop'|null
     *
     * @example
     *  return $type === 'number' ? 'delta' : 'noop';
     */
    public function compareFor(string $path, string $type): ?string;

    /**
     * Describe structured collections (lists) to compare by a logical key.
     *
     * Each entry must be an associative array:
     *  [
     *    'path'   => string Dot-path for the collection in the data bag (e.g. 'skill_summary'),
     *    'prop'   => string Property name on the item where the list lives (e.g. 'skill_summary'),
     *    'key'    => string Field name inside each row used to align/diff (e.g. 'skill_name'),
     *    'fields' => array<string, 'delta'|'flag-diff'|'noop'> Per-field compare strategies.
     *  ]
     *
     * @return array<int, array{
     *     path: string,
     *     prop: string,
     *     key: string,
     *     fields: array<string, string>
     * }>
     *
     * @example
     *  return [[
     *      'path'   => 'skill_summary',
     *      'prop'   => 'skill_summary',
     *      'key'    => 'skill_name',
     *      'fields' => [
     *          'skill_training_bonus' => 'delta',
     *          'skill_bonus'          => 'delta',
     *      ],
     *  ]];
     */
    public function collections(): array;
}
