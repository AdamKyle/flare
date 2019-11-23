<?php

namespace App\Game\Controllers;

use App\Http\Controllers\Controller;

class GameController extends Controller {

    public function __construct() {
        $this->middleware('auth');
    }

    public function game() {
        return view('game.game', [
            'user' => auth()->user(),
        ]);
    }
}
