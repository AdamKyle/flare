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
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Kingdoms\Jobs\MassEmbezzle;
use App\Game\Kingdoms\Requests\KingdomDepositRequest;
use App\Game\Kingdoms\Requests\PurchaseGoldBarsRequest;
use App\Game\Kingdoms\Requests\PurchasePeopleRequest;
use App\Game\Kingdoms\Requests\WithdrawGoldBarsRequest;
use App\Game\Kingdoms\Values\KingdomMaxValue;
use App\Game\Kingdoms\Values\UnitCosts;
use App\Game\Kingdoms\Requests\KingdomRenameRequest;
use App\Game\Kingdoms\Service\KingdomBuildingService;
use App\Game\Kingdoms\Events\UpdateKingdom;
use App\Game\Kingdoms\Requests\KingdomEmbezzleRequest;
use App\Game\Kingdoms\Events\AddKingdomToMap;
use App\Game\Kingdoms\Events\UpdateGlobalMap;
use App\Game\Messages\Events\ServerMessageEvent;

class KingdomGoldBarsController extends Controller {

    /**
     * @var UpdateKingdomHandler $updateKingdomHandler
     */
    private UpdateKingdomHandler $updateKingdomHandler;

    /**
     * @param UpdateKingdomHandler $updateKingdomHandler
     */
    public function __construct(UpdateKingdomHandler $updateKingdomHandler){
        $this->updateKingdomHandler = $updateKingdomHandler;
    }

    /**
     * @param PurchaseGoldBarsRequest $request
     * @param Kingdom $kingdom
     * @return JsonResponse
     */
    public function purchaseGoldBars(PurchaseGoldBarsRequest $request, Kingdom $kingdom): JsonResponse {
        if ($kingdom->character->id !== auth()->user()->character->id) {
            return response()->json([
                'message' => 'Invalid Input. Not allowed to do that.'
            ], 422);
        }

        $amountToBuy = $request->amount_to_purchase;

        if ($amountToBuy > 1000) {
            $amountToBuy = 1000;
        }

        $newGoldBars = $amountToBuy + $kingdom->gold_bars;

        if ($newGoldBars > 1000) {
            return response()->json([
                'message' => 'Too many gold bars.'
            ], 422);
        }

        $cost = $amountToBuy * 2000000000;

        $character = $kingdom->character;

        if ($cost > $character->gold) {
            return response()->json(['message' => 'Not enough gold.'], 422);
        }

        $character->update([
            'gold' => $character->gold - $cost
        ]);

        $kingdom->update([
            'gold_bars' => $newGoldBars,
        ]);

        $kingdom = new Item($kingdom->refresh(), $this->kingdom);

        $kingdom = $this->manager->createData($kingdom)->toArray();

        event(new UpdateTopBarEvent($character->refresh()));
        event(new UpdateKingdom($character->user, $kingdom));

        return response()->json([
            'message' => 'Purchased: ' . $amountToBuy . ' Gold bars.'
        ], 200);
    }

    /**
     * @param WithdrawGoldBarsRequest $request
     * @param Kingdom $kingdom
     * @return JsonResponse
     */
    public function withdrawGoldBars(WithdrawGoldBarsRequest $request, Kingdom $kingdom): JsonResponse {
        if ($kingdom->character->id !== auth()->user()->character->id) {
            return response()->json([
                'message' => 'Invalid Input. Not allowed to do that.'
            ], 422);
        }

        $amount = $request->amount_to_withdraw;

        if ($kingdom->gold_bars < $amount) {
            return response()->json([
                'message' => "You don't have enough bars to do that."
            ], 422);
        }

        $totalGold = $amount * 2000000000;
        $character = $kingdom->character;

        $newGold = $character->gold + $totalGold;

        if ($newGold > MaxCurrenciesValue::MAX_GOLD) {
            return response()->json([
                'message' => 'This would cause you to go over the max allowed gold. You cannot do that.'
            ], 422);
        }

        $newAmount = $kingdom->gold_bars - $amount;

        if ($newAmount < 0) {
            return response()->json([
                'message' => 'Child! You do not have that many gold bars!'
            ], 422);
        }

        $character->update([
            'gold' => $newGold,
        ]);

        $kingdom->update([
            'gold_bars' => $newAmount,
        ]);

        $character = $character->refresh();

        event(new UpdateTopBarEvent($character));

        $this->updateKingdomHandler->refreshPlayersKingdoms($character);

        return response()->json([
            'message' => 'Exchanged: ' . $amount . ' Gold bars for: ' . $totalGold . ' Gold!',
        ], 200);
    }
}
