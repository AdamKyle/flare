<?php

namespace App\Game\Core\Controllers;

use App\Http\Controllers\Controller;
use App\Flare\Models\Adventure;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use App\Flare\Models\Quest;

class ItemsController extends Controller {

    public function show(Item $item) {
        return view('game.items.item', [
            'item' => $item,
            'monster'   => Monster::where('quest_item_id', $item->id)->first(),
            'quest'     => Quest::where('item_id', $item->id)->first(),
            'location'  => Location::where('quest_reward_item_id', $item->id)->first(),
            'adventure' => Adventure::where('reward_item_id', $item->id)->first(),
        ]);
    }
}
