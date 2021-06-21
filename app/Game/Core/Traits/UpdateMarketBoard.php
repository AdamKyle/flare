<?php

namespace App\Game\Core\Traits;

use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use App\Flare\Models\MarketBoard;
use App\Flare\Transformers\MarketItemsTransfromer;
use App\Game\Core\Events\UpdateMarketBoardBroadcastEvent;

trait UpdateMarketBoard {

    /**
     * @param MarketItemsTransfromer $transformer
     * @param Manager $manager
     */
    public function sendUpdate(MarketItemsTransfromer $transformer, Manager $manager) {
        $items = MarketBoard::where('is_locked', false)->get();
        $items = new Collection($items, $transformer);
        $items = $manager->createData($items)->toArray();

        event(new UpdateMarketBoardBroadcastEvent(auth()->user(), $items, auth()->user()->character->gold));
    }
}
