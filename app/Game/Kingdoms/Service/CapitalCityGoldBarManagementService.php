<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Gambler\Values\CurrencyValue;
use Facades\App\Game\Core\Handlers\HandleGoldBarsAsACurrency;

class CapitalCityGoldBarManagementService {

    use ResponseBuilder;

    public function __construct( private readonly UpdateKingdom $updateKingdom){}

    public function fetchGoldBarDetails(Character $character, Kingdom $kingdom): array {

        $kingdoms = $character->kingdoms()->where('game_map_id', $kingdom->game_map_id)->get();

        $data = [
            'total_gold_bars' => $kingdoms->sum('gold_bars'),
            'character_gold' => $character->gold,
        ];

        return $this->successResult([
            'gold_bar_details' => $data,
        ]);
    }

    public function convertGoldBars(Character $character, Kingdom $kingdom, int $goldBars): array {
        $kingdoms = $character->kingdoms()->where('game_map_id', $kingdom->game_map_id)
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
    }

}
