<?php

namespace App\Game\Core\Controllers;

use App\Flare\Models\Item;
use App\Flare\Models\Monster;
use App\Http\Controllers\Controller;

class MonstersController extends Controller {

    public function __construct() {
    }

    public function show(Monster $monster) {
        return view('admin.monsters.monster', [
            'monster' => $monster,
        ]);
    }
}
