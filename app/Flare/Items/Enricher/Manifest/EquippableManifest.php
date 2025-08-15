<?php

namespace App\Flare\Items\Enricher\Manifest;

use App\Flare\Items\Enricher\Manifest\Concerns\ManifestSchema;

class EquippableManifest extends BaseManifest
{
    /**
     * List of PCRE regex patterns that an item property name must match
     * to be considered for inclusion in the manifest.
     *
     * Examples of included properties:
     *  - total_damage, total_defence, total_healing
     *  - base_damage_mod, base_healing_mod, base_ac_mod
     *  - devouring_light, devouring_darkness
     *  - *_chance, *_reduction
     *  - total_stackable_affix_damage, total_non_stacking_affix_damage, total_irresistible_affix_damage
     *
     * @return array<int, string> PCRE patterns
     */
    public function includes(): array
    {
        return [
            '/^total_.+$/',
            '/^base_.+_mod$/',
            '/^devouring_.+$/',
            '/^.*_chance$/',
            '/^.*_reduction$/',
            '/^total_.*_affix_damage$/',
            '/^(str|dur|dex|chr|int|agi|focus)_mod$/',
        ];
    }

    /**
     * List of PCRE regex patterns that, if matched by a property name,
     * will exclude that property from the manifest (evaluated after includes()).
     *
     * Typical exclusions:
     *  - Primary keys and foreign keys (id, *_id)
     *
     * @return array<int, string> PCRE patterns
     */
    public function excludes(): array
    {
        return ['/^id$/', '/_id$/'];
    }

    /**
     * Map a flat property name into a grouped dot-path for the manifest.
     * Return null to skip a property entirely.
     *
     * Known mappings:
     *  - total_{damage|defence|healing}     → totals.{stat}
     *  - base_{damage|healing|ac}_mod       → mods.base.{stat}_mod
     *  - devouring_{light|darkness}         → devouring.{type}
     *  - total_{stackable|non_stacking|irresistible}_affix_damage → affix_damage.{category}
     *
     * Fallback: returns the original property name (kept at top level).
     *
     * @param  string $prop The concrete property name on the item.
     * @return string|null  Dot-path for manifest grouping, or null to omit.
     */
    public function map(string $prop): ?string
    {
        if (preg_match('/^total_(damage|defence|healing)$/', $prop, $m)) {
            return "totals.$m[1]";
        }

        if (preg_match('/^base_(damage|healing|ac)_mod$/', $prop, $m)) {
            return "mods.base.{$m[1]}_mod";
        }

        if (preg_match('/^devouring_(light|darkness)$/', $prop, $m)) {
            return "devouring.$m[1]";
        }

        if (preg_match('/^total_(stackable|non_stacking|irresistible)_affix_damage$/', $prop, $m)) {
            return "affix_damage.$m[1]";
        }

        if (preg_match('/^(str|dur|dex|chr|int|agi|focus)_mod$/', $prop, $m)) {
            return "stats.{$m[1]}_mod";
        }

        // Fallback: keep as-is (appears at top level in data bag)
        return $prop;
    }

    /**
     * Determine the logical type for a given property value to guide the default
     * comparison strategy when {@see compareFor()} returns null.
     *
     * Returns one of:
     *  - 'number'  (for int/float)
     *  - 'boolean' (for bool)
     *  - 'string'  (for string)
     *  - null      (type unknown; let builder decide or skip)
     *
     * @param  string $prop  The original (flat) property name.
     * @param  mixed  $value The property value taken from the item.
     * @return 'number'|'boolean'|'string'|null
     */
    public function typeFor(string $prop, mixed $value): ?string
    {
        return match (true) {
            is_int($value), is_float($value) => 'number',
            is_bool($value)                  => 'boolean',
            is_string($value)                => 'string',
            default                          => null,
        };
    }

    /**
     * Provide the comparison strategy for a mapped field path, optionally
     * based on the logical type.
     *
     * Standard strategies:
     *  - 'delta'     (numeric difference: toEquip - equipped)
     *  - 'flag-diff' (boolean change)
     *  - 'noop'      (no comparison; informational)
     *
     * Returning null delegates to a default chosen by the builder.
     *
     * @param  string $path Dot-path (post-mapping), e.g., "totals.damage".
     * @param  string $type Result of {@see typeFor()} or builder inference.
     * @return 'delta'|'flag-diff'|'noop'|null
     */
    public function compareFor(string $path, string $type): ?string
    {
        return match ($type) {
            'number'  => 'delta',
            'boolean' => 'flag-diff',
            default   => 'noop',
        };
    }

    /**
     * Describe structured collections that should be compared by a logical key.
     *
     * Each entry:
     *  [
     *    'path'   => string,                            // dot-path to store in the data bag
     *    'prop'   => string,                            // property on the item holding the list
     *    'key'    => string,                            // join key inside each row (e.g., 'skill_name')
     *    'fields' => array<string, 'delta'|'flag-diff'|'noop'> // per-field strategies
     *  ]
     *
     * This schema exposes the "skill_summary" as a collection keyed by "skill_name",
     * with numeric deltas for "skill_training_bonus" and "skill_bonus".
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
        return [[
            'path'   => 'skill_summary',
            'prop'   => 'skill_summary',
            'key'    => 'skill_name',
            'fields' => [
                'skill_training_bonus' => 'delta',
                'skill_bonus'          => 'delta',
            ],
        ]];
    }
}
