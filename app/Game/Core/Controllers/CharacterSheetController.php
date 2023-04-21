<?php

namespace App\Game\Core\Controllers;

use App\Http\Controllers\Controller;

class CharacterSheetController extends Controller {

    public function __construct() {
        $this->middleware('auth');
    }

    public function index() {
        $character     = auth()->user()->character;

        return view ('game.character.sheet', [
            'character' => $character,
        ]);
    }
}
