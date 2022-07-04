<?php

namespace App\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\UnitMovementQueue;
use App\Game\Kingdoms\Handlers\UpdateKingdomHandler;
use App\Game\Kingdoms\Service\KingdomResourcesService;
use App\Game\Kingdoms\Service\KingdomSettleService;
use App\Game\Messages\Events\GlobalMessageEvent;
use Illuminate\Http\JsonResponse;
use League\Fractal\Resource\Item;
use App\Http\Controllers\Controller;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Flare\Models\Kingdom;
use App\Game\Kingdoms\Requests\PurchasePeopleRequest;
use App\Game\Kingdoms\Values\KingdomMaxValue;
use App\Game\Kingdoms\Values\UnitCosts;
use App\Game\Kingdoms\Requests\KingdomRenameRequest;
use App\Game\Kingdoms\Events\UpdateKingdom;
use App\Game\Kingdoms\Events\AddKingdomToMap;
use App\Game\Kingdoms\Events\UpdateGlobalMap;

class KingdomsController extends Controller {

    /**
     * @var UpdateKingdomHandler $updateKingdomHandler
     */
    private UpdateKingdomHandler $updateKingdomHandler;

    /**
     * @var KingdomSettleService $kingdomSettleService
     */
    private KingdomSettleService $kingdomSettleService;

    /**
     * @var KingdomResourcesService $kingdomResourceServer
     */
    private KingdomResourcesService $kingdomResourceServer;

    /**
     * @param UpdateKingdomHandler $updateKingdomHandler
     * @param KingdomSettleService $kingdomSettleService
     * @param KingdomResourcesService $kingdomResourceServer
     */
    public function __construct(UpdateKingdomHandler $updateKingdomHandler,
                                KingdomSettleService $kingdomSettleService,
                                KingdomResourcesService $kingdomResourceServer)
    {

        $this->updateKingdomHandler    = $updateKingdomHandler;
        $this->kingdomSettleService    = $kingdomSettleService;
        $this->kingdomResourceServer   = $kingdomResourceServer;
    }

    /**
     * @param KingdomRenameRequest $request
     * @param Kingdom $kingdom
     * @return JsonResponse
     */
    public function rename(KingdomRenameRequest $request, Kingdom $kingdom): JsonResponse {
        $kingdom->update($request->all());

        $character = $kingdom->character->refresh();

        $this->kingdomSettleService->addKingdomToCache($character, $kingdom);

        $this->updateKingdomHandler->refreshPlayersKingdoms($character);

        event(new UpdateGlobalMap($character));
        event(new AddKingdomToMap($character));

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

        $kingdom = new Item($kingdom->refresh(), $this->kingdom);

        $kingdom = $this->manager->createData($kingdom)->toArray();

        event(new UpdateTopBarEvent($character->refresh()));
        event(new UpdateKingdom($character->user, $kingdom));

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
