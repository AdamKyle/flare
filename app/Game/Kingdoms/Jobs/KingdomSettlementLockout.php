<?php

namespace App\Game\Kingdoms\Jobs;

use App\Flare\Models\Character;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\User;
use App\Game\Kingdoms\Handlers\GiveKingdomsToNpcHandler;

class KingdomSettlementLockout implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $character;

    public function __construct(Character $character) {
        $this->character = $character;
    }

    public function handle() {
        if (is_null($this->character->can_settle_again_at)) {
            return;
        }

        if (!$this->character->can_settle_again_at->lessThanOrEqualTo(now())) {
            $timeLeft = $this->character->can_settle_again_at->diffInMinutes(now());

            if ($timeLeft <= 15) {
                $time = now()->addMinutes($timeLeft);
            } else {
                $time = now()->addMinutes(15);
            }

            // @codeCoverageIgnoreStart
            KingdomSettlementLockout::dispatch($this->character)->delay($time);

            return;
            // @codeCoverageIgnoreEnd
        }

        $this->character->update([
            'can_settle_again_at' => null,
        ]);

        event(new ServerMessageEvent($this->character->user, 'You may now settle a new kingdom. Your "lockout" is ended.'));
    }
}
