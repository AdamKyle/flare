<?php

namespace App\Game\Core\Handlers;

use App\Flare\Models\Announcement;
use App\Flare\Models\Event;
use App\Flare\Models\GameMap;
use App\Flare\Models\Location;
use App\Flare\Models\Raid;
use App\Game\Events\Values\EventType;
use App\Game\Messages\Events\AnnouncementMessageEvent;
use App\Game\Messages\Services\AnnouncementPresenter;
use Carbon\Carbon;
use Exception;

class AnnouncementHandler
{
    /**
     * @param AnnouncementPresenter $announcementPresenter
     */
    public function __construct(
        private readonly AnnouncementPresenter $announcementPresenter,
    ) {}

    /**
     * Create and broadcast the Announcement for the given canonical announcement type.
     *
     * @param string $type
     * @param ?Event $event
     * @return void
     */
    public function createAnnouncement(string $type, ?Event $event = null): void
    {
        $this->buildAnnouncementForType($type, $event);
    }

    /**
     * Resolve the canonical announcement type key for the given Event type.
     *
     * @param int $type
     * @return ?string
     */
    public function getNameForType(int $type): ?string
    {
        return match ($type) {
            EventType::RAID_EVENT => 'raid_announcement',
            EventType::WEEKLY_CELESTIALS => 'weekly_celestial_spawn',
            EventType::WEEKLY_CURRENCY_DROPS => 'weekly_currency_drop',
            EventType::WINTER_EVENT => 'winter_event',
            EventType::PURGATORY_SMITH_HOUSE => 'purgatory_house',
            EventType::GOLD_MINES => 'gold_mines',
            EventType::THE_OLD_CHURCH => 'the_old_church',
            EventType::DELUSIONAL_MEMORIES_EVENT => 'delusional_memories_event',
            EventType::WEEKLY_FACTION_LOYALTY_EVENT => 'weekly_faction_loyalty_event',
            default => null,
        };
    }

    /**
     * Route the canonical announcement type to its message builder.
     *
     * @param string $type
     * @param ?Event $event
     * @return void
     */
    private function buildAnnouncementForType(string $type, ?Event $event = null): void
    {
        match ($type) {
            'raid_announcement' => $this->buildRaidAnnouncementMessage($event),
            'weekly_celestial_spawn' => $this->buildWeeklyCelestialMessage($event),
            'weekly_currency_drop' => $this->buildWeeklyCurrencyDrop($event),
            'winter_event' => $this->buildWinterEventMessage($event),
            'purgatory_house' => $this->buildPurgatoryHouseMessage($event),
            'gold_mines' => $this->buildTheGoldMinesMessage($event),
            'the_old_church' => $this->buildTheOldChurchMessage($event),
            'delusional_memories_event' => $this->buildDelusionalMemoriesMessage($event),
            'weekly_faction_loyalty_event' => $this->buildWeeklyFactionLoyaltyEvent($event),
            default => throw new Exception('Cannot determine announcement type'),
        };
    }

    /**
     * Build and publish the Raid Announcement message for the given or currently running Raid Event.
     *
     * @param ?Event $event
     * @return void
     */
    private function buildRaidAnnouncementMessage(?Event $event = null): void
    {
        $event ??= Event::where('type', EventType::RAID_EVENT)->first();

        if (is_null($event)) {
            throw new Exception('Cannot create message for raid event, when no event exists.');
        }

        $raid = Raid::find($event->raid_id);

        $locationNames = Location::whereIn('id', $raid->corrupted_location_ids)->pluck('name')->toArray();
        $gameMapIds = Location::whereIn('id', $raid->corrupted_location_ids)->pluck('game_map_id')->toArray();
        $gameMapNames = array_unique(GameMap::whereIn('id', $gameMapIds)->pluck('name')->toArray());
        $locationOfRaidBoss = Location::find($raid->raid_boss_location_id);

        $message = 'There is a raid ('.$raid->name.') currently running that ends on: '.$this->formatEndTime($event).
            '. Corrupted location are at: '.implode(', ', $locationNames).' on the planes: '.implode(', ', $gameMapNames).
            '. While the boss ('.$raid->raidBoss->name.') is at: '.$locationOfRaidBoss->name.' At (X/Y): '.$locationOfRaidBoss->x.
            '/'.$locationOfRaidBoss->y.' on plane: '.$locationOfRaidBoss->map->name.'.';

        $this->publish($message, $event);
    }

    /**
     * Build and publish the Gold Mines Announcement message for the given or currently running Gold Mines Event.
     *
     * @param ?Event $event
     * @return void
     */
    private function buildTheGoldMinesMessage(?Event $event = null): void
    {
        $event ??= Event::where('type', EventType::GOLD_MINES)->first();

        if (is_null($event)) {
            throw new Exception('Cannot create message for The Gold Mines, when no event exists.');
        }

        $message = 'From now until: '.$this->formatEndTime($event).' '.
            'Players who are in The Gold Mines will have double chance to get unique gear. '.
            'Players will also get 2x the amount of Gold Dust, Shards and Gold from critters.';

        $this->publish($message, $event);
    }

    /**
     * Build and publish The Old Church Announcement message for the given or currently running Old Church Event.
     *
     * @param ?Event $event
     * @return void
     */
    private function buildTheOldChurchMessage(?Event $event = null): void
    {
        $event ??= Event::where('type', EventType::THE_OLD_CHURCH)->first();

        if (is_null($event)) {
            throw new Exception('Cannot create message for The Old Church, when no event exists.');
        }

        $message = 'From now until: '.$this->formatEndTime($event).' '.
            'Players who are in The Old Church will have double chance to get a unique Corrupted Ice gear. '.
            'Players will also get 2x the amount of Gold Dust, Shards and Gold from critters.';

        $this->publish($message, $event);
    }

