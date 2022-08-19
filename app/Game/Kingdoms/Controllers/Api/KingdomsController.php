<?php

namespace App\Game\Kingdoms\Controllers\Api;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Flare\Models\UnitMovementQueue;
use App\Flare\Models\Kingdom;
use App\Game\Kingdoms\Service\UpdateKingdom;
use App\Game\Kingdoms\Service\KingdomResourcesService;
use App\Game\Kingdoms\Service\KingdomSettleService;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Kingdoms\Requests\PurchasePeopleRequest;
use App\Game\Kingdoms\Values\KingdomMaxValue;
use App\Game\Kingdoms\Values\UnitCosts;
use App\Game\Kingdoms\Requests\KingdomRenameRequest;


class KingdomsController extends Controller {

    /**
     * @var UpdateKingdom $updateKingdom
     */
    private UpdateKingdom $updateKingdom;

    /**
     * @var KingdomSettleService $kingdomSettleService
     */
    private KingdomSettleService $kingdomSettleService;

    /**
     * @var KingdomResourcesService $kingdomResourceServer
     */
    private KingdomResourcesService $kingdomResourceServer;

    /**
     * @param UpdateKingdom $updateKingdom
     * @param KingdomSettleService $kingdomSettleService
     * @param KingdomResourcesService $kingdomResourceServer
     */
    public function __construct(UpdateKingdom $updateKingdom,
                                KingdomSettleService $kingdomSettleService,
                                KingdomResourcesService $kingdomResourceServer)
    {

        $this->updateKingdom           = $updateKingdom;
        $this->kingdomSettleService    = $kingdomSettleService;
        $this->kingdomResourceServer   = $kingdomResourceServer;
    }

    /**
     * @param KingdomRenameRequest $request
     * @param Kingdom $kingdom
     * @return JsonResponse
     */
    public function rename(KingdomRenameRequest $request, Kingdom $kingdom): JsonResponse {
        $user = auth()->user();

        if ($kingdom->character_id !== $user->character->id) {
            return response()->json([
                'message' => 'Not allowed to do that.'
            ], 422);
        }

        $kingdom->update($request->all());

        $this->updateKingdom->updateKingdom($kingdom->refresh());

        return response()->json();
    }

    /**
     * @param PurchasePeopleRequest $request
     * @param Kingdom $kingdom
     * @return JsonResponse
     */
    public function purchasePeople(PurchasePeopleRequest $request, Kingdom $kingdom): JsonResponse {
        if ($kingdom->character->id !== auth()->user()->character->id) {
            return response()->json([
                'message' => 'Invalid Input. Not allowed to do that.'
            ], 422);
        }

        $amountToBuy = $request->amount_to_purchase;

        if ($amountToBuy > KingdomMaxValue::MAX_CURRENT_POPULATION) {
            $amountToBuy = KingdomMaxValue::MAX_CURRENT_POPULATION;
        }

        $newAmount = $kingdom->current_population + $amountToBuy;

        if ($newAmount > KingdomMaxValue::MAX_CURRENT_POPULATION) {
            $newAmount = KingdomMaxValue::MAX_CURRENT_POPULATION;
        }

        $character = $kingdom->character;

        $character->gold -= (new UnitCosts(UnitCosts::PERSON))->fetchCost() * $amountToBuy;

        $character->save();

        $character = $character->refresh();

        $kingdom->update([
            'current_population' => $newAmount,
        ]);

        $this->updateKingdom->updateKingdom($kingdom->refresh());

        event(new UpdateTopBarEvent($character->refresh()));

        return response()->json([], 200);
    }

    /**
     * @param Kingdom $kingdom
     * @return JsonResponse
     */
    public function abandon(Kingdom $kingdom): JsonResponse {
        if ($kingdom->character->id !== auth()->user()->character->id) {
            return response()->json([
                'message' => 'Invalid Input. Not allowed to do that.'
            ], 422);
        }

        $unitsInMovement = UnitMovementQueue::where('from_kingdom_id', $kingdom->id)->orWhere('to_kingdom_id', $kingdom->id)->get();

        if ($unitsInMovement->isNotEmpty()) {
            return response()->json([
                'message' => 'You either sent units that are currently moving, or an attack is incoming. Either way, there are units in movement from or to this kingdom and you cannot abandon it.'
            ], 422);
        }

        if ($kingdom->gold_bars > 0) {
            return response()->json([
                'message' => 'You cannot abandon a kingdom that has Gold Bars.'
            ], 422);
        }

        $this->kingdomResourceServer->abandonKingdom($kingdom);

        event(new GlobalMessageEvent('The Creator feels for the people of: ' . $kingdom->name . ' as their leader selfishly leaves them to fend for themselves.'));

        return response()->json([
            'message' => 'Kingdom has been abandoned.'
        ]);
    }
}
