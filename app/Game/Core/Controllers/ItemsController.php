<?php

namespace App\Game\Core\Controllers;

use App\Http\Controllers\Controller;
use App\Flare\Models\Item;
use App\Flare\Traits\Controllers\ItemsShowInformation;


class ItemsController extends Controller {

    use ItemsShowInformation;

    public function show(Item $item) {
        return $this->renderItemShow('game.items.item', $item);
    }
}
