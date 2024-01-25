<?php

namespace App\Console\AfterDeployment;

use App\Flare\Models\UnitInQueue;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Kingdoms\Values\KingdomMaxValue;
use Illuminate\Console\Command;

class ReduceUnitQueueAmount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reduce:unit-queue-amount';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'reduce unit queue amount';

    /**
     * Execute the console command.
     */
    public function handle() {
        UnitInQueue::chunkById(250, function($queues) {
            foreach ($queues as $queue) {
                if ($queue->amount > KingdomMaxValue::MAX_UNIT) {

                    $characterGold = $queue->character->gold;

                    $newGold = $characterGold + $queue->gold_paid;

                    if ($newGold > MaxCurrenciesValue::MAX_GOLD) {
                        $newGold = MaxCurrenciesValue::MAX_GOLD;
                    }

                    $queue->character()->update([
                        'gold' => $newGold,
                    ]);

                    $queue->update([
                        'amount' => KingdomMaxValue::MAX_UNIT,
                        'gold_paid' => null,
                    ]);
                }
            }
        });
    }
}
