<?php

namespace App\Game\Core\Controllers\Api;

use App\Flare\Models\Character;
use App\Http\Controllers\Controller;

class UpdateCharacterFlagsController extends Controller
{

    public function turnOffIntro(Character $character) {
        $character->user()->update([
            'show_intro_page' => false,
        ]);

        return response()->json();
    }
}
