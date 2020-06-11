<?php

namespace App\Game\Core\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use Illuminate\Http\Request;

class AdventureController extends Controller {


    public function __construct() {
        $this->middleware('auth:api');
        $this->middleware('is.character.dead');
    }

    public function adventure(Request $request, Character $character) {
        dd($request->all());
    }
}
