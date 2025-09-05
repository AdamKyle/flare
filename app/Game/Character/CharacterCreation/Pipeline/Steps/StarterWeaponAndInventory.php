<?php

namespace App\Game\Character\CharacterCreation\Pipeline\Steps;

use App\Flare\Models\Item;
use App\Flare\Models\InventorySet;
use App\Game\Character\CharacterCreation\State\CharacterBuildState;
use App\Game\Character\CharacterInventory\Mappings\ItemTypeMapping;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class StarterWeaponAndInventory
{
    /**
     * Create gem bag and inventory, equip a starter weapon, and create 10 inventory sets.
     *
     * @param CharacterBuildState $state
     * @param Closure $next
     * @return CharacterBuildState
     */
    public function process(CharacterBuildState $state, Closure $next): CharacterBuildState
    {
        $character = $state->getCharacter();

        if ($character === null) {
            return $next($state);
        }

        $character->gemBag()->create([
            'character_id' => $character->id,
        ]);

        $character->inventory()->create([
            'character_id' => $character->id,
        ]);

        $starterWeaponId = $this->resolveStarterWeaponItemId($character->class->name);

        $character->inventory->slots()->create([
            'inventory_id' => $character->inventory->id,
            'item_id' => $starterWeaponId,
            'equipped' => true,
            'position' => 'left-hand',
        ]);

        $now = $state->getNow() ?? now();

        $rows = collect(range(1, 10))->map(function () use ($character, $now) {
            return [
                'character_id' => $character->id,
                'can_be_equipped' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->all();

        if (! empty($rows)) {
            InventorySet::query()->insert($rows);
        }

        return $next($state);
    }

    /**
     * Resolve a starter weapon id for a class name, preferring the first mapped type and
     * falling back to any valid starter item. Throws if none exist.
     *
     * @param string $className
     * @return int
     */
    private function resolveStarterWeaponItemId(string $className): int
    {
        $mapping = ItemTypeMapping::getForClass($className);

        $preferredTypes = match (true) {
            is_string($mapping) => [$mapping],
            is_array($mapping) => [$mapping[0]],
            default => [],
        };

        $id = $this->findStarterItemIdByTypes($preferredTypes);

        if ($id === null) {
            $id = $this->findAnyStarterItemId();
        }

        if ($id === null) {
            throw new \RuntimeException('No starter item exists for character creation.');
        }

        return (int) $id;
    }

    /**
     * Find a starter item id limited to specific weapon types.
     *
     * @param array<int, string> $types
     * @return int|null
     */
    private function findStarterItemIdByTypes(array $types): ?int
    {
        if (empty($types)) {
            return null;
        }

        return $this->baseStarterItemQuery()
            ->whereIn('type', $types)
            ->orderBy('id')
            ->value('id');
    }

    /**
     * Find any valid starter item id regardless of type.
     *
     * @return int|null
     */
    private function findAnyStarterItemId(): ?int
    {
        return $this->baseStarterItemQuery()
            ->orderBy('id')
            ->value('id');
    }

    /**
     * Base query for a starter item: no affixes, no specialty, no holy stacks, no sockets, and skill level 1.
     *
     * @return Builder
     */
    private function baseStarterItemQuery(): Builder
    {
        return Item::query()
            ->whereNull('item_suffix_id')
            ->whereNull('item_prefix_id')
            ->whereNull('specialty_type')
            ->doesntHave('appliedHolyStacks')
            ->doesntHave('sockets')
            ->where('skill_level_required', 1);
    }
}
