<?php

namespace App\Game\Core\Controllers;

use App\Flare\Models\Item;
use App\Http\Controllers\Controller;

class ItemsController extends Controller {

    public function __construct() {
    }

    public function show(Item $item) {
        return view('game.items.item', [
            'item' => $item,
        ]);
    }
}
