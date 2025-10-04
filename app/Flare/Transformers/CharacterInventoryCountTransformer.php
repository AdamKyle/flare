<?php

namespace App\Flare\Transformers;

use App\Flare\Models\Character;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySlot;
use League\Fractal\TransformerAbstract;

class CharacterInventoryCountTransformer extends TransformerAbstract
{
    /**
     * Undocumented function
     */
    public function transform(Character $character): array
    {

        return [
            'inventory_max' => $character->inventory_max,
            'inventory_count' => $character->getInventoryCount(),
            'gem_bag_count' => $character->gemBag->gemSlots->sum('amount'),
            'inventory_bag_count' => $this->getInventoryBagCount($character),
            'alchemy_item_count' => $this->getAlchemyItemCount($character),
        ];
    }

    private function getInventoryBagCount(Character $character)
    {
        $inventory = Inventory::where('character_id', $character->id)->first();

        return InventorySlot::select('inventory_slots.*')
            ->where('inventory_slots.inventory_id', $inventory->id)
            ->where('inventory_slots.equipped', false)
            ->join('items', function ($join) {
                $join->on('items.id', '=', 'inventory_slots.item_id')
                    ->where('items.type', '!=', 'quest')
                    ->where('items.type', '!=', 'alchemy');
            })->count();
    }

    private function getAlchemyItemCount(Character $character)
    {
        $inventory = Inventory::where('character_id', $character->id)->first();

        return InventorySlot::select('inventory_slots.*')
            ->where('inventory_slots.inventory_id', $inventory->id)
            ->where('inventory_slots.equipped', false)
            ->join('items', function ($join) {
                $join->on('items.id', '=', 'inventory_slots.item_id')
                    ->where('items.type', '!=', 'quest')
                    ->where('items.type', '=', 'alchemy');
            })->count();
    }
}
