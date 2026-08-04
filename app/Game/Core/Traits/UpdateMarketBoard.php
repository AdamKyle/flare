<?php

namespace App\Game\Core\Traits;

use App\Flare\Models\MarketBoard;
use App\Flare\Models\User;
use App\Game\Core\Events\UpdateMarketBoardBroadcastEvent;
use App\Game\Market\Transformers\MarketItemsTransformer;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;

trait UpdateMarketBoard
{
    public function sendUpdate(MarketItemsTransformer $transformer, Manager $manager, ?User $user = null)
    {
        $items = MarketBoard::where('is_locked', false)->get();
        $items = new Collection($items, $transformer);
        $items = $manager->createData($items)->toArray();

        if (is_null($user)) {
            $user = auth()->user();
        }

        event(new UpdateMarketBoardBroadcastEvent($user, $items, $user->character->gold));
    }
}
