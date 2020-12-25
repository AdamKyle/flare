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
use App\Game\Maps\Adventure\Jobs\AdventureJob;
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
    public function __construct(Character $character, string $type, int $id, int $totalTimes, User $adminUser = null) {
        $this->character  = $character;
        $this->adminUser  = $adminUser;
        $this->type       = $type;
        $this->model      = $this->getModel($type, $id);
        $this->totalTimes = $totalTimes;
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
            default:
                return;
        }
    }

    protected function processAdventure() {
        for ($i = 1; $i <= $this->totalTimes; $i++) {
            $jobName = Str::random(80);
                
            Cache::put('character_'.$this->character->id.'_adventure_'.$this->model->id, $jobName, now()->addMinutes(5));

            for ($j = 1; $j <= $this->model->levels; $j++) {
                $delay            = $j === 1 ? $this->model->time_per_level : $j * $this->model->time_per_level;
                $timeTillFinished = now()->addMinutes($delay);

                AdventureJob::dispatch($this->character, $this->model, $jobName, $j, true)->delay($timeTillFinished);
            }
        }
    }

    protected function processBattle() {

        $logData = [];

        for ($i = 1; $i <= $this->totalTimes; $i++) {
            $fightService = resolve(FightService::class, ['character' => $this->character, 'monster'=> $this->model]);

            $fightService->attack($this->character, $this->model);

            $logInfo = $fightService->getLogInformation();
            
            $logInfo['character_dead'] = $fightService->isCharacterDead();
            $logInfo['monster_dead']   = $fightService->isMonsterDead();
            
            $logData[] = $logInfo;
        }

        $logData['monster_id']     = $this->model->id;

        $this->character->snapShots()->where('snap_shot->level', strval($this->character->level))->first()->update([
            'battle_simmulation_data' => $logData,
        ]);

        // Finally reset the character back to level 1000.
        $this->character->update(
            $this->character->snapShots()->where('snap_shot->level', '1000')->first()->snap_shot
        );
    }

    protected function emailAdmin() {
        if (!is_null($this->adminUser)) {
            Mail::to($this->adminUser->email)->send(new GenericMail($this->adminUser, 'Your simulation has completed. Login and see the details.', 'Simmulation Results', false));
        }
    }

    protected function getModel(string $type, int $id) {
        switch($type) {
            case 'monster':
                return Monster::find($id);
            case 'adventure':
                return Adventure::find($id);
            default:
                return null;
        }
    }
}
