<?php

namespace App\Game\Core\Traits;

use App\Flare\Models\User;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use App\Flare\Models\MarketBoard;
use App\Flare\Transformers\MarketItemsTransformer;
use App\Game\Core\Events\UpdateMarketBoardBroadcastEvent;

trait UpdateMarketBoard {

    /**
     * @param MarketItemsTransformer $transformer
     * @param Manager $manager
     * @param User|null $user
     */
    public function sendUpdate(MarketItemsTransformer $transformer, Manager $manager, User $user = null) {
        $items = MarketBoard::where('is_locked', false)->get();
        $items = new Collection($items, $transformer);
        $items = $manager->createData($items)->toArray();

        if (is_null($user)) {
            $user = auth()->user();
        }

        event(new UpdateMarketBoardBroadcastEvent($user, $items, $user->character->gold));
    }
}
