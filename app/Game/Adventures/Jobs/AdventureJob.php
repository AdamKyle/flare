<?php

namespace App\Game\Adventures\Jobs;

use App\Flare\Mail\GenericMail;
use App\Flare\Models\Adventure;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterSnapShot;
use App\Flare\Models\User;
use App\Game\Adventures\Builders\RewardBuilder;
use App\Game\Adventures\Services\AdventureService;
use Cache;
use Mail;

class AdventureJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Character $character
     */
    protected $character;

    /**
     * @var Adventure $adventure
     */
    protected $adventure;

    /**
     * @var string $name
     */
    protected $name;

    /**
     * @var int $currentLevel
     */
    protected $currentLevel;

    /**
     * Create a new job instance.
     *
     * @param Character $character
     * @param Adventure $adventure
     * @param string $name
     * @param int $curentLevel
     * @return void
     */
    public function __construct(
        Character $character,
        Adventure $adventure,
        string $name,
        int $currentLevel,
    ) {
        $this->character          = $character;
        $this->adventure          = $adventure;
        $this->name               = $name;
        $this->currentLevel       = $currentLevel;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(RewardBuilder $rewardBuilder)
    {
        $name = Cache::get('character_'.$this->character->id.'_adventure_'.$this->adventure->id);

        if (is_null($name) || $name !== $this->name) {
            return;
        }

        $adventureService = resolve(AdventureService::class, [
            'character'           => $this->character->refresh(),
            'adventure'           => $this->adventure,
            'rewardBuilder'       => $rewardBuilder,
            'name'                => $this->name
        ]);

        $adventureService->processAdventure($this->currentLevel, $this->adventure->levels);

        if ($this->currentLevel === $this->adventure->levels) {
            Cache::forget('character_'.$this->character->id.'_adventure_'.$this->adventure->id);
        }
    }
}
