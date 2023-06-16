<?php

namespace App\Console\Commands;

use App\Flare\Models\Item;
use App\Flare\Models\Raid;
use App\Flare\Models\Event;
use App\Flare\Models\Location;
use App\Flare\Models\Character;
use App\Flare\Values\EventType;
use Illuminate\Console\Command;
use App\Flare\Models\Announcement;
use App\Flare\Models\ScheduledEvent;
use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Events\UpdateScheduledEvents;
use App\Flare\Models\RaidBossParticipation;
use App\Game\Maps\Services\LocationService;
use App\Game\Raids\Events\CorruptLocations;
use App\Flare\Services\EventSchedulerService;
use App\Game\Maps\Services\UpdateRaidMonsters;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;

class EndScheduledEvent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'end:scheduled-event';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'End all scheduled events';

    /**
     * Execute the console command.
     */
    public function handle(LocationService $locationService, UpdateRaidMonsters $updateRaidMonsters, EventSchedulerService $eventSchedulerService)
    {
        $targetEventStart = now()->copy()->addMinutes(5);

        $scheduledEvents = ScheduledEvent::where('end_date', '<=', now())->get();

        foreach ($scheduledEvents as $event) {
            $eventType = new EventType($event->event_type);

            if ($eventType->isRaidEvent()) {

                $this->endRaid($event->raid, $locationService, $updateRaidMonsters);

                $event->update([
                    'currently_running' => false,
                ]);

                event(new UpdateScheduledEvents($eventSchedulerService->fetchEvents()));
            }
        }
    }

    protected function endRaid(Raid $raid, LocationService $locationService, UpdateRaidMonsters $updateRaidMonsters)
    {
        event(new GlobalMessageEvent('The Raid: ' . $raid->name . ' is now ending! Don\'t worry, the raid will be back soon. Check the event calendar for the next time!'));

        $this->unCorruptLocations($raid, $locationService, $updateRaidMonsters);

        Event::where('raid_id', $raid->id)->delete();

        $this->updateMonstersForCharactersAtRaidLocations($raid, $updateRaidMonsters);

        $this->giveGearReward($raid);

        $this->cleanUp();
    }

    protected function unCorruptLocations(Raid $raid, LocationService $locationService, UpdateRaidMonsters $updateRaidMonsters)
    {
        $raidLocations = [...$raid->corrupted_location_ids, $raid->raid_boss_location_id];

        Location::whereIn('id', $raidLocations)->update([
            'is_corrupted'  => false,
            'raid_id'       => null,
            'has_raid_boss' => false,
        ]);

        event(new CorruptLocations($locationService->fetchCorruptedLocationData($raid)->toArray()));
    }

    protected function giveGearReward(Raid $raid)
    {
        $raidParticipation = RaidBossParticipation::where('raid_id', $raid->id)->get();

        foreach ($raidParticipation as $participator) {

            $item = Item::where('specialty_type', ItemSpecialtyType::PIRATE_LORD_LEATHER)->inRandomOrder()->first();

            if ($participator->character->isInventoryFull()) {
                return;
            }
            
            if (!is_null($item)) {
                $validSocketTypes = [
                    'weapon', 'sleeves', 'gloves', 'feet', 'body', 'shield', 'helmet'
                ];

                $duplicatedItem = $item->duplicate();

                if (in_array($duplicatedItem->type, $validSocketTypes)) {
                    
                    $duplicatedItem->update([
                        'socket_count' => rand(0, 6),
                    ]);
                }

                $slot = $participator->character->inventory->slots()->create([
                    'inventory_id' => $participator->character->inventory->id,
                    'item_id'      => $duplicatedItem->id,
                ]);

                event(new ServerMessageEvent($participator->character->user, 'You were given: ' . $slot->item->name, $slot->id));

                event(new GlobalMessageEvent('Congratulations to: ' . $participator->character->name . ' for doing: ' . number_format($participator->damage_dealt) . ' total Damage to the raid boss! They have recieved a godly gift!'));
            }
        }
    }

    protected function cleanUp() {
        Announcement::where('expires_at', '<=', now())->delete();
    }

    protected function updateMonstersForCharactersAtRaidLocations(Raid $raid, UpdateRaidMonsters $updateRaidMonsters): void
    {
        $corruptedLocationIds = $raid->corrupted_location_ids;

        array_unshift($corruptedLocationIds, $raid->raid_boss_location_id);

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
