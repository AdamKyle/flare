<?php

namespace App\Game\Market\Transformers;

use App\Flare\Models\MarketBoard;
use App\Game\Core\Items\Values\ItemUniqueness;
use League\Fractal\TransformerAbstract;

class MarketItemsTransformer extends TransformerAbstract
{
    /**
     * Gets the response data for the character sheet
     */
    public function transform(MarketBoard $marketListing): array
    {

        return [
            'id' => $marketListing->id,
            'character_id' => $marketListing->character_id,
            'item_id' => $marketListing->item_id,
            'name' => $marketListing->item->affix_name,
            'listed_price' => $marketListing->listed_price,
            'character_name' => $marketListing->character->name,
            'type' => $marketListing->item->type,
            'unique' => ItemUniqueness::fromItem($marketListing->item)->isUnique(),
            'listed_at' => $marketListing->created_at,
        ];
    }
}
