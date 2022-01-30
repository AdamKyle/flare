<?php

namespace App\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\UnitMovementQueue;
use App\Game\Kingdoms\Service\KingdomResourcesService;
use App\Game\Messages\Events\GlobalMessageEvent;
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
use App\Flare\Models\User;
use App\Flare\Transformers\KingdomTransformer;
use App\Flare\Jobs\CoreJob;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Kingdoms\Jobs\MassEmbezzle;
use App\Game\Kingdoms\Requests\KingdomDepositRequest;
use App\Game\Kingdoms\Requests\KingdomUnitRecrutmentRequest;
use App\Game\Kingdoms\Requests\PurchaseGoldBarsRequest;
use App\Game\Kingdoms\Requests\PurchasePeopleRequest;
use App\Game\Kingdoms\Requests\WithrawGoldBarsRequest;
use App\Game\Kingdoms\Values\KingdomMaxValue;
use App\Game\Kingdoms\Values\UnitCosts;
use App\Game\Kingdoms\Requests\KingdomRenameRequest;
use App\Game\Kingdoms\Requests\KingdomsSettleRequest;
use App\Game\Kingdoms\Service\KingdomBuildingService;
use App\Game\Kingdoms\Service\KingdomService;
use App\Game\Kingdoms\Service\UnitService;
use App\Game\Kingdoms\Events\UpdateKingdom;
use App\Game\Kingdoms\Requests\KingdomEmbezzleRequest;
use App\Game\Kingdoms\Events\AddKingdomToMap;
use App\Game\Kingdoms\Events\UpdateGlobalMap;
use App\Game\Messages\Events\ServerMessageEvent;

class KingdomsController extends Controller
{

    private $manager;

    private $kingdom;

    private $kingdomService;

    public function __construct(Manager $manager, KingdomTransformer $kingdom, KingdomService $kingdomService)
    {
        $this->middleware('is.character.dead')->except('getAttackLogs');

        $this->manager = $manager;
        $this->kingdom = $kingdom;
        $this->kingdomService = $kingdomService;
    }

    public function getLocationData(Character $character, Kingdom $kingdom)
    {
        $kingdom = new Item($kingdom, $this->kingdom);

        return response()->json(
            $this->manager->createData($kingdom)->toArray(),
            200
        );
    }

    public function getAttackLogs(User $user)
    {
        $logs = $user->character->kingdomAttackLogs()->where('published', true)->get();

        return response()->json([
            'logs' => $logs,
            'character_id' => $user->character->id
        ], 200);
    }

