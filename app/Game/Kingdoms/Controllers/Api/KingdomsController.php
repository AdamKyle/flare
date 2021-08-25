<?php

namespace App\Game\Kingdoms\Controllers\Api;

use App\Game\Kingdoms\Values\KingdomMaxValue;
use Illuminate\Http\Request;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use Facades\App\Game\Kingdoms\Validation\ResourceValidation;
use App\Http\Controllers\Controller;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\KingdomBuilding;
use App\Flare\Models\Character;
use App\Flare\Models\GameUnit;
use App\Flare\Models\Kingdom;
use App\Flare\Models\UnitInQueue;
use App\Flare\Transformers\KingdomTransformer;
use App\Game\Kingdoms\Requests\KingdomRenameRequest;
use App\Game\Kingdoms\Requests\KingdomsSettleRequest;
use App\Game\Kingdoms\Service\KingdomBuildingService;
use App\Game\Kingdoms\Service\KingdomService;
use App\Game\Kingdoms\Service\UnitService;
use App\Game\Kingdoms\Events\UpdateKingdom;
use App\Game\Kingdoms\Requests\KingdomEmbezzelRequest;

class KingdomsController extends Controller {

    private $manager;

    private $kingdom;

    public function __construct(Manager $manager, KingdomTransformer $kingdom) {
        $this->middleware('is.character.dead');;

        $this->manager = $manager;
        $this->kingdom = $kingdom;
    }

    public function getLocationData(Character $character, Kingdom $kingdom) {
        $kingdom  = new Item($kingdom, $this->kingdom);

        return response()->json(
            $this->manager->createData($kingdom)->toArray(),
            200
        );
    }

    public function settle(KingdomsSettleRequest $request, Character $character, KingdomService $kingdomService) {
        $kingdom = Kingdom::where('name', $request->name)->where('game_map_id', $character->map->game_map_id)->first();

        if (!is_null($kingdom)) {
            return response()->json(['message' => 'Name is taken'], 422);
        }

        $kingdomService->setParams($request->all(), $character);

        if (!$kingdomService->canSettle($request->x_position, $request->y_position, $character)) {
            return response()->json([
                'message' => 'Cannot settle here.'
            ], 200);
        }

        if ($kingdomService->canAfford($request->kingdom_amount, $character)) {
            $amount = $request->kingdom_amount * 10000;

            $character->update([
                'gold' => $character->gold - $amount,
            ]);

            event(new UpdateTopBarEvent($character->refresh()));
        } else {
            return response()->json([
                'message' => 'You don\'t have the gold.',
            ], 200);
        }

        $kingdomService->createKingdom($character);

        return response()->json($kingdomService->addKingdomToMap($character), 200);
    }

    public function rename(KingdomRenameRequest $request, Kingdom $kingdom, KingdomService $kingdomService) {
        $kingdom->update($request->all());

        $kingdomData  = new Item($kingdom->refresh(), $this->kingdom);

        $kingdomData  = $this->manager->createData($kingdomData)->toArray();

        event(new UpdateKingdom($kingdom->character->user, $kingdomData));

        return response()->json([], 200);
    }

    public function upgradeKingdomBuilding(Character $character, KingdomBuilding $building, KingdomBuildingService $buildingService) {

        if (ResourceValidation::shouldRedirectKingdomBuilding($building, $building->kingdom)) {
            return response()->json([
                'message' => "You don't have the resources."
            ], 422);
        }

        if ($building->level + 1 > $building->gameBuilding->max_level) {
            return response()->json([
                'message' => "Building is already max level."
            ], 422);
        }

        $kingdom = $buildingService->updateKingdomResourcesForKingdomBuildingUpgrade($building);

        $buildingService->upgradeKingdomBuilding($building, $character);

        $kingdom  = new Item($kingdom, $this->kingdom);

        $kingdom  = $this->manager->createData($kingdom)->toArray();

        event(new UpdateKingdom($character->user, $kingdom));

        return response()->json([], 200);
    }

