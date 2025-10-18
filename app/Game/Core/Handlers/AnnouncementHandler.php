<?php

namespace App\Game\Core\Handlers;

use App\Flare\Models\Announcement;
use App\Flare\Models\Event;
use App\Flare\Models\GameMap;
use App\Flare\Models\Location;
use App\Flare\Models\Raid;
use App\Game\Events\Values\EventType;
use App\Game\Messages\Events\AnnouncementMessageEvent;
use Carbon\Carbon;
use Exception;

class AnnouncementHandler
{
    public function createAnnouncement(string $type): void
    {
        $this->buildAnnouncementForType($type);
    }

    public function getNameForType(int $type): ?string
    {
        return match ($type) {
            EventType::RAID_EVENT => 'raid_announcement',
            EventType::WEEKLY_CELESTIALS => 'weekly_celestial_spawn',
            EventType::WEEKLY_CURRENCY_DROPS => 'weekly_currency_drop',
            EventType::WINTER_EVENT => 'winter_event',
            EventType::PURGATORY_SMITH_HOUSE => 'purgatory_house',
            EventType::GOLD_MINES => 'gold_mines',
            EventType::THE_OLD_CHURCH => 'the_old_chur',
            EventType::DELUSIONAL_MEMORIES_EVENT => 'delusional_memory',
            EventType::WEEKLY_FACTION_LOYALTY_EVENT => 'weekly_faction_loyalty_event',
            EventType::FEEDBACK_EVENT => 'tlessas_feedback_event',
            default => null,
        };
    }

    protected function buildAnnouncementForType(string $type): void
    {
        match ($type) {
            'raid_announcement' => $this->buildRaidAnnouncementMessage(),
            'weekly_celestial_spawn' => $this->buildWeeklyCelestialMessage(),
            'weekly_currency_drop' => $this->buildWeeklyCurrencyDrop(),
            'winter_event' => $this->buildWinterEventMessage(),
            'purgatory_house' => $this->buildPurgatoryHouseMessage(),
            'gold_mines' => $this->buildTheGoldMinesMessage(),
            'the_old_church' => $this->buildTheOldChurchMessage(),
            'delusional_memories_event' => $this->buildDelusionalMemoriesMessage(),
            'weekly_faction_loyalty_event' => $this->buildWeeklyFactionLoyaltyEvent(),
            'tlessas_feedback_event' => $this->buildFeedbackAnnouncement(),
            default => throw new Exception('Cannot determine announcement type'),
        };
    }

    private function buildRaidAnnouncementMessage(): void
    {
        $event = Event::where('type', EventType::RAID_EVENT)->first();

        if (is_null($event)) {
            throw new Exception('Cannot create message for raid event, when no event exists.');
        }

        $raid = Raid::find($event->raid_id);

        $locationNames = Location::whereIn('id', $raid->corrupted_location_ids)->pluck('name')->toArray();
        $gameMapIds = Location::whereIn('id', $raid->corrupted_location_ids)->pluck('game_map_id')->toArray();
        $gameMapNames = array_unique(GameMap::whereIn('id', $gameMapIds)->pluck('name')->toArray());
        $locationOfRaidBoss = Location::find($raid->raid_boss_location_id);

        $endTime = Carbon::parse($event->ends_at)->setTimezone(env('TIME_ZONE'))->format('g A T');

        $message = 'There is a raid ('.$raid->name.') currently running that ends on: '.$endTime.
            '. Corrupted location are at: '.implode(', ', $locationNames).' on the planes: '.implode(', ', $gameMapNames).
            '. While the boss ('.$raid->raidBoss->name.') is at: '.$locationOfRaidBoss->name.' At (X/Y): '.$locationOfRaidBoss->x.
            '/'.$locationOfRaidBoss->y.' on plane: '.$locationOfRaidBoss->map->name.'.';

        $announcement = Announcement::create([
            'message' => $message,
            'expires_at' => $event->ends_at,
            'event_id' => $event->id,
        ]);

        event(new AnnouncementMessageEvent($message, $announcement->id));
    }

