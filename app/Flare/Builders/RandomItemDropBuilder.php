<?php

namespace App\Flare\Builders;

use Illuminate\Database\Eloquent\Builder;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Flare\Models\Location;

class RandomItemDropBuilder {

    /**
     * @var ?string $monsterPlane
     */
    private ?string $monsterPlane;

    /**
     * @var int $characterLevel
     */
    private int $characterLevel = 0;

    /**
     * @var int $monsterLevel
     */
    private int $monsterLevel   = 0;

    /**
     * @var Location|null $location
     */
    private ?Location $location;

    /**
     * Set the monster plane.
     *
     * @param string $name
     * @return $this
     */
    public function setMonsterPlane(string $name): RandomItemDropBuilder {
        $this->monsterPlane = $name;

        return $this;
    }

    /**
     * set the character level.
     *
     * @param int $level
     * @return $this
     */
    public function setCharacterLevel(int $level): RandomItemDropBuilder {
        $this->characterLevel = $level;

        return $this;
    }

    /**
     * set the monster level.
     *
     * @param int $level
     * @return $this
     */
    public function setMonsterMaxLevel(int $level): RandomItemDropBuilder {
        $this->monsterLevel = $level;

        return $this;
    }

    /**
     * Set the location.
     *
     * @param Location|null $location
     * @return RandomItemDropBuilder
     */
    public function setLocation(Location $location = null): RandomItemDropBuilder {
        $this->location = $location;

        return $this;
    }

    /**
     * Generates the random item for a player.
     *
     * @return Item|null
     */
    public function generateItem(): ?Item {
        $item    = $this->getItem();
        $affixes = $this->getAffixes();

        if (count($affixes) < 1) {
            return null;
        }

        $foundItem = $this->itemExists($item, $affixes);

        if (!is_null($foundItem)) {
            return $foundItem;
        }


        return $this->createItem($item, $affixes);
    }

    /**
     * Fetches a random item with not attached affixes.
     *
     * The item cannot be a quest item, artifact item or alchemy item.
     *
     * @return Item
     */
    protected function getItem(): Item {
        $query =  Item::inRandomOrder()->doesntHave('itemSuffix')
                                       ->doesntHave('itemPrefix')
                                       ->whereNotIn('type', ['artifact', 'quest', 'alchemy', 'trinket']);



        return $this->canDropRestrictions($query)->first();
    }

    /**
     * Fetches one or two Affixes.
     *
     * @return array
     */
    protected function getAffixes(): array {
        $affixes = [];

        $affixes[] = $this->canDropRestrictions(ItemAffix::inRandomOrder()->where('type', 'prefix'))->first();

        if (rand(1, 100) > 50) {
            $affix = $this->canDropRestrictions(ItemAffix::inRandomOrder()->where('type', 'suffix'))->first();

            if (!is_null($affix)) {
                $affixes[] = $this->canDropRestrictions(ItemAffix::inRandomOrder()->where('type', 'suffix'))->first();
            }
        }

        return $affixes;
    }

    /**
     * Returns a possible item that may already exist with the affixes.
     *
     * @param Item $item
     * @param array $affixes
     * @return Item|null
     */
    protected function itemExists(Item $item, array $affixes): ?Item {
        $query = Item::where('id', $item->id);

        foreach ($affixes as $affix) {
            $column = 'item_'.$affix->type.'_id';
            $query->where($column, $affix->id);
        }

        return $query->first();
    }

    /**
     * Creates a new entry in the database for a new item.
     *
     * @param Item $item
     * @param array $affixes
     * @return Item
     */
    protected function createItem(Item $item, array $affixes): Item {
        $item = $item->duplicate();

        $updates = [];

        foreach ($affixes as $affix) {
            $updates['item_' . $affix->type . '_id'] = $affix->id;
        }

        $item->update($updates);

        return $item;
    }

    /**
     * Defines the restrictions on random query.
     *
     * If the monster is not a Shadow Plane monster and the location is not special (null) and the characters levels are not 10 levels
     * higher than the max level, then we return only items or affixes that can drop.
     *
     * Else the valuation must be less than 4 billion gold.
     *
     * @param Builder $query
     * @return Builder
     */
    protected function canDropRestrictions(Builder $query): Builder {
        $totalLevels = $this->monsterLevel - $this->characterLevel;

        if (($this->monsterPlane !== 'Shadow Plane' || is_null($this->location)) && !($totalLevels >= 10)) {
            return $query->where('can_drop', true);
        }

        // Only drops up to 4 Billion is cost may drop.
        return $query->where('cost', '<=', 4000000000);
    }
}
