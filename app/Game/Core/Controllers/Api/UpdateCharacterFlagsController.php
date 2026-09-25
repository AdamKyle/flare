<?php

namespace App\Game\Core\Controllers\Api;

use App\Flare\Models\Character;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class UpdateCharacterFlagsController extends Controller
{
    /**
     * Turn off the intro slides for the character's user.
     *
     * @param Character $character
     * @return JsonResponse
     */
    public function turnOffIntro(Character $character): JsonResponse
    {
        $character->user()->update([
            'show_intro_page' => false,
        ]);

        return response()->json();
    }
}
