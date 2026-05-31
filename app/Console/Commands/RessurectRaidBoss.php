<?php

namespace App\Console\Commands;

use App\Flare\Models\Character;
use App\Flare\Models\Event;
use App\Flare\Models\Location;
use App\Flare\Models\RaidBoss;
use App\Flare\Models\RaidBossParticipation;
use App\Flare\Models\ScheduledEvent;
use App\Game\Maps\Services\UpdateRaidMonsters;
use App\Game\Messages\Events\GlobalMessageEvent;
use Illuminate\Console\Command;

class RessurectRaidBoss extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ressurect:raid-boss';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ressurects Raid Boss once a week after death.';

    /**
     * Execute the console command.
     */
    public function handle(UpdateRaidMonsters $updateRaidMonsters)
    {

        $events = Event::whereNotNull('raid_id')->with('raid')->get();

        foreach ($events as $event) {
            if (! $this->hasRunningScheduledRaid($event)) {
                continue;
            }

            $raidBoss = RaidBoss::where('raid_id', $event->raid_id)->first();

            if (is_null($raidBoss) || ! $this->isRaidBossDead($raidBoss)) {
                continue;
            }

            $raidBoss->update([
                'boss_current_hp' => $raidBoss->boss_max_hp,
            ]);

            RaidBossParticipation::where('raid_id', $event->raid_id)->delete();

            $locationOfRaidBoss = Location::find($event->raid->raid_boss_location_id);

            event(new GlobalMessageEvent('"Death has come for you child! I shall have my revenge!!"', 'raid-global-message'));

            event(new GlobalMessageEvent('Location: '.$locationOfRaidBoss->name.' At (X/Y): '.$locationOfRaidBoss->x.
                '/'.$locationOfRaidBoss->y.' on plane: '.$locationOfRaidBoss->map->name.' has become over run! The Raid boss: '.$event->raid->raidBoss->name.
                ' has set up shop!'));

            $corruptedLocationIds = $event->raid->corrupted_location_ids;

            array_unshift($corruptedLocationIds, $event->raid->raid_boss_location_id);

            $corruptedLocations = Location::whereIn('id', $corruptedLocationIds)->get();

            foreach ($corruptedLocations as $location) {
                $characters = Character::leftJoin('maps', 'characters.id', '=', 'maps.character_id')
                    ->where('maps.character_position_x', $location->x)
                    ->where('maps.character_position_y', $location->y)
                    ->where('maps.game_map_id', $location->game_map_id)
                    ->get();

                foreach ($characters as $character) {
                    $updateRaidMonsters->updateMonstersForRaidLocations($character, $location);
                }
            }
        }
    }

    private function hasRunningScheduledRaid(Event $event): bool
    {
        return ScheduledEvent::where('raid_id', $event->raid_id)
            ->where('currently_running', true)
            ->exists();
    }

    private function isRaidBossDead(RaidBoss $raidBoss): bool
    {
        if (! is_null($raidBoss->boss_current_hp) && $raidBoss->boss_current_hp <= 0) {
            return true;
        }

        return RaidBossParticipation::where('raid_id', $raidBoss->raid_id)
            ->where('killed_boss', true)
            ->exists();
    }
}