    public function settle(KingdomsSettleRequest $request, Character $character, KingdomService $kingdomService)
    {

        if (!is_null($character->can_settle_again_at)) {

            return response()->json([
                'message' => 'You can settle another kingdom in: ' . now()->diffInMinutes($character->can_settle_again_at) . ' Minutes.'
            ], 200);
        }

        if ($character->map->gameMap->mapType()->isPurgatory()) {
            return response()->json([
                'message' => 'Child, this is not place to be a King or Queen, The Creator would destroy anything you build down here.'
            ], 200);
        }

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

    public function rename(KingdomRenameRequest $request, Kingdom $kingdom, KingdomService $kingdomService)
    {
        $kingdom->update($request->all());

        $character = $kingdom->character;

        $kingdomService->addKingdomToCache($kingdom->character, $kingdom);

        $kingdomData = new Item($kingdom->refresh(), $this->kingdom);

        $kingdomData = $this->manager->createData($kingdomData)->toArray();

        $character = $character->refresh();

        event(new UpdateKingdom($character->user, $kingdomData));
        event(new UpdateGlobalMap($character));
        event(new AddKingdomToMap($character));

        return response()->json([], 200);
    }

    public function upgradeKingdomBuilding(Request $request, Character $character, KingdomBuilding $building, KingdomBuildingService $buildingService)
    {

        if ($request->paying_with_gold) {
            $request->validate([
                'cost_to_upgrade' => 'required|integer',
                'how_many_levels' => 'required|integer',
                'pop_required' => 'required|integer',
                'time' => 'required|integer',
            ]);

            $paid = $buildingService->upgradeBuildingWithGold($building, $request->all());


            if (!$paid) {
                return response()->json([
                    'message' => 'You cannot afford this upgrade.'
                ], 422);
            }

            $buildingService->processUpgradeWithGold($building, $request->all());
        } else {
            if (ResourceValidation::shouldRedirectKingdomBuilding($building, $building->kingdom)) {
                return response()->json([
                    'message' => "You don't have the resources."
                ], 422);
            }

            if ($building->level + 1 > $building->gameBuilding->max_level) {
                return response()->json([
                    'message' => 'Building is already max level.'
                ], 422);
            }

            $buildingService->updateKingdomResourcesForKingdomBuildingUpgrade($building);

            $buildingService->upgradeKingdomBuilding($building, $character);
        }

        $kingdom = $building->kingdom;
        $kingdom = new Item($kingdom->refresh(), $this->kingdom);
        $kingdom = $this->manager->createData($kingdom)->toArray();

        event(new UpdateKingdom($character->user, $kingdom));

        return response()->json([], 200);
    }

    public function rebuildKingdomBuilding(Character $character, KingdomBuilding $building, KingdomBuildingService $buildingService)
    {
        if (ResourceValidation::shouldRedirectRebuildKingdomBuilding($building, $building->kingdom)) {
            return response()->json([
                'message' => "You don't have the resources."
            ], 422);
        }

        $kingdom = $buildingService->updateKingdomResourcesForRebuildKingdomBuilding($building);

        $buildingService->rebuildKingdomBuilding($building, $character);

        $kingdom = new Item($kingdom, $this->kingdom);

        $kingdom = $this->manager->createData($kingdom)->toArray();

        event(new UpdateKingdom($character->user, $kingdom));

        return response()->json([], 200);
    }

    public function recruitUnits(KingdomUnitRecrutmentRequest $request, Kingdom $kingdom, GameUnit $gameUnit, UnitService $service)
    {
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
                'message' => 'Too few units to recruit.'
            ], 422);
        }


        $paidGold = false;

        if ($request->recruitment_type === 'recruit-normally') {
            if (ResourceValidation::shouldRedirectUnits($gameUnit, $kingdom, $request->amount)) {
                return response()->json([
                    'message' => "You don't have the resources."
                ], 422);
            }

            $service->updateKingdomResources($kingdom, $gameUnit, $request->amount);
        } else {

            $amount              = $gameUnit->required_population * $request->amount;
            $populationReduction = $kingdom->fetchPopulationCostReduction();

            $amount = ceil($amount - $amount * $populationReduction);

            if ($amount > $kingdom->current_population) {
                return response()->json([
                    'message' => "You do not have enough population to purchase with gold alone."
                ], 422);
            }

            $newAmount = $kingdom->current_population - $amount;

            if ($newAmount < 0) {
                $newAmount = 0;
            }

            $service->updateCharacterGold($kingdom, $gameUnit, $request->amount);

            $kingdom->update([
                'current_population' => $newAmount
            ]);

            $paidGold = true;
        }

        $service->recruitUnits($kingdom, $gameUnit, $request->amount, $paidGold);

        $character = $kingdom->character;

        $kingdom = new Item($kingdom, $this->kingdom);

        $kingdom = $this->manager->createData($kingdom)->toArray();

        event(new UpdateKingdom($character->user, $kingdom));

        return response()->json($kingdom, 200);
    }

    public function cancelRecruit(Request $request, UnitService $service)
    {
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

    public function removeKingdomBuildingFromQueue(Request $request, KingdomBuildingService $service)
    {

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

    public function embezzle(KingdomEmbezzleRequest $request, Kingdom $kingdom, KingdomService $kingdomService)
    {
        $amountToEmbezzle = $request->embezzle_amount;
        $newAGoldAmount = $kingdom->character->gold + $amountToEmbezzle;

        $maxCurrencies = new MaxCurrenciesValue($newAGoldAmount, MaxCurrenciesValue::GOLD);

        if ($maxCurrencies->canNotGiveCurrency()) {
            return response()->json([
                'message' => number_format($amountToEmbezzle) . ' Would yput you well over the gold cap limit.'
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

        $kingdomService->embezzleFromKingdom($kingdom, $amountToEmbezzle);

        return response()->json([], 200);
    }

    public function massEmbezzle(KingdomEmbezzleRequest $request, Character $character)
    {

        $character->update([
            'is_mass_embezzling' => true
        ]);

        MassEmbezzle::dispatch($character, $request->embezzle_amount)->delay(now()->addSeconds(5))->onConnection('long_running');

        event(new ServerMessageEvent($character->user, 'Mass Embezzling underway...'));

        return response()->json([], 200);
    }

    public function deposit(KingdomDepositRequest $request, Kingdom $kingdom)
    {
        $amountToDeposit = $request->deposit_amount;

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

        $newMorale = $kingdom->current_morale + 0.15;

        if ($newMorale > 1) {
            $newMorale = 1;
        }

        $kingdom->update([
            'treasury' => $kingdom->treasury + $amountToDeposit,
            'current_morale' => $newMorale,
        ]);

        $character = $kingdom->character;

        $character->update([
            'gold' => $character->gold - $amountToDeposit
        ]);

        $kingdom = new Item($kingdom->refresh(), $this->kingdom);

        $kingdom = $this->manager->createData($kingdom)->toArray();

        event(new UpdateTopBarEvent($character->refresh()));
        event(new UpdateKingdom($character->user, $kingdom));

        return response()->json([], 200);
    }

    public function purchasePeople(PurchasePeopleRequest $request, Kingdom $kingdom)
    {
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

    public function purchaseGoldBars(PurchaseGoldBarsRequest $request, Kingdom $kingdom)
    {
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

    public function withdrawGoldBars(WithrawGoldBarsRequest $request, Kingdom $kingdom)
    {
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

        $kingdom = new Item($kingdom->refresh(), $this->kingdom);

        $kingdom = $this->manager->createData($kingdom)->toArray();

        event(new UpdateTopBarEvent($character->refresh()));
        event(new UpdateKingdom($character->user, $kingdom));

        return response()->json([
            'message' => 'Exchanged: ' . $amount . ' Gold bars for: ' . $totalGold . ' Gold!',
        ], 200);
    }

    public function abandon(Kingdom $kingdom, KingdomResourcesService $kingdomResourceServer) {
        if ($kingdom->character->id !== auth()->user()->character->id) {
            return response()->json([
                'message' => 'Invalid Input. Not allowed to do that.'
            ], 422);
        }

        $unitsInMovement = UnitMovementQueue::where('from_kingdom_id', $kingdom->id)->orWhere('to_kingdom_id', $kingdom->id)->get();

        if ($unitsInMovement->isNotEmpty()) {
            return response()->json([
                'message' => 'You either sent units, that in movement, or an attack is incoming. Either way there is units in movement from or to this kingdom and you cannot abandon it.'
            ], 422);
        }

        if ($kingdom->gold_bars > 0) {
            return response()->json([
                'message' => 'You cannot abandon a kingdom that has Gold Bars.'
            ], 422);
        }

        $kingdomResourceServer->abandonKingdom($kingdom);

        event(new GlobalMessageEvent('The Creator feels for the people of: ' . $kingdom->name . ' as their leader selfishly leaves them to fend for themselves.'));

        return response()->json([], 200);
    }
}
