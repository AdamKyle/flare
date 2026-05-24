<?php

namespace App\Game\Reincarnate\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Reincarnate\Services\CharacterReincarnationService;
use App\Http\Controllers\Controller;

class ReincarnateController extends Controller
{
    private CharacterReincarnationService $reincarnateService;

    public function __construct(CharacterReincarnationService $reincarnateService)
    {
        $this->reincarnateService = $reincarnateService;
    }

    public function reincarnate(Character $character)
    {
        $result = $this->reincarnateService->reincarnate($character);

        $status = $result['status'];

        unset($result['status']);

        return response()->json($result, $status);
    }
}
