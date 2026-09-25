<?php

namespace App\Game\Market\Services;

use App\Flare\Models\Character;
use App\Flare\Models\MarketBoard;
use App\Game\Core\Events\UpdateMarketBoardBroadcastEvent;
use App\Game\Market\Transformers\MarketItemsTransformer;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;

class MarketRealtimePublisher
{
    /**
     * @param Manager $manager
     * @param MarketItemsTransformer $marketItemsTransformer
     */
    public function __construct(
        private readonly Manager $manager,
        private readonly MarketItemsTransformer $marketItemsTransformer,
    ) {}

    /**
     * Broadcast the current unlocked Market listings after a listing visibility change made by the character.
     *
     * @param Character $character
     * @return void
     */
    public function publish(Character $character): void
    {
        $listings = MarketBoard::where('is_locked', false)
            ->with(['item.itemPrefix', 'item.itemSuffix', 'character'])
            ->get();

        $marketListings = $this->manager->createData(new Collection($listings, $this->marketItemsTransformer))->toArray();

        event(new UpdateMarketBoardBroadcastEvent($character->user, $marketListings, $character->gold));
    }
}
