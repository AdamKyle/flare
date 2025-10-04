<?php

namespace App\Game\Core\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\GuideQuests\Events\OpenGuideQuestModal;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class UpdateCharacterFlagsController extends Controller
{
    /**
     * Turn off the intro slides
     */
    public function turnOffIntro(Character $character): JsonResponse
    {
        $character->user()->update([
            'show_intro_page' => false,
        ]);

        $character = $character->refresh();

        event(new OpenGuideQuestModal($character->user));

        return response()->json();
    }
}
