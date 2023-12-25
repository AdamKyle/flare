<?php

namespace App\Game\Raids\Console\Commands;

use App\Flare\Models\Event;
use Illuminate\Console\Command;
use App\Flare\Models\RaidBossParticipation;
use App\Game\Battle\Events\UpdateRaidAttacksLeft;
use App\Game\Messages\Events\GlobalMessageEvent;

class ResetDailyRaidAttackLimits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reset:daily-raid-attack-limits';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'resets the daily raid attack limit';

    /**
     * Execute the console command.
     */
    public function handle() {

        $eventRaid = Event::whereNotNull('raid_id')->first();

        if (is_null($eventRaid)) {
            return;
        }

        $isRaidBossDead = is_null(RaidBossParticipation::where('killed_boss', true)->first());

        if ($isRaidBossDead) {
            return;
        }

        RaidBossParticipation::chunkById(250, function($participationRecords) {
            foreach ($participationRecords as $record) {
                $record->update([
                    'attacks_left' => 5,
                ]);

                event(new UpdateRaidAttacksLeft($record->character->user_id, 5));
            }
        });

        event(new GlobalMessageEvent('Raid Boss Attack Limit has been reset!'));
    }
}
