<?php

namespace App\Game\Kingdoms\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Kingdom;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Kingdoms\Service\KingdomService;
use App\Game\Messages\Events\ServerMessageEvent;

class MassEmbezzle implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $kingdom;

    public $amount;

    public $final;

    public function __construct(Kingdom $kingdom, int $amount, bool $final = false) {
        $this->kingdom = $kingdom;
        $this->amount  = $amount;
        $this->final   = $final;
    }

    public function handle(KingdomService $kingdomService) {
        $character = $this->kingdom->character;

        if (!$character->is_mass_embezzling) {
            return;
        }

        $newAGoldAmount   = $character->gold + $this->amount;

        $maxCurrencies = new MaxCurrenciesValue($newAGoldAmount, MaxCurrenciesValue::GOLD);

        if ($maxCurrencies->canNotGiveCurrency()) {
            $kingdom = $this->kingdom;

            $character->update([
                'is_mass_embezzling' => false,
            ]);

            $message = 'Stopping!: ' . $kingdom->name . ' At: (X/Y)' . $kingdom->x_position . '/' . $kingdom->y_position .
                ' On the ' . $kingdom->gameMap->name . ' Plane. Reason: Embezzling would waste gold.';

            event(new ServerMessageEvent($character->user, $message));

            return;
        }

        if ($this->kingdom->current_morale <= 0.15) {
            $kingdom = $this->kingdom;
            $message = 'Skipping: ' . $kingdom->name . ' At: (X/Y)' . $kingdom->x_position . '/' . $kingdom->y_position .
                ' On the ' . $kingdom->gameMap->name . ' Plane. Reason: Morale too low: ' . $kingdom->current_morale;

            event(new ServerMessageEvent($character->user, $message));

            return;
        }

        $kingdom = $this->kingdom;

        $kingdomService->embezzleFromKingdom($this->kingdom, $this->amount);

        $kingdom = $kingdom->refresh();

        $message = 'Embezzled!: ' . $kingdom->name . ' At: (X/Y)' . $kingdom->x_position . '/' . $kingdom->y_position .
            ' On the ' . $kingdom->gameMap->name . ' Plane. Amount: ' . $this->amount . ' You\'re new amount: ' . $newAGoldAmount .
         '. Morale has been reduced 15% to: ' . ($kingdom->current_morale * 100) . '%';

        event(new ServerMessageEvent($character->user, $message));

        if ($this->final) {
            $message = 'Embezzling has finished.';

            event(new ServerMessageEvent($character->user, $message));
        }
    }
}
