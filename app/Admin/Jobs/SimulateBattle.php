<?php

namespace App\Admin\Jobs;

use App\Admin\Mail\GenericMail;
use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Flare\Models\User;
use App\Flare\Services\FightService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Mail;

class SimulateBattle implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $character;

    public $monster;

    public $currentFight;

    public $totalFights;

    public $adminUser;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Character $character, Monster $monster, $currentFight, $totalFights, User $adminUser)
    {
        $this->character    = $character;
        $this->monster      = $monster; 
        $this->currentFight = $currentFight;
        $this->totalFights  = $totalFights;
        $this->adminUser    = $adminUser;
    }

    
    public function handle()
    {
        $fightService = resolve(FightService::class, ['character' => $this->character, 'monster'=> $this->monster]);

        $fightService->attack($this->character, $this->monster);

        $logInfo = $fightService->getLogInformation();
        $logData = [];
        
        $logInfo['character_dead'] = $fightService->isCharacterDead();
        $logInfo['monster_dead']   = $fightService->isMonsterDead();
        $logInfo['monster_id']     = $this->monster->id;
        $logInfo['character_id']   = $this->character->id;

        $logData[] = $logInfo;

        $snapShot = $this->character->snapShots()->where('snap_shot->level', strval($this->character->level))->first();

        if (is_null($snapShot->battle_simmulation_data)) {
            $snapShot->update([
                'battle_simmulation_data' => $logData
            ]);
        } else {
            $snapShotData   = $snapShot->battle_simmulation_data;
            $snapShotData[] = $logData[0];

            $snapShot->update([
                'battle_simmulation_data' => $snapShotData,
            ]);
        }

        if ($this->currentFight === $this->totalFights) {
            $snapShot                   = $this->character->snapShots()->where('snap_shot->level', strval($this->character->level))->first();
            $snapShotData               = $snapShot->battle_simmulation_data;
            $snapShotData['monster_id'] = $this->monster->id;
            
            $snapShot->update([
                'battle_simmulation_data' => $snapShotData
            ]);

            Mail::to($this->adminUser->email)->send(new GenericMail($this->adminUser, 'Your simulation has completed. Login and see the details for the monster: ' . $this->monster->name . '.', 'Battle Simmulation Results', false));
        }
    }
}