    private function buildTheGoldMinesMessage(): void
    {
        $event = Event::where('type', EventType::THE_OLD_CHURCH)->first();

        if (is_null($event)) {
            throw new Exception('Cannot create message for The The Old Church, when no event exists.');
        }

        $endTime = Carbon::parse($event->ends_at)->setTimezone(env('TIME_ZONE'))->format('g A T');

        $message = 'From now until: '.$endTime.' '.
            'Players who are in The Gold Mines will have double chance to get unique gear. '.
            'Players will also get 2x the amount of Gold Dust, Shards and Gold from critters.';

        $announcement = Announcement::create([
            'message' => $message,
            'expires_at' => $event->ends_at,
            'event_id' => $event->id,
        ]);

        event(new AnnouncementMessageEvent($message, $announcement->id));
    }

    private function buildTheOldChurchMessage(): void
    {
        $event = Event::where('type', EventType::THE_OLD_CHURCH)->first();

        if (is_null($event)) {
            throw new Exception('Cannot create message for The The Old Church, when no event exists.');
        }

        $endTime = Carbon::parse($event->ends_at)->setTimezone(env('TIME_ZONE'))->format('g A T');

        $message = 'From now until: '.$endTime.' '.
            'Players who are in The Old Church will have double chance to get a unique Corrupted Ice gear. '.
            'Players will also get 2x the amount of Gold Dust, Shards and Gold from critters.';

        $announcement = Announcement::create([
            'message' => $message,
            'expires_at' => $event->ends_at,
            'event_id' => $event->id,
        ]);

        event(new AnnouncementMessageEvent($message, $announcement->id));
    }

    private function buildWeeklyCelestialMessage(): void
    {
        $event = Event::where('type', EventType::WEEKLY_CELESTIALS)->first();

        if (is_null($event)) {
            throw new Exception('Cannot create message for weekly celestial event, when no event exists.');
        }

        $endTime = Carbon::parse($event->ends_at)->setTimezone(env('TIME_ZONE'))->format('g A T');

        $message = 'Celestials have been unleashed across the lands and various planes! All you have to do, for the next 24 hours '.
            'ending at: '.$endTime.' players just have to move around the map and there is a 80% '.
            'chance for Celestial Entities that you would otherwise have to pay to conjure, will spawn! Kill em all child and get those pretty shards for alchemy!';

        $announcement = Announcement::create([
            'message' => $message,
            'expires_at' => $event->ends_at,
            'event_id' => $event->id,
        ]);

        event(new AnnouncementMessageEvent($message, $announcement->id));
    }

    private function buildWeeklyCurrencyDrop(): void
    {
        $event = Event::where('type', EventType::WEEKLY_CURRENCY_DROPS)->first();

        if (is_null($event)) {
            throw new Exception('Cannot create message for weekly celestial event, when no event exists.');
        }

        $endTime = Carbon::parse($event->ends_at)->setTimezone(env('TIME_ZONE'))->format('g A T');

        $message = 'For one day only, ending: '.$endTime.' '.
            'Players can get 1-50 of each type of currency, Gold Dust, Crystal Shards, Copper Coins (if you have the appropriate quest item). ';

        $announcement = Announcement::create([
            'message' => $message,
            'expires_at' => $event->ends_at,
            'event_id' => $event->id,
        ]);

        event(new AnnouncementMessageEvent($message, $announcement->id));
    }

    public function buildWeeklyFactionLoyaltyEvent(): void
    {
        $event = Event::where('type', EventType::WEEKLY_FACTION_LOYALTY_EVENT)->first();

        if (is_null($event)) {
            throw new Exception('Cannot create message for weekly celestial event, when no event exists.');
        }

        $endTime = Carbon::parse($event->ends_at)->setTimezone(env('TIME_ZONE'))->format('g A T');

        $message = 'For one day only, ending: '.$endTime.' '.
            'Players will get two points in their faction loyalty tasks when completing a task. When an NPC task list refreshes from gaining a level,'.' '.
            'it will half the required amount of each task.';

        $announcement = Announcement::create([
            'message' => $message,
            'expires_at' => $event->ends_at,
            'event_id' => $event->id,
        ]);

        event(new AnnouncementMessageEvent($message, $announcement->id));
    }

