<?php

namespace App\Admin\Jobs;

use Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Admin\Mail\GenericMail;
use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Flare\Models\User;
use App\Flare\Services\FightService;

class RunTestSimulation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public $character;

    /**
     * @var User $adminUser
     */
    public $adminUser;

    public $model;

    public $type;

    public $totalTimes;

    /**
     * Create a new job instance.
     *
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
     * 
     * @return void
     */
    public function handle() {
        switch($this->type) {
            case 'monster':
                $this->processBattle();
                
                break;
            default:
                return;
        }

        $this->emailAdmin();
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
        

        $this->character->snapShots()->where('snap_shot->level', $this->character->level)->first()->update([
            'battle_simmulation_data' => $logData,
        ]);

        // Finally reset the character back to level 1000.
        $this->character->update(
            $this->character->snapShots()->where('snap_shot->level', 1000)->first()->snap_shot
        );
    }

    protected function emailAdmin() {
        if (!is_null($this->adminUser)) {
            Mail::to($this->adminUser->email)->send(new GenericMail($this->adminUser, 'Your battle simulation has completed.', 'Battle Simmulation Results', false));
        }
    }

    protected function getModel(string $type, int $id) {
        switch($type) {
            case 'monster':
                return Monster::find($id);
            default:
                return null;
        }
    }
}
