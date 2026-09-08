<?php

namespace App\Game\Monsters\Services;

use App\Flare\Models\Monster;
use App\Game\Monsters\Values\MonsterCacheKey;
use Illuminate\Support\Facades\Cache;

/**
 * Reads the cached, already Gem-transformed Monster payloads and exposes the
 * deduplicated set of Gem effect contexts a persisted Monster currently
 * appears in, for factual detail/report display only. No Gem math is
 * recalculated here.
 */
class MonsterGemEffectContextService
{
    /**
     * The cached effective field, its factual label, its base Monster attribute, and its display type.
     *
     * @var array<string, array{label: string, base: string, type: string}>
     */
    private const FIELD_MAP = [
        'criticality' => ['label' => 'Criticality', 'base' => 'criticality', 'type' => 'percent'],
        'spell_damage' => ['label' => 'Max Spell Damage', 'base' => 'max_spell_damage', 'type' => 'number'],
        'max_affix_damage' => ['label' => 'Max Affix Damage', 'base' => 'max_affix_damage', 'type' => 'number'],
        'str' => ['label' => 'Strength', 'base' => 'str', 'type' => 'number'],
        'dur' => ['label' => 'Durability', 'base' => 'dur', 'type' => 'number'],
        'dex' => ['label' => 'Dexterity', 'base' => 'dex', 'type' => 'number'],
        'chr' => ['label' => 'Charisma', 'base' => 'chr', 'type' => 'number'],
        'int' => ['label' => 'Intelligence', 'base' => 'int', 'type' => 'number'],
        'agi' => ['label' => 'Agility', 'base' => 'agi', 'type' => 'number'],
        'focus' => ['label' => 'Focus', 'base' => 'focus', 'type' => 'number'],
        'ac' => ['label' => 'AC', 'base' => 'ac', 'type' => 'number'],
        'health_range' => ['label' => 'Health Range', 'base' => 'health_range', 'type' => 'range'],
        'attack_range' => ['label' => 'Attack Range', 'base' => 'attack_range', 'type' => 'range'],
        'accuracy' => ['label' => 'Accuracy', 'base' => 'accuracy', 'type' => 'percent'],
        'dodge' => ['label' => 'Dodge', 'base' => 'dodge', 'type' => 'percent'],
        'casting_accuracy' => ['label' => 'Casting Accuracy', 'base' => 'casting_accuracy', 'type' => 'percent'],
        'spell_evasion' => ['label' => 'Spell Evasion', 'base' => 'spell_evasion', 'type' => 'percent'],
        'affix_resistance' => ['label' => 'Affix Resistance', 'base' => 'affix_resistance', 'type' => 'percent'],
        'max_healing' => ['label' => 'Healing', 'base' => 'healing_percentage', 'type' => 'percent'],
        'entrancing_chance' => ['label' => 'Entrancing Chance', 'base' => 'entrancing_chance', 'type' => 'percent'],
        'devouring_light_chance' => ['label' => 'Devouring Light Chance', 'base' => 'devouring_light_chance', 'type' => 'percent'],
        'devouring_darkness_chance' => ['label' => 'Devouring Darkness Chance', 'base' => 'devouring_darkness_chance', 'type' => 'percent'],
        'ambush_chance' => ['label' => 'Ambush Chance', 'base' => 'ambush_chance', 'type' => 'percent'],
        'ambush_resistance_chance' => ['label' => 'Ambush Resistance', 'base' => 'ambush_resistance', 'type' => 'percent'],
        'counter_chance' => ['label' => 'Counter Chance', 'base' => 'counter_chance', 'type' => 'percent'],
        'counter_resistance_chance' => ['label' => 'Counter Resistance', 'base' => 'counter_resistance', 'type' => 'percent'],
        'quest_item_drop_chance' => ['label' => 'Quest Item Drop Chance', 'base' => 'quest_item_drop_chance', 'type' => 'percent'],
        'xp' => ['label' => 'XP', 'base' => 'xp', 'type' => 'number'],
        'gold' => ['label' => 'Gold', 'base' => 'gold', 'type' => 'number'],
        'fire_atonement' => ['label' => 'Fire Atonement', 'base' => 'fire_atonement', 'type' => 'percent'],
        'ice_atonement' => ['label' => 'Ice Atonement', 'base' => 'ice_atonement', 'type' => 'percent'],
        'water_atonement' => ['label' => 'Water Atonement', 'base' => 'water_atonement', 'type' => 'percent'],
    ];

