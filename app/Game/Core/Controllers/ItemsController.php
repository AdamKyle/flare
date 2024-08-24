<?php

namespace App\Game\Core\Controllers;

use App\Flare\Models\Item;
use App\Flare\Traits\Controllers\ItemsShowInformation;
use App\Http\Controllers\Controller;

class ItemsController extends Controller
{
    use ItemsShowInformation;

    public function show(Item $item)
    {
        return $this->renderItemShow('game.items.item', $item);
    }
}
