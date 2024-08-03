<?php

namespace App\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\Kingdom;
use App\Flare\Models\UnitInQueue;
use App\Flare\Models\UnitMovementQueue;
use App\Game\Kingdoms\Requests\KingdomRenameRequest;
use App\Game\Kingdoms\Requests\PurchasePeopleRequest;
use App\Game\Kingdoms\Service\AbandonKingdomService;
use App\Game\Kingdoms\Service\PurchasePeopleService;
use App\Game\Kingdoms\Service\UpdateKingdom;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class KingdomsController extends Controller
{
    private UpdateKingdom $updateKingdom;

    private PurchasePeopleService $purchasePeopleService;

    private AbandonKingdomService $abandonKingdomService;

    public function __construct(UpdateKingdom $updateKingdom,
        PurchasePeopleService $purchasePeopleService,
        AbandonKingdomService $abandonKingdomService
    ) {

        $this->updateKingdom = $updateKingdom;
        $this->purchasePeopleService = $purchasePeopleService;
        $this->abandonKingdomService = $abandonKingdomService;
    }

    public function rename(KingdomRenameRequest $request, Kingdom $kingdom): JsonResponse
    {
        $user = auth()->user();

        if ($kingdom->character_id !== $user->character->id) {
            return response()->json([
                'message' => 'Not allowed to do that.',
            ], 422);
        }

        $kingdom->update($request->all());

        $this->updateKingdom->updateKingdom($kingdom->refresh());

        return response()->json();
    }

    public function purchasePeople(PurchasePeopleRequest $request, Kingdom $kingdom): JsonResponse
    {
        if ($kingdom->character->id !== auth()->user()->character->id) {
            return response()->json([
                'message' => 'Invalid Input. Not allowed to do that.',
            ], 422);
        }

        $this->purchasePeopleService->setKingdom($kingdom)->purchasePeople($request->amount_to_purchase);

        return response()->json([], 200);
    }

    public function abandon(Kingdom $kingdom): JsonResponse
    {
        if ($kingdom->character->id !== auth()->user()->character->id) {
            return response()->json([
                'message' => 'Invalid Input. Not allowed to do that.',
            ], 422);
        }

        $unitsInMovement = UnitMovementQueue::where('from_kingdom_id', $kingdom->id)->orWhere('to_kingdom_id', $kingdom->id)->count();
        $buildingsInQueue = BuildingInQueue::where('kingdom_id', $kingdom->id)->where('character_id', auth()->user()->character->id)->count();
        $unitsInQueue = UnitInQueue::where('kingdom_id', $kingdom->id)->where('character_id', auth()->user()->character->id)->count();

        if ($unitsInMovement > 0) {
            return response()->json([
                'message' => 'You either sent units that are currently moving, or an attack is incoming. Either way, there are units in movement from or to this kingdom and you cannot abandon it.',
            ], 422);
        }

        if ($buildingsInQueue > 0) {
            return response()->json([
                'message' => 'You have buildings in queue. You cannot abandon a kingdom when people are hard at work!',
            ], 422);
        }

        if ($unitsInQueue > 0) {
            return response()->json([
                'message' => 'You have units currently training. You cannot abandon a kingdom when people are training to fight for you.',
            ], 422);
        }

        if ($kingdom->gold_bars > 0) {
            return response()->json([
                'message' => 'You cannot abandon a kingdom that has Gold Bars.',
            ], 422);
        }

        $timeout = $kingdom->character->can_settle_again_at;

        if (! is_null($timeout)) {
            return response()->json([
                'message' => 'You cannot abandon this kingdom yet, you have: '.now()->diffInMinutes($timeout).' minutes left before you can settle/purchase/abandon.',
            ], 422);
        }

        $name = $kingdom->name;

        $this->abandonKingdomService->setKingdom($kingdom)->abandon();

        event(new GlobalMessageEvent('The Creator feels for the people of: '.$name.' as their leader selfishly leaves them to fend for themselves.'));

        return response()->json([], 200);
    }
}
