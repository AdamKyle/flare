<?php

namespace App\Flare\Transformers;

use App\Flare\Traits\IsItemUnique;
use League\Fractal\TransformerAbstract;
use App\Flare\Models\MarketBoard;

class MarketItemsTransformer extends TransformerAbstract {

    use IsItemUnique;

    /**
     * Gets the response data for the character sheet
     *
     * @param MarketBoard $marketListing
     * @return array
     */
    public function transform(MarketBoard $marketListing): array {

        return [
            'id'             => $marketListing->id,
            'character_id'   => $marketListing->character_id,
            'item_id'        => $marketListing->item_id,
            'name'           => $marketListing->item->affix_name,
            'listed_price'   => $marketListing->listed_price,
            'character_name' => $marketListing->character->name,
            'type'           => $marketListing->item->type,
            'unique'         => $this->isUnique($marketListing->item),
            'listed_at'      => $marketListing->created_at,
        ];
    }


}