    /**
     * Sort order for each closed Gem effect context type.
     *
     * @var array<string, int>
     */
    private const CONTEXT_TYPE_ORDER = [
        'map' => 0,
        'location' => 1,
        'map_gem_world' => 2,
        'location_gem_world' => 3,
    ];

    /**
     * Build the deduplicated, sorted list of cached Gem effect contexts a Monster currently appears in.
     */
    public function forMonster(Monster $monster): array
    {
        if (! $this->isEligibleForGemContexts($monster)) {
            return [];
        }

        $contexts = [];

        $this->collectFromCache(Cache::get(MonsterCacheKey::MONSTERS->value) ?? [], $monster, $contexts);
        $this->collectFromCache(Cache::get(MonsterCacheKey::LOCATION_MONSTERS->value) ?? [], $monster, $contexts);

        return $this->sortContexts(array_values($contexts));
    }

    /**
     * Build the single current Gem effect context for an already-resolved effective Monster row, when one exists.
     */
    public function forEffectiveMonster(Monster $monster, array $effectiveMonster): ?array
    {
        if (! $this->isEligibleForGemContexts($monster)) {
            return null;
        }

        $gemEffectContext = $effectiveMonster['gem_effect_context'] ?? null;

        if (is_null($gemEffectContext) || ! ($gemEffectContext['has_effects'] ?? false)) {
            return null;
        }

        $key = $this->buildContextKey($gemEffectContext);

        if (is_null($key)) {
            return null;
        }

        return $this->buildContext($key, $gemEffectContext, $effectiveMonster, $monster);
    }

    /**
     * Determine whether a Monster is eligible to carry Gem effect contexts at all.
     */
    private function isEligibleForGemContexts(Monster $monster): bool
    {
        return ! $monster->is_celestial_entity
            && ! $monster->is_raid_monster
            && ! $monster->is_raid_boss
            && is_null($monster->only_for_location_type);
    }

    /**
     * Scan every cache entry (including the regular/easier event-map tiers) for the Monster.
     */
    private function collectFromCache(array $cache, Monster $monster, array &$contexts): void
    {
        foreach ($cache as $entry) {
            if (isset($entry['data'])) {
                $this->collectFromDataset($entry['data'], $monster, $contexts);

                continue;
            }

            foreach (['regular', 'easier'] as $tier) {
                if (isset($entry[$tier]['data'])) {
                    $this->collectFromDataset($entry[$tier]['data'], $monster, $contexts);
                }
            }
        }
    }

    /**
     * Collect the Monster's matching, Gem-affected cache row from a single cached data set.
     */
    private function collectFromDataset(array $dataset, Monster $monster, array &$contexts): void
    {
        foreach ($dataset as $cachedMonster) {
            if (($cachedMonster['id'] ?? null) !== $monster->id) {
                continue;
            }

            $gemEffectContext = $cachedMonster['gem_effect_context'] ?? null;

            if (is_null($gemEffectContext) || ! ($gemEffectContext['has_effects'] ?? false)) {
                continue;
            }

            $key = $this->buildContextKey($gemEffectContext);

            if (is_null($key) || isset($contexts[$key])) {
                continue;
            }

            $contexts[$key] = $this->buildContext($key, $gemEffectContext, $cachedMonster, $monster);
        }
    }

