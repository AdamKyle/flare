<?php

namespace App\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\Kingdom;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Character\CharacterSheet\Events\UpdateCharacterBaseDetailsEvent;
use App\Game\Kingdoms\Requests\PurchaseGoldBarsRequest;
use App\Game\Kingdoms\Requests\WithdrawGoldBarsRequest;
use App\Game\Kingdoms\Service\UpdateKingdom;
use App\Game\Kingdoms\Values\KingdomMaxValue;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class KingdomGoldBarsController extends Controller
{
    private UpdateKingdom $updateKingdom;

    public function __construct(UpdateKingdom $updateKingdom)
    {
        $this->updateKingdom = $updateKingdom;
    }

    public function purchaseGoldBars(PurchaseGoldBarsRequest $request, Kingdom $kingdom): JsonResponse
    {
        if ($kingdom->character->id !== auth()->user()->character->id) {
            return response()->json([
                'message' => 'Invalid Input. Not allowed to do that.',
            ], 422);
        }

        if ($kingdom->buildings->where('name', 'Goblin Coin Bank')->first()->level < 5) {
            return response()->json([
                'message' => 'Goblin Coin Bank must be level 5 or higher to purchase.',
            ], 422);
        }

        $amountToBuy = $request->amount_to_purchase;

        $newAmount = $amountToBuy + $kingdom->gold_bars;

        if ($newAmount > KingdomMaxValue::MAX_GOLD_BARS) {
            return response()->json([
                'message' => 'Cannot go over the max amount of Gold Bars: 1000',
            ], 422);
        }

        if ($amountToBuy > 1000) {
            $amountToBuy = 1000;
        }

        $newGoldBars = $amountToBuy + $kingdom->gold_bars;

        if ($newGoldBars > 1000) {
            $amountToBuy = $amountToBuy - $kingdom->gold_bars;
        }

        $cost = $amountToBuy * 2000000000;

        $character = $kingdom->character;

        if ($cost > $character->gold) {
            return response()->json(['message' => 'Not enough gold.'], 422);
        }

        $character->update([
            'gold' => $character->gold - $cost,
        ]);

        $kingdom->update([
            'gold_bars' => $newGoldBars,
        ]);

        $this->updateKingdom->updateKingdom($kingdom->refresh());

        $this->updateKingdom->updateKingdomAllKingdoms($character->refresh());

        event(new UpdateCharacterBaseDetailsEvent($character->refresh()));

        return response()->json([
            'message' => 'Purchased: '.number_format($amountToBuy).' Gold bars.',
        ], 200);
    }

    public function withdrawGoldBars(WithdrawGoldBarsRequest $request, Kingdom $kingdom): JsonResponse
    {
        if ($kingdom->character->id !== auth()->user()->character->id) {
            return response()->json([
                'message' => 'Invalid Input. Not allowed to do that.',
            ], 422);
        }

        if ($kingdom->buildings->where('name', 'Goblin Coin Bank')->first()->level < 5) {
            return response()->json([
                'message' => 'Goblin Coin Bank must be level 5 or higher to withdraw.',
            ], 422);
        }

        $amount = $request->amount_to_withdraw;

        if ($kingdom->gold_bars < $amount) {
            $amount = $kingdom->gold_bars;
        }

        $totalGold = $amount * 2000000000;
        $character = $kingdom->character;

        $newGold = $character->gold + $totalGold;

        if ($newGold > MaxCurrenciesValue::MAX_GOLD) {
            return response()->json([
                'message' => 'You would waste gold if you withdrew this amount.',
            ], 422);
        }

        $character->update([
            'gold' => $newGold,
        ]);

        $kingdom->update([
            'gold_bars' => $kingdom->gold_bars - $amount,
        ]);

        $character = $character->refresh();

        event(new UpdateCharacterBaseDetailsEvent($character));

        $this->updateKingdom->updateKingdom($kingdom->refresh());

        return response()->json([
            'message' => 'Exchanged: '.$amount.' Gold bars for: '.number_format($totalGold).' Gold!',
        ], 200);
    }
}
