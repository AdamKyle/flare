<?php

namespace App\Game\Kingdoms\Jobs;

use App\Flare\Models\Character;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Kingdom;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Kingdoms\Service\KingdomService;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Support\Collection;

class MassEmbezzle implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $character;

    public $amount;

    public function __construct(Character $character, int $amount) {
        $this->character = $character;
        $this->amount    = $amount;
    }

    public function handle(KingdomService $kingdomService) {

        if (!$this->character->is_mass_embezzling) {
            return;
        }

        $mapId          = $this->character->map->game_map_id;

        $kingdomsForMap = $this->character->kingdoms()->where('game_map_id', $mapId)->orderBy('id')->get();

        if ($kingdomsForMap->isEmpty()) {
            event(new ServerMessageEvent($this->character->user, 'You have no kingdoms on this plane ...'));

            $this->character->update([
                'is_mass_embezzling' => false,
            ]);
        }

        foreach ($kingdomsForMap as $kingdom) {

            if ($this->cannotGiveCurrency($kingdom)) {
                return;
            }

            $isLastKingdom = $this->isFinalKingdom($kingdomsForMap, $kingdom);

            if ($this->skipForMorale($kingdom)) {

                if ($isLastKingdom) {
                    return $this->hasFinished();
                }

                continue;
            }

            if ($this->skipForLowTreasury($kingdom)) {

                if ($isLastKingdom) {
                    return $this->hasFinished();
                }

                continue;
            }

            $this->embezzle($kingdomService, $kingdom);

            if ($isLastKingdom) {
                return $this->hasFinished();
            }
        }
    }

    protected function isFinalKingdom(Collection $kingdoms, Kingdom $kingdom) {
        return $kingdoms->last()->id === $kingdom->id;
    }

    protected function cannotGiveCurrency(Kingdom $kingdom) {
        $newGoldAmount = $this->character->gold + $this->amount;
        $maxCurrencies = new MaxCurrenciesValue($newGoldAmount, MaxCurrenciesValue::GOLD);

        if ($maxCurrencies->canNotGiveCurrency()) {
            $kingdom = $this->kingdom;

            $this->character->update([
                'is_mass_embezzling' => false,
            ]);

            $message = 'Stopping!: ' . $kingdom->name . ' At: (X/Y) ' . $kingdom->x_position . '/' . $kingdom->y_position .
                ' On the ' . $kingdom->gameMap->name . ' Plane. Reason: Embezzling would waste gold.';

            event(new ServerMessageEvent($character->user, $message));

            return true;
        }

        return false;
    }

    protected function skipForMorale(Kingdom $kingdom) {
        if ($kingdom->current_morale <= 0.15) {

            $message = 'Skipping: ' . $kingdom->name . ' At: (X/Y) ' . $kingdom->x_position . '/' . $kingdom->y_position .
                ' On the ' . $kingdom->gameMap->name . ' Plane. Reason: Morale too low: ' . $kingdom->current_morale;

            event(new ServerMessageEvent($this->character->user, $message));

            return true;
        }

        return false;
    }

    protected function skipForLowTreasury(Kingdom $kingdom) {
        $newTreasuryAmount = $kingdom->treasury - $this->amount;

        if ($newTreasuryAmount < 0) {
            $message = 'Skipping: ' . $kingdom->name . ' At: (X/Y) ' . $kingdom->x_position . '/' . $kingdom->y_position .
                ' On the ' . $kingdom->gameMap->name . ' Plane. Reason: No gold to embezzle';

            event(new ServerMessageEvent($this->character->user, $message));

            return true;
        }

        return false;
    }

    protected function embezzle(KingdomService $kingdomService, Kingdom $kingdom) {

        $characterGold = $this->character->gold + $this->amount;
        $amountLeft    = $kingdom->treasury - $this->amount;

        $kingdomService->embezzleFromKingdom($kingdom, $this->amount);

        $kingdom = $kingdom->refresh();

        $message = 'Embezzled!: ' . $kingdom->name . ' At: (X/Y) ' . $kingdom->x_position . '/' . $kingdom->y_position .
            ' On the ' . $kingdom->gameMap->name . ' Plane. Amount: ' . number_format($this->amount) . ' You\'re new gold: ' . number_format($characterGold) .
            '. Kingdom Gold Left: '.number_format($amountLeft).' Morale has been reduced 15% to: ' . ($kingdom->current_morale * 100) . '%';

        event(new ServerMessageEvent($this->character->user, $message));
    }

    protected function hasFinished() {
        $message = 'Embezzling has finished.';

        $this->character->update([
            'is_mass_embezzling' => false,
        ]);

        event(new ServerMessageEvent($this->character->user, $message));
    }
}
