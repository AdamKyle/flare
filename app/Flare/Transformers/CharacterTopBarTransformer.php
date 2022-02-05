<?php

namespace App\Flare\Transformers;

use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use Illuminate\Support\Facades\Cache;
use League\Fractal\TransformerAbstract;
use App\Flare\Models\Character;
use App\Flare\Models\MaxLevelConfiguration;
use App\Flare\Values\ItemEffectsValue;
use App\Game\Battle\Values\MaxLevel;

class CharacterTopBarTransformer extends TransformerAbstract {

    /**
     * Gets the response data for the character sheet
     *
     * @param Character $character
     * @return mixed
     */
    public function transform(Character $character) {
        return [
            'id'                => $character->id,
            'name'              => $character->name,
            'attack'            => number_format($this->getFromCache($character, 'attack')),
            'health'            => number_format($this->getFromCache($character, 'health')),
            'ac'                => number_format($this->getFromCache($character, 'ac')),
            'race'              => $character->race->name,
            'class'             => $character->class->name,
            'level'             => number_format($character->level),
            'max_level'         => number_format($this->getMaxLevel($character)),
            'xp'                => (int) $character->xp,
            'xp_next'           => (int) $character->xp_next,
            'str_modded'        => number_format($this->getFromCache($character, 'str_modded')),
            'dur_modded'        => number_format($this->getFromCache($character, 'dur_modded')),
            'dex_modded'        => number_format($this->getFromCache($character, 'dex_modded')),
            'chr_modded'        => number_format($this->getFromCache($character, 'chr_modded')),
            'int_modded'        => number_format($this->getFromCache($character, 'int_modded')),
            'agi_modded'        => number_format($this->getFromCache($character, 'agi_modded')),
            'focus_modded'      => number_format($this->getFromCache($character, 'focus_modded')),
            'gold'              => number_format($character->gold),
            'gold_dust'         => number_format($character->gold_dust),
            'shards'            => number_format($character->shards),
            'is_dead'           => $character->is_dead,
            'can_adventure'     => $character->can_adventure,
        ];
    }

    protected function getMaxLevel(Character $character) {

        $item = Item::where('effect', ItemEffectsValue::CONTNUE_LEVELING)->first();

        if (is_null($item)) {
            return MaxLevel::MAX_LEVEL;
        }

        $inventory = Inventory::where('character_id', $character->id)->first();

        $slot = InventorySlot::where('item_id', $item->id)->where('inventory_id', $inventory->id)->first();

        if (!is_null($slot)) {
            return MaxLevelConfiguration::first()->max_level;
        }

        return MaxLevel::MAX_LEVEL;
    }

    protected function getFromCache(Character $character, string $stat): mixed {
        if (!Cache::has('character-attack-data-' . $character->id)) {
            return 0.0;
        }

        $cache = Cache::get('character-attack-data-' . $character->id);

        return $cache['character_data'][$stat];
    }
}
