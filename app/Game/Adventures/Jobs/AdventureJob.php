<?php

namespace App\Game\Adventures\Jobs;

use App\Flare\Events\UpdateTopBarEvent;
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
        Character $character,
        Adventure $adventure,
        string $attackType,
        string $name,
        int $currentLevel = 1,
    ) {
        $this->character          = $character;
        $this->adventure          = $adventure;
        $this->attackType         = $attackType;
        $this->name               = $name;
        $this->currentLevel       = $currentLevel;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $name = Cache::get('character_'.$this->character->id.'_adventure_'.$this->adventure->id);

        $shouldBail = false;

        if (is_null($name) || $name !== $this->name) {
            if (!is_null($this->character->current_adventure_id)) {
                if (!$this->character->current_adventure_id === $this->adventure->id) {
                    $shouldBail = true;
                }
            } else  {
                $shouldBail = true;
            }
        }

        if ($shouldBail) {
            return;
        }

        ProcessAdventure::dispatch($this->character->id, $this->adventure->id, $this->currentLevel, $this->attackType);

        if ($this->currentLevel === $this->adventure->levels) {
            Cache::forget('character_'.$this->character->id.'_adventure_'.$this->adventure->id);

            event(new UpdateTopBarEvent($this->character->refresh()));
        } else {
            $delay            = $this->adventure->time_per_level;
            $timeTillFinished = now()->addMinutes($delay);

            AdventureJob::dispatch(
                $this->character,
                $this->adventure,
                $this->attackType,
                $this->name,
                $this->currentLevel + 1
            )->delay($timeTillFinished);
        }
    }

    public function middleware()
    {
        return [new WithoutOverlapping($this->name)];
    }
}
