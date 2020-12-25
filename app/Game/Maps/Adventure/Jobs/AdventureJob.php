<?php

namespace App\Game\Maps\Adventure\Jobs;

use App\Flare\Models\Adventure;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterSnapShot;
use App\Game\Maps\Adventure\Builders\RewardBuilder;
use App\Game\Maps\Adventure\Services\AdventureService;
use Cache;

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
     * @var bool $characterModeling
     */
    protected $characterModeling;

    /**
     * Create a new job instance.
     *
     * @param Character $character
     * @param Adventure $adventure
     * @param string $name
     * @param int $curentLevel
     * @return void
     */
    public function __construct(Character $character, Adventure $adventure, String $name, int $currentLevel, bool $characterModeling = false)
    {
        $this->character          = $character;
        $this->adventure          = $adventure;
        $this->name               = $name;
        $this->currentLevel       = $currentLevel;
        $this->characterModeling  = $characterModeling;
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

        $adevntureService = resolve(AdventureService::class, [
            'character'           => $this->character->refresh(),
            'adventure'           => $this->adventure,
            'rewardBuilder'       => $rewardBuilder,
            'name'                => $this->name
        ]);

        $adevntureService->processAdventure($this->currentLevel, $this->adventure->levels, $this->characterModeling);

        if ($this->currentLevel === $this->adventure->levels) {
            Cache::forget('character_'.$this->character->id.'_adventure_'.$this->adventure->id);
        }

        if ($this->characterModeling) {
            $data = [];
            $data[$this->currentLevel] = $adevntureService->getLogInformation();

            $snapShot = CharacterSnapShot::where('character_id', $this->character->id)->first();

            if (is_null($snapShot->adventure_simmulation_data)) {
                $snapShot->update(['adventure_simmulation_data' => $data]);
            } else {
                $snapShotData = $snapShot->adventure_simmulation_data;
                
                $snapShot->update(['adventure_simmulation_data' => array_merge($snapShotData, $data)]);
            }

            if ($this->currentLevel === $this->adventure->levels) {
                $data         = [];
                $snapShotData = $snapShot->refresh()->adventure_simmulation_data;

                $data['adventure_id']   = $this->adventure->id;
                $data['snap_shot_data'] = $snapShotData;

                $snapShot->update([
                    'adventure_simmulation_data' => $data,
                ]);
            }
        }
    }
}
