<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\Character;
use App\Flare\Models\GameBuilding;
use App\Flare\Models\Kingdom;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Gambler\Values\CurrencyValue;
use App\Game\Kingdoms\Values\BuildingCosts;
use Facades\App\Game\Core\Handlers\HandleGoldBarsAsACurrency;

class CapitalCityGoldBarManagementService {

    use ResponseBuilder;

    public function __construct( private readonly UpdateKingdom $updateKingdom){}

    public function fetchGoldBarDetails(Character $character, Kingdom $kingdom, bool $returnResponse = true): array {

        $goblinBank = GameBuilding::where('name', BuildingCosts::GOBLIN_COIN_BANK)->first();

        $kingdoms = $character->kingdoms()
            ->where('id', '!=', $kingdom->id)
            ->where('game_map_id', $kingdom->game_map_id)
            ->whereHas('buildings', function($query) use ($goblinBank) {
                $query->where('game_building_id', $goblinBank->id);
            })
            ->get();

        $allBuildingsLevelFive = $kingdoms->every(function ($kingdom) use ($goblinBank) {
            $building = $kingdom->buildings->firstWhere('game_building_id', $goblinBank->id);
            return $building && $building->level >= 5;
        });

        $data = [
            'total_gold_bars' => $kingdoms->sum('gold_bars'),
            'character_gold' => $character->gold,
            'total_kingdoms' => $kingdoms->count(),
            'goblin_banks_level_five' => $allBuildingsLevelFive,
        ];

        if (!$returnResponse) {
            return $data;
        }

        return $this->successResult([
            'gold_bar_details' => $data,
        ]);
    }

    public function convertGoldBars(Character $character, Kingdom $kingdom, int $goldBars): array {
        $kingdoms = $character->kingdoms()->where('game_map_id', $kingdom->game_map_id)
            ->where('id', '!=', $kingdom->id)
            ->whereRaw('(SELECT SUM(gold_bars) FROM kingdoms WHERE gold_bars > 0) >= ?', [$goldBars])
            ->groupBy('kingdoms.id', 'kingdoms.character_id', 'kingdoms.name')
            ->selectRaw('*, SUM(gold_bars) as gold_bars_sum')
            ->get();

        $canAfford = HandleGoldBarsAsACurrency::hasTheGoldBars($kingdoms, $goldBars);

        if (!$canAfford) {
            return $this->errorResult('Not enough gold bars. Go slay monsters to stalk your treasury.');
        }

        $convertedAmount = $goldBars * 2000000000;

        if ($convertedAmount > MaxCurrenciesValue::MAX_GOLD) {
            return $this->errorResult('This would exceed the max amount of gold you can have.');
        }

        $newGold = $character->gold + $convertedAmount;

        if ($newGold > MaxCurrenciesValue::MAX_GOLD) {
            return $this->errorResult('You would waste gold child. Cannot withdraw that amount.');
        }

        $character->update([
            'gold' => $newGold,
        ]);

        HandleGoldBarsAsACurrency::subtractCostFromKingdoms($kingdoms, $goldBars);

        $character = $character->refresh();

        event(new UpdateTopBarEvent($character));

        $this->updateKingdom->updateKingdomAllKingdoms($character);

        return $this->successResult([
            'message' => 'Withdrew: ' . number_format($goldBars) . ' Gold Bars.',
            'gold_bar_details' => $this->fetchGoldBarDetails($character, $kingdom, false),
        ]);
    }

    public function depositGoldBars(Character $character, Kingdom $kingdom, int $amountToPurchase): array {
        $cost = 2000000000 * $amountToPurchase;

        if ($cost > $character->gold) {
            return $this->errorResult('Far too much gold is required. You do not have enough.');
        }

        $kingdoms = $character->kingdoms()->where('game_map_id', $kingdom->game_map_id)->where('id', '!=', $kingdom->id)->get();

        $allowedGoldBars = $kingdoms->count() * 1000;
        $currentGoldBars = $kingdoms->sum('gold_bars');

        if ($amountToPurchase > $allowedGoldBars) {
            return $this->errorResult('You are only allowed to have: ' . number_format($allowedGoldBars) . ' total Gold Bars. Settle more kingdoms or spend some.');
        }

        $newAmount = $currentGoldBars + $amountToPurchase;

        if ($newAmount > $allowedGoldBars) {
            return $this->errorResult('You are only allowed to have: ' . number_format($allowedGoldBars) . ' total Gold Bars. Settle more kingdoms or spend some.');
        }

        HandleGoldBarsAsACurrency::addGoldBarsToKingdoms($kingdoms, $amountToPurchase);

        $character->update([
            'gold' => $character->gold - $cost,
        ]);

        $character = $character->refresh();

        event(new UpdateTopBarEvent($character));

        $this->updateKingdom->updateKingdomAllKingdoms($character);

        return $this->successResult([
            'message' => 'Deposited: ' . number_format($amountToPurchase) . ' Gold Bars.',
            'gold_bar_details' => $this->fetchGoldBarDetails($character, $kingdom, false),
        ]);
    }
}
