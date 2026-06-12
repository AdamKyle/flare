<?php

namespace App\Game\Raids\Console\Commands;

use App\Flare\Models\Event;
use App\Flare\Models\RaidBossParticipation;
use App\Game\Battle\Events\UpdateRaidAttacksLeft;
use App\Game\Messages\Events\GlobalMessageEvent;
use Illuminate\Console\Command;

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
    public function handle()
    {

        $eventRaidIds = Event::whereNotNull('raid_id')->pluck('raid_id');

        if ($eventRaidIds->isEmpty()) {
            return;
        }

        $participationWasReset = false;

        RaidBossParticipation::whereIn('raid_id', $eventRaidIds)
            ->where('killed_boss', false)
            ->chunkById(250, function ($participationRecords) use (&$participationWasReset) {
                foreach ($participationRecords as $record) {
                    $record->update([
                        'attacks_left' => 5,
                    ]);

                    $record = $record->refresh();

                    event(new UpdateRaidAttacksLeft($record->character->user_id, 5, $record->damage_dealt));

                    $participationWasReset = true;
                }
            });

        if (! $participationWasReset) {
            return;
        }

        event(new GlobalMessageEvent('Raid Boss Attack Limit has been reset!'));
    }
}
