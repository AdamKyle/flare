<?php

namespace App\Flare\Items\Enricher\Manifest;

use App\Flare\Items\Enricher\Manifest\Concerns\ManifestSchema;

class EquippableManifest extends BaseManifest
{
    /**
     * List of PCRE regex patterns that an item property name must match
     * to be considered for inclusion in the manifest.
     *
     * @return array<int, string>
     */
    public function includes(): array
    {
        return [
            '/^total_.+$/',
            '/^base_.+_mod$/',
            '/^devouring_.+$/',
            '/^.*_chance$/',       // e.g. resurrection_chance, ambush_chance
            '/^.*_reduction$/',    // e.g. healing_reduction, affix_damage_reduction, counter_reduction
            '/^.*_evasion$/',      // e.g. spell_evasion  <-- added
            '/^total_.*_affix_damage$/',
            '/^(str|dur|dex|chr|int|agi|focus)_mod$/',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function excludes(): array
    {
        return ['/^id$/', '/_id$/'];
    }

    /**
     * Map a flat property name into a grouped dot-path for the manifest.
     * Return null to skip a property entirely.
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

        // Fallback: keep as-is -> becomes "{leaf}_adjustment"
        return $prop;
    }

    /**
     * Determine logical type for comparison defaults.
     *
     * @param string $prop
     * @param mixed $value
     * @return 'number'|'boolean'|'string'|null
     */
    public function typeFor(string $prop, mixed $value): ?string
    {
        // Treat numeric strings (e.g., "1.0000") as numbers so they compare with 'delta'
        if (is_string($value) && is_numeric($value)) {
            return 'number';
        }

        return match (true) {
            is_int($value), is_float($value) => 'number',
            is_bool($value)                  => 'boolean',
            is_string($value)                => 'string',
            default                          => null,
        };
    }

    /**
     * @param string $path
     * @param string $type
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
     * @return array<int, array{path:string, prop:string, key:string, fields:array<string,string>}>
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
