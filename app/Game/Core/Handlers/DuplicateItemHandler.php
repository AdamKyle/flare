<?php

namespace App\Game\Core\Handlers;

use App\Flare\Models\Item;

class DuplicateItemHandler {

    /**
     * Duplicate the item.
     *
     * - Applies enchants and holy stacks to the item.
     *
     * @param Item $item
     * @return Item
     */
    public function duplicateItem(Item $item): Item {
        $newItem = $item->duplicate();

        $newItem->update([
            'item_prefix_id' => $item->item_prefix_id,
            'item_suffix_id' => $item->item_suffix_id,
        ]);

        $newItem = $newItem->refresh();

        $hasItemAffix = (!is_null($newItem->item_prefix_id) || !is_null($newItem->item_suffix_id));
        $hasHoly      = $newItem->appliedHolyStacks->count() > 0;

        if ($hasItemAffix || $hasHoly) {
            $newItem->update([
                'market_sellable' => true
            ]);

            $newItem = $newItem->refresh();
        }

        $newItem = $this->applyHolyStacks($item, $newItem);

        return $this->applyGems($item, $newItem);
    }

    /**
     * Apply holy stacks from the old item to the new one.
     *
     * @param Item $oldItem
     * @param Item $newItem
     * @return Item
     */
    protected function applyHolyStacks(Item $oldItem, Item $newItem): Item {
        if ($oldItem->appliedHolyStacks()->count() > 0) {

            foreach ($oldItem->appliedHolyStacks as $stack) {
                $stackAttributes = $stack->getAttributes();

                $stackAttributes['item_id'] = $newItem->id;

                $newItem->appliedHolyStacks()->create($stackAttributes);
            }
        }

        return $newItem->refresh();
    }

    /**
     * Add gems.
     *
     * @param Item $oldItem
     * @param Item $newItem
     * @return Item
     */
    protected function applyGems(Item $oldItem, Item $newItem): Item {
        if ($oldItem->socket_count > 0) {
            foreach ($oldItem->sockets as $socket) {
                $newItem->sockets()->create([
                    'item_id' => $newItem->id,
                    'gem_id'  => $socket->gem_id,
                ]);
            }

            $newItem->update([
                'socket_count' => $oldItem->socket_count
            ]);

            return $newItem->refresh();
        }

        return $newItem;
    }
}
