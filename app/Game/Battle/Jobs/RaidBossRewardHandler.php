<?php

namespace App\Game\Battle\Jobs;

use App\Flare\Models\Raid;
use App\Flare\Models\Monster;
use Illuminate\Bus\Queueable;
use App\Flare\Models\Location;
use App\Flare\Models\Character;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Flare\Models\RaidBossParticipation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Database\Eloquent\Collection;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Battle\Events\UpdateRaidAttacksLeft;
use App\Game\Maps\Services\Common\UpdateRaidMonstersForLocation;

class RaidBossRewardHandler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, UpdateRaidMonstersForLocation;

    /**
     * @var integer $characterId
     */
    private int $characterId;

    /**
     * @var integer $raidId|null
     */
    private ?int $raidId;

    /**
     * @var integer $monsterId
     */
    private int $monsterId;

    /**
     * @param Collection $participants
     */
    public function __construct(int $characterId, int $monsterId, int $raidId = null) {
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

        if (!is_null($this->raidId)) {

            $raid = Raid::find($this->raidId);

            $this->handleWhenRaidBossIsKilled($character, $raid->raidBoss);

            $location = Location::where('x', $character->map->character_position_x)->where('y', $character->map->character_position_y)->first();

            $this->updateMonstersForRaid($character, $location);
        }
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
        
        $this->giveAncientReward($charater);

        RaidBossParticipation::chunkById(250, function($participationRecords) {
            foreach ($participationRecords as $record) {
                $record->update([
                    'attacks_left' => 0,
                ]);

                event(new UpdateRaidAttacksLeft($record->character->user_id, 0));
            }
        });

    }

    protected function giveAncientReward(Character $character) {
        
    }

}
