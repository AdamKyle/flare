<?php

namespace App\Game\Core\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Game\Core\Services\UseItemService;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Flare\Models\CharacterBoon;

class CharacterBoonJob implements ShouldQueue
{
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
    public function __construct(int $characterBoonId)
    {
        $this->characterBoon = $characterBoonId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(UseItemService $useItemService)
    {
        $boon = CharacterBoon::find($this->characterBoon);

        if (is_null($boon)) {
            return;
        }

        $character = $boon->character;

        $boon->delete();

        $useItemService->updateCharacter($character->refresh());

        event(new ServerMessageEvent($character->user, 'A boon has worn off your stats (skills) have been adjusted accordingly.'));
    }
}