    /**
     * Build and publish the Weekly Celestials Announcement message for the given Event.
     *
     * @param ?Event $event
     * @return void
     */
    private function buildWeeklyCelestialMessage(?Event $event = null): void
    {
        if (is_null($event)) {
            throw new Exception('Cannot create message for weekly celestial event, when no event exists.');
        }

        $message = 'Celestials have been unleashed across the lands and various planes! All you have to do, for the next 24 hours '.
            'ending at: '.$this->formatEndTime($event).' players just have to move around the map and there is a 80% '.
            'chance for Celestial Entities that you would otherwise have to pay to conjure, will spawn! Kill em all child and get those pretty shards for alchemy!';

        $this->publish($message, $event);
    }

    /**
     * Build and publish the Weekly Currency Drops Announcement message for the given Event.
     *
     * @param ?Event $event
     * @return void
     */
    private function buildWeeklyCurrencyDrop(?Event $event = null): void
    {
        if (is_null($event)) {
            throw new Exception('Cannot create message for weekly currency drop event, when no event exists.');
        }

        $message = 'For one day only, ending: '.$this->formatEndTime($event).' '.
            'Players can get 1-50 of each type of currency, Gold Dust, Crystal Shards, Copper Coins (if you have the appropriate quest item). ';

        $this->publish($message, $event);
    }

    /**
     * Build and publish the Weekly Faction Loyalty Announcement message for the given Event.
     *
     * @param ?Event $event
     * @return void
     */
    private function buildWeeklyFactionLoyaltyEvent(?Event $event = null): void
    {
        if (is_null($event)) {
            throw new Exception('Cannot create message for weekly faction loyalty event, when no event exists.');
        }

        $message = 'For one day only, ending: '.$this->formatEndTime($event).' '.
            'Players will get two points in their faction loyalty tasks when completing a task. When an NPC task list refreshes from gaining a level,'.' '.
            'it will half the required amount of each task.';

        $this->publish($message, $event);
    }

    /**
     * Build and publish the Winter Event Announcement message for the given Event.
     *
     * @param ?Event $event
     * @return void
     */
    private function buildWinterEventMessage(?Event $event = null): void
    {
        if (is_null($event)) {
            throw new Exception('Cannot create message for Winter Event, when no event exists.');
        }

        $message = 'From now until: '.$this->formatEndTime($event).' '.
            'Players can enter, with no item requirements, The Ice Plane and fight fearsome creatures as well as take on The Ice Queen her self.'.' '.
            'You will find the creatures down here to be much more powerful then even Purgatory! Prepare your self child, the chill of death awaits.'.' '.
            'All you have to do is use the traverse feature to move from your current plane to The Ice Plane where rewards are bountiful!';

        $this->publish($message, $event);
    }

    /**
     * Build and publish the Delusional Memories Announcement message for the given Event.
     *
     * @param ?Event $event
     * @return void
     */
    private function buildDelusionalMemoriesMessage(?Event $event = null): void
    {
        if (is_null($event)) {
            throw new Exception('Cannot create message for Delusional Memories Event, when no event exists.');
        }

        $message = 'From now until: '.$this->formatEndTime($event).' '.
            'Players can enter, with no item requirements, The Delusional Memories Plane and fight fearsome creatures and take on the Jester of Time who twist and deludes his own memories. '.
            'All you have to is Traverse to participate in new quests, new raid, new gear and new global events where all players come '.
            'together to help the Red Hawks push back an enemy from a time long forgotten!';

        $this->publish($message, $event);
    }

    /**
     * Build and publish the Purgatory Smith House Announcement message for the given or currently running Purgatory Smith House Event.
     *
     * @param ?Event $event
     * @return void
     */
    private function buildPurgatoryHouseMessage(?Event $event = null): void
    {
        $event ??= Event::where('type', EventType::PURGATORY_SMITH_HOUSE)->first();

        if (is_null($event)) {
            throw new Exception('Cannot create message for The Purgatory Smith House, when no event exists.');
        }

        $message = 'From now until: '.$this->formatEndTime($event).' '.
            'Players who are in The Purgatory Smiths House will have double chance to get LEGENDARY uniques and MYTHICAL gear. '.
            'Players will also get 2x the amount of Gold Dust, Copper Coins and Shards from critters.';

        $this->publish($message, $event);
    }

    /**
     * Format the Event's end time for player-facing Announcement copy.
     *
     * @param Event $event
     * @return string
     */
    private function formatEndTime(Event $event): string
    {
        return Carbon::parse($event->ends_at)->setTimezone(env('TIME_ZONE'))->format('g A T');
    }

    /**
     * Create the Announcement for the given message/Event and broadcast it.
     *
     * @param string $message
     * @param Event $event
     * @return void
     */
    private function publish(string $message, Event $event): void
    {
        $announcement = Announcement::create([
            'message' => $message,
            'expires_at' => $event->ends_at,
            'event_id' => $event->id,
        ]);

        $this->dispatch($announcement);
    }

    /**
     * Decorate the Announcement for player-facing display and broadcast it.
     *
     * @param Announcement $announcement
     * @return void
     */
    private function dispatch(Announcement $announcement): void
    {
        event(new AnnouncementMessageEvent($this->announcementPresenter->present($announcement)));
    }
}
