<?php

namespace App\Game\Kingdoms\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\SmeltingProgress;
use App\Game\Kingdoms\Service\UpdateKingdom;
use Facades\App\Flare\Values\UserOnlineValue;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;

class SmeltSteel implements ShouldQueue {

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var int $smeltSteelId
     */
    protected int $smeltSteelId;

    /**
     * Create a new job instance.
     *
     * @param int $smeltSteelId
     */
    public function __construct(int $smeltSteelId) {
        $this->smeltSteelId = $smeltSteelId;
    }

    /**
     * Execute the job.
     *
     * @param UpdateKingdom $updateKingdom
     * @return void
     */
    public function handle(UpdateKingdom $updateKingdom) {

        $queue = SmeltingProgress::find($this->smeltSteelId);

        if (is_null($queue)) {
            return;
        }

        if (!$queue->completed_at->lessThanOrEqualTo(now())) {
            $timeLeft = $queue->completed_at->diffInMinutes(now());

            if ($timeLeft <= 15) {
                $time = now()->addMinutes($timeLeft);
            } else {
                $time = now()->addMinutes(15);
            }

            // @codeCoverageIgnoreStart
            SmeltSteel::dispatch($this->smeltSteelId)->delay($time);

            return;
            // @codeCoverageIgnoreEnd
        }

        $amount = $queue->amount_to_smelt;

        $newAmount = $queue->kingdom->current_steel + $amount;

        if ($newAmount > $queue->kingdom->max_steel) {
            $newAmount = $queue->kingdom->max_steel;
        }

        $queue->kingdom()->update([
            'current_steel' => $newAmount,
        ]);

        $kingdom = $queue->kingdom->refresh();

        $queue->delete();

        $updateKingdom->updateKingdom($kingdom);

        $x       = $kingdom->x_position;
        $y       = $kingdom->y_position;
        $user    = $kingdom->character->user;
        $plane   = $kingdom->gameMap->name;

        if (UserOnlineValue::isOnline($user)) {

            if ($user->show_unit_recruitment_messages) {
                $message = 'kingdom: ' .  $kingdom->name . ' on plane: ' . $plane . ' at: (X/Y) ' . $x . '/' . $y .
                    ' has finished smelting: ' . number_format($amount) . ' of steel and now has: ' .
                    number_format($kingdom->current_steel) . ' steel.';

                ServerMessageHandler::handleMessage($user, 'unit_recruitment_finished', $message);
            }
        }
    }
}
