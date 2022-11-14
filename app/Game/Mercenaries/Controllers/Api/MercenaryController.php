<?php

namespace App\Game\Mercenaries\Controllers\Api;


use App\Flare\Models\Character;
use App\Flare\Models\CharacterMercenary;
use App\Flare\Models\GuideQuest;
use App\Flare\Models\QuestsCompleted;
use App\Flare\Models\User;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\GuideQuests\Events\RemoveGuideQuestButton;
use App\Game\GuideQuests\Services\GuideQuestService;
use App\Game\Mercenaries\Requests\PurchaseMercenaryRequest;
use App\Game\Mercenaries\Services\MercenaryService;
use App\Game\Mercenaries\Values\MercenaryValue;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;

class MercenaryController extends Controller {

    private MercenaryService $mercenaryService;

    public function __construct(MercenaryService $mercenaryService) {
        $this->mercenaryService = $mercenaryService;
    }

    public function list(Character $character) {
        $charactersMercenary = $character->mercenaries;

        return response()->json([
            'merc_data'    => $this->mercenaryService->formatCharacterMercenaries($charactersMercenary),
            'mercs_to_buy' => MercenaryValue::mercenaries($charactersMercenary)
        ]);
    }

    public function buy(PurchaseMercenaryRequest $request, Character $character) {
        $response = $this->mercenaryService->purchaseMercenary($request->all(), $character);
        $status   = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
    }

    public function reincarnate(Character $character, CharacterMercenary $characterMercenary) {
        $response = $this->mercenaryService->reIncarnateMercenary($character, $characterMercenary);
        $status   = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
    }
}
