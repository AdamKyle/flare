<?php

namespace App\Game\Adventures\Jobs;

use App\Game\Core\Events\UpdateTopBarEvent;
use App\Flare\Mail\GenericMail;
use App\Flare\Models\Adventure;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterSnapShot;
use App\Flare\Models\User;
use App\Game\Adventures\Builders\RewardBuilder;
use App\Game\Adventures\Services\AdventureService;
use Cache;
use Mail;

class ProcessAdventure implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $characterId;

    protected $adventureId;

    protected $currentLevel;

    protected $attackType;

    /**
     * Create a new job instance.
     *
     * @param Character $character
     * @param Adventure $adventure
     * @param string $name
     * @param int $currentLevel
     * @return void
     */
    public function __construct(
        int $characterId,
        int $adventureId,
        int $currentLevel,
        string $attackType
    ) {
        $this->characterId        = $characterId;
        $this->adventureId        = $adventureId;
        $this->attackType         = $attackType;
        $this->currentLevel       = $currentLevel;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(AdventureService $adventureService)
    {
        $adventure = Adventure::find($this->adventureId);
        $character = Character::find($this->characterId);

        $adventureService = $adventureService->setAdventure($adventure)
                                             ->setCharacter($character);

        $adventureService->processAdventure($this->currentLevel, $adventure->levels, $this->attackType);
    }
}