    private function buildWinterEventMessage(): void
    {
        $event = Event::where('type', EventType::WINTER_EVENT)->first();

        if (is_null($event)) {
            throw new Exception('Cannot create message for Winter Event, when no event exists.');
        }

        $endTime = Carbon::parse($event->ends_at)->setTimezone(env('TIME_ZONE'))->format('g A T');

        $message = 'From now until: '.$endTime.' '.
            'Players can enter, with no item requirements, The Ice Plane and fight fearsome creatures as well as take on The Ice Queen her self.'.' '.
            'You will find the creatures down here to be much more powerful then even Purgatory! Prepare your self child, the chill of death awaits.'.' '.
            'All you have to do is use the traverse feature to move from your current plane to The Ice Plane where rewards are bountiful!';

        $announcement = Announcement::create([
            'message' => $message,
            'expires_at' => $event->ends_at,
            'event_id' => $event->id,
        ]);

        event(new AnnouncementMessageEvent($message, $announcement->id));
    }

    private function buildDelusionalMemoriesMessage(): void
    {
        $event = Event::where('type', EventType::DELUSIONAL_MEMORIES_EVENT)->first();

        if (is_null($event)) {
            throw new Exception('Cannot create message for Delusional Memories Event, when no event exists.');
        }

        $endTime = Carbon::parse($event->ends_at)->setTimezone(env('TIME_ZONE'))->format('g A T');

        $message = 'From now until: '.$endTime.' '.
            'Players can enter, with no item requirements, The Delusional Memories Plane and fight fearsome creatures and take on the Jester of Time who twist and deludes his own memories. '.
            'All you have to is Traverse to participate in new quests, new raid, new gear and new global events where all players come '.
            'together to help the Red Hawks push back an enemy from a time long forgotten!';

        $announcement = Announcement::create([
            'message' => $message,
            'expires_at' => $event->ends_at,
            'event_id' => $event->id,
        ]);

        event(new AnnouncementMessageEvent($message, $announcement->id));
    }

    private function buildPurgatoryHouseMessage(): void
    {
        $event = Event::where('type', EventType::PURGATORY_SMITH_HOUSE)->first();

        if (is_null($event)) {
            throw new Exception('Cannot create message for The Purgatory Smith House, when no event exists.');
        }

        $endTime = Carbon::parse($event->ends_at)->setTimezone(env('TIME_ZONE'))->format('g A T');

        $message = 'From now until: '.$endTime.' '.
            'Players who are in The Purgatory Smiths House will have double chance to get LEGENDARY uniques and MYTHICAL gear. '.
            'Players will also get 2x the amount of Gold Dust, Copper Coins and Shards from critters.';

        $announcement = Announcement::create([
            'message' => $message,
            'expires_at' => $event->ends_at,
            'event_id' => $event->id,
        ]);

        event(new AnnouncementMessageEvent($message, $announcement->id));
    }

    private function buildFeedbackAnnouncement(): void
    {
        $event = Event::where('type', EventType::FEEDBACK_EVENT)->first();

        if (is_null($event)) {
            throw new Exception('Cannot create message for Feedback event, when no event exists.');
        }

        $endTime = Carbon::parse($event->ends_at)->setTimezone(env('TIME_ZONE'))->format('g A T');

        $message = 'From now until: '.$endTime.' '.
            'Players who are new and old will gain 75 more xp per kill under level 1,000, 150 more xp under level 5000 and for those who have reincarnated, you will gain 500 more xp per kill.'.' '.
            'Players will also gain +150 XP in training skills and in crafting skills, including alchemy and enchanting, they will also see a raise of +175xp per craft/enchant!'.' '.
            'After 6 hours of combined (does NOT need to be consecutive) - players of all skill types and play times will be asked to participate in a survey to help Tlessa become a better game. Once you complete the survey you will be rewarded with a mythical item!'.' '.
            'These items, for newer players, will carry them to rough mid game start of end game, depending on how their stats are rolled. These items can be re-rolled later at The Queen of Hearts in Hell!';

        $announcement = Announcement::create([
            'message' => $message,
            'expires_at' => $event->ends_at,
            'event_id' => $event->id,
        ]);

        event(new AnnouncementMessageEvent($message, $announcement->id));
    }
}