    /**
     * Build the stable deduplication key for a resolved Gem effect context.
     */
    private function buildContextKey(array $gemEffectContext): ?string
    {
        $contextType = $gemEffectContext['context_type'] ?? null;
        $gameMapId = $gemEffectContext['game_map']['id'] ?? null;
        $locationId = $gemEffectContext['location']['id'] ?? null;

        return match ($contextType) {
            'map' => is_null($gameMapId) ? null : 'map-'.$gameMapId,
            'location' => is_null($locationId) ? null : 'location-'.$locationId,
            'map_gem_world' => is_null($gameMapId) ? null : 'map-gem-world-'.$gameMapId,
            'location_gem_world' => is_null($gameMapId) ? null : 'location-gem-world-'.$gameMapId,
            default => null,
        };
    }

    /**
     * Build one Monster Gem effect context row for the Admin/Info detail contract.
     */
    private function buildContext(string $key, array $gemEffectContext, array $cachedMonster, Monster $monster): array
    {
        return [
            'key' => $key,
            'type' => $gemEffectContext['context_type'],
            'label' => $gemEffectContext['context_label'],
            'game_map' => $gemEffectContext['game_map'],
            'location' => $gemEffectContext['location'],
            'sources' => $gemEffectContext['sources'],
            'character_power_reduction' => $gemEffectContext['character_power_reduction'],
            'changed_values' => $this->buildChangedValues($monster, $cachedMonster),
        ];
    }

    /**
     * Identify only the effective Monster fields that actually changed from the factual base.
     */
    private function buildChangedValues(Monster $monster, array $cachedMonster): array
    {
        $changes = [];

        $toHitBaseChange = $this->buildChange(
            'to_hit_base',
            'To Hit Base',
            'number',
            $monster->{$monster->damage_stat},
            $cachedMonster['to_hit_base'] ?? null,
        );

        if (! is_null($toHitBaseChange)) {
            $changes[] = $toHitBaseChange;
        }

        foreach (self::FIELD_MAP as $cacheKey => $descriptor) {
            $change = $this->buildChange(
                $cacheKey,
                $descriptor['label'],
                $descriptor['type'],
                $monster->{$descriptor['base']},
                $cachedMonster[$cacheKey] ?? null,
            );

            if (! is_null($change)) {
                $changes[] = $change;
            }
        }

        return $changes;
    }

    /**
     * Build a changed-value row when the effective Monster value meaningfully differs.
     */
    private function buildChange(string $field, string $label, string $displayType, mixed $baseValue, mixed $effectiveValue): ?array
    {
        if ($this->valuesEqual($baseValue, $effectiveValue)) {
            return null;
        }

        if ($this->isMeaninglessValue($effectiveValue)) {
            return null;
        }

        return [
            'field' => $field,
            'label' => $label,
            'base_value' => $baseValue,
            'effective_value' => $effectiveValue,
            'display_type' => $displayType,
        ];
    }

    /**
     * Determine whether a base and effective value are factually equal, tolerating float rounding.
     */
    private function valuesEqual(mixed $base, mixed $effective): bool
    {
        if (is_float($base) || is_float($effective)) {
            return abs((float) ($base ?? 0) - (float) ($effective ?? 0)) < 0.0001;
        }

        return $base === $effective;
    }

    /**
     * Determine whether an effective value is meaningless and should be omitted from display.
     */
    private function isMeaninglessValue(mixed $value): bool
    {
        if (is_null($value) || $value === false) {
            return true;
        }

        if (is_numeric($value)) {
            return (float) $value <= 0.0;
        }

        return false;
    }

    /**
     * Sort contexts: normal Map, then Locations by label, then Map Gem Worlds, then Location Gem Worlds.
     */
    private function sortContexts(array $contexts): array
    {
        usort($contexts, function (array $a, array $b): int {
            $orderA = self::CONTEXT_TYPE_ORDER[$a['type']] ?? 99;
            $orderB = self::CONTEXT_TYPE_ORDER[$b['type']] ?? 99;

            if ($orderA !== $orderB) {
                return $orderA <=> $orderB;
            }

            return strcmp($a['label'] ?? '', $b['label'] ?? '');
        });

        return $contexts;
    }
}
