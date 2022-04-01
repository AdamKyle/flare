<?php

namespace App\Game\Core\Jobs;

use Cache;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Flare\Models\AdventureLog;
use App\Game\Adventures\Events\UpdateAdventureLogsBroadcastEvent;
use App\Game\Core\Services\AdventureRewardService;
use App\Game\Messages\Events\ServerMessageEvent;

class HandleAdventureRewards implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Character $character
     */
    protected $character;

    protected $adventureLog;

    protected $rewards;

    /**
     * Create a new job instance.
     *
     * @param Character $character
     * @return void
     */
    public function __construct(Character $character, AdventureLog $adventureLog, array $rewards) {
        $this->character    = $character;
        $this->rewards      = $rewards;
        $this->adventureLog = $adventureLog;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(AdventureRewardService $adventureRewardService) {
        Cache::put('character-adventure-rewards-' . $this->character->id, 'processing ...');

        $adventureRewardService->distributeRewards($this->rewards, $this->character, $this->adventureLog);

        $character = $this->character->refresh();

        $character->update([
            'current_adventure_id' => null,
        ]);

        $this->adventureLog->update([
            'rewards' => null,
        ]);

        $character = $character->refresh();

        event(new UpdateAdventureLogsBroadcastEvent($character->adventureLogs, $character->user));

        Cache::delete('character-adventure-rewards-' . $this->character->id);

        event(new UpdateTopBarEvent($character));

        event(new ServerMessageEvent($character->user, 'Xp, Skill XP, Currencies have all been rewarded. You can start another adventure while we process your items or you can wait if you please. Keep an eye on Server tab for item updates.'));
    }
}
