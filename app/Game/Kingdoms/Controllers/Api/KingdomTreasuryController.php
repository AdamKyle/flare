<?php

namespace App\Game\Kingdoms\Controllers\Api;

use Exception;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Kingdoms\Jobs\MassEmbezzle;
use App\Game\Kingdoms\Requests\KingdomDepositRequest;
use App\Game\Kingdoms\Values\KingdomMaxValue;
use App\Game\Kingdoms\Requests\KingdomEmbezzleRequest;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Kingdoms\Handlers\UpdateKingdomHandler;
use App\Game\Kingdoms\Service\KingdomService;

class KingdomTreasuryController extends Controller {

    /**
     * @var UpdateKingdomHandler $updateKingdomHandler
     */
    private UpdateKingdomHandler $updateKingdomHandler;

    /**
     * @var KingdomService $kingdomService
     */
    private KingdomService $kingdomService;

    /**
     * @param UpdateKingdomHandler $updateKingdomHandler
     * @param KingdomService $kingdomService
     */
    public function __construct(UpdateKingdomHandler $updateKingdomHandler, KingdomService $kingdomService)
    {
        $this->updateKingdomHandler = $updateKingdomHandler;
        $this->kingdomService       = $kingdomService;
    }

    /**
     * @param KingdomEmbezzleRequest $request
     * @param Kingdom $kingdom
     * @return JsonResponse
     * @throws Exception
     */
    public function embezzle(KingdomEmbezzleRequest $request, Kingdom $kingdom): JsonResponse
    {
        $amountToEmbezzle = $request->embezzle_amount;
        $newAGoldAmount = $kingdom->character->gold + $amountToEmbezzle;

        $maxCurrencies = new MaxCurrenciesValue($newAGoldAmount, MaxCurrenciesValue::GOLD);

        if ($maxCurrencies->canNotGiveCurrency()) {
            return response()->json([
                'message' => number_format($amountToEmbezzle) . ' Would put you well over the gold cap limit.'
            ], 422);
        }

        if ($kingdom->character->id !== auth()->user()->character->id) {
            return response()->json([
                'message' => 'Invalid Input. Not allowed to do that.'
            ], 422);
        }

        if ($amountToEmbezzle > $kingdom->treasury) {
            return response()->json([
                'message' => "You don't have the gold in your treasury."
            ], 422);
        }

        if ($kingdom->current_morale <= 0.15) {
            return response()->json([
                'message' => 'Morale is too low.'
            ], 422);
        }

        $this->kingdomService->embezzleFromKingdom($kingdom, $amountToEmbezzle);

        $kingdom = $kingdom->refresh();

        return response()->json([
            'message' => 'Withdrew: ' . number_format($amountToEmbezzle) . ' which in turn increased your morale to: ' . ($kingdom->current_morale * 100) . '%'
        ], 200);
    }

    /**
     * @param KingdomEmbezzleRequest $request
     * @param Character $character
     * @return JsonResponse
     */
    public function massEmbezzle(KingdomEmbezzleRequest $request, Character $character): JsonResponse
    {

        $character->update([
            'is_mass_embezzling' => true
        ]);

        MassEmbezzle::dispatch($character, $request->embezzle_amount)->delay(now()->addSeconds(5))->onConnection('long_running');

        event(new ServerMessageEvent($character->user, 'Mass Embezzling underway...'));

        return response()->json([
            'message' => 'Mass Embezzling underway for amount: ' . number_format($request->embezzle_amount) . '. Check server messages section below for more info.',
        ], 200);
    }

    /**
     * @param KingdomDepositRequest $request
     * @param Kingdom $kingdom
     * @return JsonResponse
     */
    public function deposit(KingdomDepositRequest $request, Kingdom $kingdom): JsonResponse {
        $amountToDeposit = $request->deposit_amount;

        if ($amountToDeposit <= 0) {
            return response()->json([
                'message' => 'Invalid Amount.'
            ], 422);
        }

        if ($kingdom->character->id !== auth()->user()->character->id) {
            return response()->json([
                'message' => 'Invalid Input. Not allowed to do that.'
            ], 422);
        }

        if ($amountToDeposit > KingdomMaxValue::MAX_TREASURY) {
            return response()->json([
                'message' => 'You cannot go over the max limit for kingdom treasury.'
            ], 422);
        }

        if ($amountToDeposit > $kingdom->character->gold) {
            return response()->json([
                'message' => 'And where are you getting this gold from? You do not have enough.'
            ], 422);
        }

        $newMorale = $kingdom->current_morale;

        // Is >= 10 million gold.
        if ($amountToDeposit >= 10000000) {

            $newMorale = $kingdom->current_morale + 0.05;

            if ($newMorale > 1) {
                $newMorale = 1;
            }
        }

        $kingdom->update([
            'treasury' => $kingdom->treasury + $amountToDeposit,
            'current_morale' => $newMorale,
        ]);

        $character = $kingdom->character;

        $character->update([
            'gold' => $character->gold - $amountToDeposit
        ]);

        $character = $character->refresh();

        $this->updateKingdomHandler->refreshPlayersKingdoms($character);

        event(new UpdateTopBarEvent($character));

        return response()->json([
            'message' => 'Deposited: ' . number_format($amountToDeposit) . ' which in turn increased your morale to: ' . ($newMorale * 100) . '%'
        ], 200);
    }
}
