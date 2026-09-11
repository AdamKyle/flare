<?php

namespace Tests\Traits;

use App\Flare\Models\Item;
use App\Game\Gems\Progression\Values\GemScrollCurrencyType;
use Illuminate\Database\Eloquent\Collection;

trait CreateItem
{
    /**
     * Create an Item for tests.
     */
    public function createItem(array $options = []): Item
    {
        return Item::factory()->create($options);
    }

    /**
     * Create a generated XP Gem Scroll Item for tests.
     */
    public function createGemXpScrollItem(
        float $bonus = 0.10,
        int $lastsForMinutes = 120,
        array $options = [],
    ): Item {
        return Item::factory()->gemXpScroll($bonus, $lastsForMinutes)->create($options);
    }

    /**
     * Create a generated Currency Gem Scroll Item for tests.
     */
    public function createGemCurrencyScrollItem(
        float $bonus = 0.10,
        GemScrollCurrencyType $currencyType = GemScrollCurrencyType::GOLD,
        int $lastsForMinutes = 120,
        array $options = [],
    ): Item {
        return Item::factory()->gemCurrencyScroll($bonus, $currencyType, $lastsForMinutes)->create($options);
    }

    /**
     * Create a generated Item Gem Scroll Item for tests.
     */
    public function createGemItemScrollItem(
        float $bonus = 0.02,
        float $socketChance = 0.02,
        float $preGemChance = 0.01,
        int $lastsForMinutes = 120,
        array $options = [],
    ): Item {
        return Item::factory()->gemItemScroll($bonus, $socketChance, $preGemChance, $lastsForMinutes)->create($options);
    }

    /**
     * Create a count of Items with distinct sequential names for tests.
     */
    public function createDistinctlyNamedItems(int $count, string $namePrefix, array $options = []): Collection
    {
        return Item::factory()
            ->count($count)
            ->sequence(fn ($sequence) => ['name' => $namePrefix.' '.$sequence->index])
            ->create($options);
    }

    /**
     * Create the standard set of craftable equipment Items for tests.
     */
    public function createCraftableEquipmentItems(array $options = []): Collection
    {
        return Item::factory()
            ->state(array_merge([
                'can_craft' => true,
                'skill_level_required' => 0,
            ], $options))
            ->sequence(
                ['type' => 'body', 'crafting_type' => 'armour'],
                ['type' => 'leggings', 'crafting_type' => 'armour'],
                ['type' => 'sleeves', 'crafting_type' => 'armour'],
                ['type' => 'gloves', 'crafting_type' => 'armour'],
                ['type' => 'feet', 'crafting_type' => 'armour'],
                ['type' => 'helmet', 'crafting_type' => 'armour'],
                ['type' => 'ring', 'crafting_type' => 'ring'],
                ['type' => 'ring', 'crafting_type' => 'ring'],
                ['type' => 'spell-damage', 'crafting_type' => 'spell'],
                ['type' => 'spell-healing', 'crafting_type' => 'spell'],
            )
            ->count(10)
            ->create();
    }
}
