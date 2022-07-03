<?php

namespace App\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\UnitMovementQueue;
use App\Flare\Transformers\BasicKingdomTransformer;
use App\Flare\Transformers\OtherKingdomTransformer;
use App\Game\Kingdoms\Handlers\UpdateKingdomHandler;
use App\Game\Kingdoms\Requests\CancelBuildingRequest;
use App\Game\Kingdoms\Requests\CancelUnitRequest;
use App\Game\Kingdoms\Requests\KingdomUpgradeBuildingRequest;
use App\Game\Kingdoms\Service\KingdomResourcesService;
use App\Game\Kingdoms\Service\KingdomSettleService;
use App\Game\Messages\Events\GlobalMessageEvent;
use Illuminate\Http\Request;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use Facades\App\Game\Kingdoms\Validation\ResourceValidation;
use App\Http\Controllers\Controller;
use App\Game\Core\Events\UpdateTopBarEvent;
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
use App\Game\Kingdoms\Requests\KingdomUnitRecruitmentRequest;
use App\Game\Kingdoms\Requests\PurchaseGoldBarsRequest;
use App\Game\Kingdoms\Requests\PurchasePeopleRequest;
use App\Game\Kingdoms\Requests\WithdrawGoldBarsRequest;
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

    private $kingdomService;

    private KingdomTransformer $kingdomTransformer;

    private UpdateKingdomHandler $updateKingdomHandler;

    private BasicKingdomTransformer $basicKingdomTransformer;

    private KingdomSettleService $kingdomSettleService;

    public function __construct(Manager $manager, KingdomTransformer $kingdomTransformer, KingdomService $kingdomService, UpdateKingdomHandler $updateKingdomHandler, KingdomSettleService $kingdomSettleService)
    {
        $this->middleware('is.character.dead')->except(['getAttackLogs', 'getCharacterInfoForKingdom', 'getOtherKingdomInfo', 'getKingdomsList']);

        $this->manager              = $manager;
        $this->kingdomTransformer   = $kingdomTransformer;
        $this->kingdomService       = $kingdomService;
        $this->updateKingdomHandler = $updateKingdomHandler;
        $this->kingdomSettleService = $kingdomSettleService;
    }

    public function getCharacterInfoForKingdom(Kingdom $kingdom, Character $character) {
        $kingdom = Kingdom::where('character_id', $character->id)->where('id', $kingdom->id)->first();

        if (is_null($kingdom)) {
            return response()->json(['message' => 'Kingdom not found.'], 422);
        }

        $kingdom = new Item($kingdom, $this->basicKingdomTransformer);
        $kingdom = $this->manager->createData($kingdom)->toArray();

        return response()->json($kingdom);
    }

    public function getOtherKingdomInfo(Kingdom $kingdom, OtherKingdomTransformer $otherKingdomTransformer) {
        $kingdom = new Item($kingdom, $otherKingdomTransformer);
        $kingdom = $this->manager->createData($kingdom)->toArray();

        return response()->json($kingdom);
    }

    public function getKingdomsList(Character $character) {
        return response()->json(
            $this->manager->createData(
                new Collection($character->kingdoms, $this->kingdomTransformer)
            )->toArray()
        );
    }

    public function getLocationData(Character $character, Kingdom $kingdom) {
        return response()->json(
            $this->manager->createData(
                new Item($kingdom, $this->kingdomTransformer)
            )->toArray(),
        );
    }

    public function getAttackLogs(User $user) {
        $logs = $user->character->kingdomAttackLogs()->where('published', true)->get();

        return response()->json([
            'logs'         => $logs,
            'character_id' => $user->character->id
        ]);
    }

    public function settle(KingdomsSettleRequest $request, Character $character) {

        $result = $this->kingdomSettleService->settlePreCheck($character, $request->name);

        if (!empty($result)) {
            return response()->json([
                'message' => $result['message']
            ], 422);
        }

        if (!$this->kingdomSettleService->canSettle($character)) {
            return response()->json([
                'message' => $this->kingdomSettleService->getErrorMessage()
            ], 422);
        }

        if (!$this->kingdomSettleService->canAfford()) {
            return response()->json([
                'message' => 'You don\'t have the gold.',
            ], 422);
        }

        if ($this->kingdomSettleService->canAfford($character)) {
            $amount = $character->kingdoms->count() * 10000;

            $character->update([
                'gold' => $character->gold - $amount,
            ]);

            event(new UpdateTopBarEvent($character->refresh()));
        }

        $this->kingdomSettleService->createKingdom($character, $request->name);

        return response()->json($this->kingdomSettleService->addKingdomToMap($character), 200);
    }

    public function rename(KingdomRenameRequest $request, Kingdom $kingdom) {
        $kingdom->update($request->all());

        $character = $kingdom->character->refresh();

        $this->kingdomSettleService->addKingdomToCache($character, $kingdom);

        $this->updateKingdomHandler->refreshPlayersKingdoms($character);

        event(new UpdateGlobalMap($character));
        event(new AddKingdomToMap($character));

        return response()->json();
    }

    public function upgradeKingdomBuilding(KingdomUpgradeBuildingRequest $request, Character $character, KingdomBuilding $building, KingdomBuildingService $buildingService) {

        if ($request->paying_with_gold) {
            $paid = $buildingService->upgradeBuildingWithGold($building, $request->all());

            if ($paid === 0) {
                return response()->json([
                    'message' => 'You cannot afford this upgrade.'
                ], 422);
            }

            $buildingService->processUpgradeWithGold($building, $paid, $request->to_level);
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

        $this->updateKingdomHandler->refreshPlayersKingdoms($character->refresh());

        return response()->json([
            'message' => 'Building is in the process of upgrading!',
        ], 200);
    }

    public function rebuildKingdomBuilding(Character $character, KingdomBuilding $building, KingdomBuildingService $buildingService) {
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

    public function recruitUnits(KingdomUnitRecruitmentRequest $request, Kingdom $kingdom, GameUnit $gameUnit, UnitService $service) {
        if ($request->amount > KingdomMaxValue::MAX_UNIT) {
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

        if ($request->recruitment_type === 'resources') {
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

            $service->updateCharacterGold($kingdom, $gameUnit, $request->amount);

            $newPop = $kingdom->current_population - $amount;

            $kingdom->update([
                'current_population' => $newPop > 0 ? $newPop : 0
            ]);

            $paidGold = true;
        }

        $service->recruitUnits($kingdom, $gameUnit, $request->amount, $paidGold);

        $character = $kingdom->character;

        $this->updateKingdomHandler->refreshPlayersKingdoms($character->refresh());

        return response()->json([
            'message' => 'Your units are being trained by the best of the best!',
        ]);
    }

    public function cancelRecruit(CancelUnitRequest $request, UnitService $service) {

        $queue = UnitInQueue::find($request->queue_id);

        if (is_null($queue)) {
            return response()->json(['message' => 'Invalid Input.'], 422);
        }

        $cancelled = $service->cancelRecruit($queue);

        if (!$cancelled) {
            return response()->json([
                'message' => 'Your units are almost done. You can\'t cancel this late in the process.'
            ], 422);
        }

        return response()->json([
            'message' => 'Your units have been disbanded. You got a % of some of the cost back in either resources or gold.'
        ]);
    }

    public function removeKingdomBuildingFromQueue(CancelBuildingRequest $request, KingdomBuildingService $service) {

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

        return response()->json([
            'message' => 'Building has been removed from queue. Some resources or gold was given back to you based on percentage of time left.'
        ], 200);
    }

    public function embezzle(KingdomEmbezzleRequest $request, Kingdom $kingdom, KingdomService $kingdomService)
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

    public function withdrawGoldBars(WithdrawGoldBarsRequest $request, Kingdom $kingdom)
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
                'message' => 'You either sent units that are currently moving, or an attack is incoming. Either way, there are units in movement from or to this kingdom and you cannot abandon it.'
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
