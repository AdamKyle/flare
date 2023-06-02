<?php

namespace App\Game\Battle\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Database\Eloquent\Collection;
use App\Flare\Models\Raid;
use App\Flare\Models\Monster;
use App\Flare\Models\Character;
use App\Flare\Models\RaidBossParticipation;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Battle\Events\UpdateRaidAttacksLeft;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;

class RaidBossRewardHandler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var integer $characterId
     */
    private int $characterId;

    /**
     * @var integer $raidId
     */
    private int $raidId;

    /**
     * @var integer $monsterId
     */
    private int $monsterId;

    /**
     * @param Collection $participants
     */
    public function __construct(int $characterId, int $raidId, $monsterId) {
        $this->characterId = $characterId;
        $this->raidId      = $raidId;
        $this->monsterId   = $monsterId;
    }

    /**
     * @param MonthlyPvpFightService $monthlyPvpFightService
     * @return void
     * @throws Exception
     */
    public function handle(BattleEventHandler $battleEventHandler) {
        $character = Character::find($this->characterId);
        
        $battleEventHandler->processMonsterDeath($this->characterId, $this->monsterId);

        $raid = Raid::find($this->raidId);

        $this->handleWhenRaidBossIsKilled($character, $raid->raidBoss);
    }

    /**
     * Handle the raid boss when its killed.
     * 
     * - No one can attack anymore. The attacks will not reset.
     *
     * @param Character $charater
     * @param Monster $raidBoss
     * @return void
     */
    private function handleWhenRaidBossIsKilled(Character $charater, Monster $raidBoss): void {
        event(new GlobalMessageEvent($charater->name . ' Has slaughted: ' . $raidBoss->name . ' and has recieved a special Ancient gift from The Poet him self!'));
        
        RaidBossParticipation::chunkById(250, function($participationRecords) {
            foreach ($participationRecords as $record) {
                $record->update([
                    'attacks_left' => 0,
                ]);

                event(new UpdateRaidAttacksLeft($record->character->user_id, 0));
            }
        });

        // Do stuff here...
    }

}
