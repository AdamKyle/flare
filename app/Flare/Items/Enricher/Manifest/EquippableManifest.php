<?php

namespace App\Flare\Items\Enricher\Manifest;

class EquippableManifest extends BaseManifest
{
    /**
     * Return the list of PCRE include patterns that determine which flat item attributes
     * are considered by the manifest and comparator.
     *
     * @return array<int, string>
     */
    public function includes(): array
    {
        return [
            '/^total_.+$/',
            '/^base_.+_mod$/',
            '/^devouring_.+$/',
            '/^.*_chance$/',
            '/^.*_reduction$/',
            '/^.*_evasion$/',
            '/^total_.*_affix_damage$/',
            '/^(str|dur|dex|chr|int|agi|focus)_mod$/',
            '/^holy_stack_devouring_darkness$/',
            '/^holy_stack_stat_bonus$/',
            '/^holy_stacks_applied$/',
        ];
    }

    /**
     * Return the list of PCRE exclude patterns for flat attributes that must be ignored.
     *
     * @return array<int, string>
     */
    public function excludes(): array
    {
        return ['/^id$/', '/_id$/'];
    }

    /**
     * Map a flat attribute name to a grouped dot-path used by the comparator.
     * Return null to skip an attribute entirely; falling back to the original name
     * yields a "{leaf}_adjustment" key.
     *
     * @param string $prop
     * @return string|null
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

        return $prop;
    }

    /**
     * Determine the logical type for a given attribute to select a default comparison strategy.
     * Numeric strings are treated as numbers for delta comparisons.
     *
     * @param string $prop
     * @param mixed $value
     * @return string|null
     */
    public function typeFor(string $prop, mixed $value): ?string
    {
        if (is_string($value) && is_numeric($value)) {
            return 'number';
        }

        return match (true) {
            is_int($value), is_float($value) => 'number',
            is_bool($value) => 'boolean',
            is_string($value) => 'string',
            default => null,
        };
    }

    /**
     * Select the comparison strategy for a mapped path and logical type.
     *
     * @param string $path
     * @param string $type
     * @return string|null
     */
    public function compareFor(string $path, string $type): ?string
    {
        return match ($type) {
            'number' => 'delta',
            'boolean' => 'flag-diff',
            default => 'noop',
        };
    }

    /**
     * Describe collections (arrays of rows) that should be diffed by key with field-level strategies.
     *
     * @return array<int, array{path:string, prop:string, key:string, fields:array<string,string>}>
     */
    public function collections(): array
    {
        return [[
            'path' => 'skill_summary',
            'prop' => 'skill_summary',
            'key' => 'skill_name',
            'fields' => [
                'skill_training_bonus' => 'delta',
                'skill_bonus' => 'delta',
            ],
        ]];
    }
}
