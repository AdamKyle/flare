<?php

namespace App\Admin\Jobs;

use Mail;
use Str;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Admin\Mail\GenericMail;
use App\Flare\Models\Adventure;
use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Flare\Models\User;
use App\Flare\Services\FightService;
use App\Game\Adventures\Jobs\AdventureJob;
use Cache;

class RunTestSimulation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Character $character
     */
    public $character;

    /**
     * @var User $adminUser
     */
    public $adminUser;

    /**
     * @var mixed Model
     */
    public $model;

    /**
     * @var string $type
     */
    public $type;

    /**
     * @var int $totalTimes
     */
    public $totalTimes;

    public $sendEmail;

    /**
     * Create a new job instance.
     *
     * @param Character $character
     * @param string $type
     * @param int $id
     * @param int $totalTimes
     * @param User $adminUser | null
     * @return void
     */
    public function __construct(Character $character, string $type, int $id, int $totalTimes, User $adminUser, bool $sendEmail) {
        $this->character     = $character;
        $this->adminUser     = $adminUser;
        $this->type          = $type;
        $this->model         = $this->getModel($type, $id);
        $this->totalTimes    = $totalTimes;
        $this->sendEmail     = $sendEmail;
    }

    /**
     * Processes the type of simmulation test we want.
     * 
     * @return void
     */
    public function handle() {
        switch($this->type) {
            case 'monster':
                $this->processBattle();
                break;
            case 'adventure':
                $this->processAdventure();
                break;
        }
    }

    protected function processAdventure() {
        $jobName = Str::random(80);
                
        Cache::put('character_'.$this->character->id.'_adventure_'.$this->model->id, $jobName, now()->addMinutes(5));

        for ($i = 1; $i <= $this->model->levels; $i++) {
            $delay            = $i === 1 ? $this->model->time_per_level : $i * $this->model->time_per_level;
            $timeTillFinished = now()->addMinutes($delay);

            AdventureJob::dispatch($this->character, $this->model, $jobName, $i, true, $this->adminUser, $this->sendEmail)->delay($timeTillFinished);
        }
    }

    protected function processBattle() {
        for ($i = 1; $i <= $this->totalTimes; $i++) {
            SimulateBattle::dispatch($this->character, $this->model, $i, $this->totalTimes, $this->adminUser, $this->sendEmail)->delay(now()->addMinutes($i));
        }
    }

    protected function getModel(string $type, int $id) {
        $model = null;

        switch($type) {
            case 'monster':
                $model = Monster::find($id);
                break;
            case 'adventure':
                $model = Adventure::find($id);
        }

        return $model;
    }
}
