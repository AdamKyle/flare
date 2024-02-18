<?php

namespace App\Game\CharacterInventory\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Game\CharacterInventory\Services\UseItemService;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Flare\Models\CharacterBoon;

class CharacterBoonJob implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var CharacterBoon $characterBoon
     */
    protected $characterBoon;

    /**
     * Create a new job instance.
     *
     * @param CharacterBoon $characterBoon
     */
    public function __construct(int $characterBoonId) {
        $this->characterBoon = $characterBoonId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(UseItemService $useItemService) {
        $boon = CharacterBoon::find($this->characterBoon);

        if (is_null($boon)) {
            return;
        }

        if (!$boon->complete->lessThanOrEqualTo(now())) {
            $timeLeft = $boon->complete->diffInMinutes(now());

            if ($timeLeft <= 15) {
                $time = now()->addMinutes($timeLeft);
            } else {
                $time = now()->addMinutes(15);
            }

            // @codeCoverageIgnoreStart
            CharacterBoonJob::dispatch(
                $boon->id,
            )->delay($time);

            return;
            // @codeCoverageIgnoreEnd
        }

        $character = $boon->character;

        $boon->delete();

        $useItemService->updateCharacter($character->refresh());

        event(new ServerMessageEvent($character->user, 'A boon has worn off, your stats (skills) have been adjusted accordingly.'));
    }
}
