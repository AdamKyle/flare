<?php

namespace App\Game\Adventures\Controllers;

use App\Flare\Models\Adventure;
use App\Http\Controllers\Controller;

class AdventuresController extends Controller {

    public function show(Adventure $adventure) {
        return view('admin.adventures.adventure', [
            'adventure' => $adventure,
        ]);
    }
}
