<?php

namespace App\Game\Market\Transformers;

use App\Flare\Models\MarketBoard;
use App\Game\Core\Items\Transformers\ItemTransformer;
use App\Game\Core\Items\Values\ItemUniqueness;
use League\Fractal\Resource\Primitive;
use League\Fractal\TransformerAbstract;

class MarketItemsTransformer extends TransformerAbstract
{
    protected array $availableIncludes = ['item'];

    /**
     * @param ItemTransformer $itemTransformer
     */
    public function __construct(private readonly ItemTransformer $itemTransformer) {}

    /**
     * Transform a Market listing into its listing metadata.
     *
     * @param MarketBoard $marketListing
     * @return array
     */
    public function transform(MarketBoard $marketListing): array
    {
        return [
            'id' => $marketListing->id,
            'character_id' => $marketListing->character_id,
            'item_id' => $marketListing->item_id,
            'name' => $marketListing->item->affix_name,
            'listed_price' => $marketListing->listed_price,
            'is_locked' => $marketListing->is_locked,
            'character_name' => $marketListing->character->name,
            'type' => $marketListing->item->type,
            'unique' => ItemUniqueness::fromItem($marketListing->item)->isUnique(),
            'listed_at' => $marketListing->created_at,
        ];
    }

    /**
     * Include the canonical item details for the listed Item.
     *
     * @param MarketBoard $marketListing
     * @return Primitive
     */
    public function includeItem(MarketBoard $marketListing): Primitive
    {
        return $this->primitive($this->itemTransformer->transform($marketListing->item));
    }
}