    public function rebuildKingdomBuilding(Character $character, KingdomBuilding $building, KingdomBuildingService $buildingService) {
        if (ResourceValidation::shouldRedirectRebuildKingdomBuilding($building, $building->kingdom)) {
            return response()->json([
                'message' => "You don't have the resources."
            ], 422);
        }

        $kingdom = $buildingService->updateKingdomResourcesForRebuildKingdomBuilding($building, $character);

        $buildingService->rebuildKingdomBuilding($building, $character);

        $kingdom  = new Item($kingdom, $this->kingdom);

        $kingdom  = $this->manager->createData($kingdom)->toArray();

        event(new UpdateKingdom($character->user, $kingdom));

        return response()->json([], 200);
    }

    public function recruitUnits(Request $request, Kingdom $kingdom, GameUnit $gameUnit, UnitService $service) {
        $request->validate([
            'amount' => 'required|integer',
        ]);

        if ($request->amount > KingdomMaxValue::MAX_UNIT) {
            return response()->json([
                'message' => 'Too many units'
            ], 422);
        }

        $currentAmount = $kingdom->units()->where('game_unit_id', $gameUnit->id)->sum('amount');

        if ($currentAmount >= KingdomMaxValue::MAX_UNIT) {
            return response()->json([
                'message' => 'Too many units'
            ], 422);
        }

        if ($request->amount <= 0) {
            return response()->json([
                'message' => "Too few units to recruit."
            ], 422);
        }

        if (ResourceValidation::shouldRedirectUnits($gameUnit, $kingdom, $request->amount)) {
            return response()->json([
                'message' => "You don't have the resources."
            ], 422);
        }

        $service->updateKingdomResources($kingdom, $gameUnit, $request->amount);

        $service->recruitUnits($kingdom, $gameUnit, $request->amount);

        $character = $kingdom->character;

        $kingdom  = new Item($kingdom, $this->kingdom);

        $kingdom  = $this->manager->createData($kingdom)->toArray();

        event(new UpdateKingdom($character->user, $kingdom));

        return response()->json($kingdom, 200);
    }

    public function cancelRecruit(Request $request, UnitService $service) {
        $request->validate([
            'queue_id' => 'required|integer',
        ]);

        $queue = UnitInQueue::find($request->queue_id);

        if (is_null($queue)) {
            return response()->json(['message' => 'Invalid Input.'], 422);
        }

        $cancelled = $service->cancelRecruit($queue, $this->manager, $this->kingdom);

        if (!$cancelled) {
            return response()->json([
                'message' => 'Your units are almost done. You can\'t cancel this late in the process.'
            ], 422);
        }

        return response()->json([], 200);
    }

    public function removeKingdomBuildingFromQueue(Request $request, KingdomBuildingService $service) {

        $request->validate([
            'queue_id' => 'required|integer',
        ]);

        $queue = BuildingInQueue::find($request->queue_id);

        if (is_null($queue)) {
            return response()->json(['message' => 'Invalid Input.'], 422);
        }

        $canceled = $service->cancelKingdomBuildingUpgrade($queue, $this->manager, $this->kingdom);

        if (!$canceled) {
            return response()->json([
                'message' => 'Your workers are almost done. You can\'t cancel this late in the process.'
            ], 422);
        }

        return response()->json([], 200);
    }

    public function embezzel(KingdomEmbezzelRequest $request, Kingdom $kingdom) {
        $amountToEmbezzel = $request->embezzel_amount;

        if ($amountToEmbezzel > $kingdom->treasury) {
            return response()->json([
                'message' => "You don't have the gold in your treasury."
            ], 422);
        }

        if ($kingdom->current_morale <= 0.15) {
            return response()->json([
                'message' => "Morale is too low."
            ], 422);
        }

        $kingdom->update([
            'treasury' => $kingdom->treasury - $amountToEmbezzel,
            'current_morale' => $kingdom->current_morale - 0.15,
        ]);

        $character = $kingdom->character;

        $character->update([
            'gold' => $character->gold + $amountToEmbezzel
        ]);

        $kingdom  = new Item($kingdom->refresh(), $this->kingdom);

        $kingdom  = $this->manager->createData($kingdom)->toArray();

        event(new UpdateTopBarEvent($character->refresh()));
        event(new UpdateKingdom($character->user, $kingdom));
    }
}
