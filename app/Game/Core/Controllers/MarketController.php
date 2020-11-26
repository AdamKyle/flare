<?php

namespace App\Game\Core\Controllers;

use App\Http\Controllers\Controller;

class MarketController extends Controller {

    public function __construct() {
        $this->middleware('auth');

        $this->middleware('is.character.dead');
        
        $this->middleware('is.character.adventuring');
    }

    public function index() {
        return view('game.core.market.market');
    }

    public function sell() {
        return view('game.core.market.sell');
    }
}