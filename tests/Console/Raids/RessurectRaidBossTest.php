<?php

namespace Tests\Console\Raids;

use App\Flare\Models\RaidBoss;
use App\Flare\Models\RaidBossParticipation;
use App\Game\Events\Values\EventType;
use App\Game\Messages\Events\GlobalMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateRaid;
use Tests\Traits\CreateScheduledEvent;

class RessurectRaidBossTest extends TestCase
{
    use CreateEvent, CreateGameMap, CreateItem, CreateLocation, CreateMonster, CreateRaid, CreateScheduledEvent, RefreshDatabase;

    public function testRevivesDeadRaidBossForRunningScheduledRaidAndDeletesOnlyThatRaidParticipation(): void
    {
        Event::fake();

        $gameMap = $this->createGameMap();
        $monster = $this->createMonster([
            'game_map_id' => $gameMap->id,
        ]);
        $item = $this->createItem();
        $location = $this->createLocation([
            'game_map_id' => $gameMap->id,
        ]);
        $raid = $this->createRaid([
            'raid_boss_id' => $monster->id,
            'raid_monster_ids' => [$monster->id],
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [$location->id],
            'artifact_item_id' => $item->id,
        ]);
        $otherMonster = $this->createMonster([
            'game_map_id' => $gameMap->id,
        ]);
        $otherItem = $this->createItem();
        $otherLocation = $this->createLocation([
            'game_map_id' => $gameMap->id,
        ]);
        $otherRaid = $this->createRaid([
            'raid_boss_id' => $otherMonster->id,
            'raid_monster_ids' => [$otherMonster->id],
            'raid_boss_location_id' => $otherLocation->id,
            'corrupted_location_ids' => [$otherLocation->id],
            'artifact_item_id' => $otherItem->id,
        ]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->createEvent([
            'raid_id' => $raid->id,
            'type' => EventType::RAID_EVENT,
            'started_at' => now(),
            'ends_at' => now()->addWeek(),
        ]);
        $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'raid_id' => $raid->id,
            'currently_running' => true,
        ]);
        $raidBoss = RaidBoss::create([
            'raid_id' => $raid->id,
            'raid_boss_id' => $monster->id,
            'boss_max_hp' => 100,
            'boss_current_hp' => 0,
        ]);
        RaidBossParticipation::create([
            'character_id' => $character->id,
            'raid_id' => $raid->id,
            'attacks_left' => 0,
            'damage_dealt' => 100,
            'killed_boss' => true,
        ]);
        $otherParticipation = RaidBossParticipation::create([
            'character_id' => $character->id,
            'raid_id' => $otherRaid->id,
            'attacks_left' => 0,
            'damage_dealt' => 50,
            'killed_boss' => true,
        ]);

        $this->artisan('ressurect:raid-boss');

        $this->assertEquals(100, $raidBoss->refresh()->boss_current_hp);
        $this->assertEquals(0, RaidBossParticipation::where('raid_id', $raid->id)->count());
        $this->assertNotNull($otherParticipation->fresh());
        Event::assertDispatched(GlobalMessageEvent::class);
    }

    public function testDoesNotReviveAliveDamagedRaidBoss(): void
    {
        Event::fake();

        $gameMap = $this->createGameMap();
        $monster = $this->createMonster([
            'game_map_id' => $gameMap->id,
        ]);
        $item = $this->createItem();
        $location = $this->createLocation([
            'game_map_id' => $gameMap->id,
        ]);
        $raid = $this->createRaid([
            'raid_boss_id' => $monster->id,
            'raid_monster_ids' => [$monster->id],
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [$location->id],
            'artifact_item_id' => $item->id,
        ]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->createEvent([
            'raid_id' => $raid->id,
            'type' => EventType::RAID_EVENT,
            'started_at' => now(),
            'ends_at' => now()->addWeek(),
        ]);
        $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'raid_id' => $raid->id,
            'currently_running' => true,
        ]);
        $raidBoss = RaidBoss::create([
            'raid_id' => $raid->id,
            'raid_boss_id' => $monster->id,
            'boss_max_hp' => 100,
            'boss_current_hp' => 25,
        ]);
        $participation = RaidBossParticipation::create([
            'character_id' => $character->id,
            'raid_id' => $raid->id,
            'attacks_left' => 0,
            'damage_dealt' => 75,
            'killed_boss' => false,
        ]);

        $this->artisan('ressurect:raid-boss');

        $this->assertEquals(25, $raidBoss->refresh()->boss_current_hp);
        $this->assertNotNull($participation->fresh());
        Event::assertNotDispatched(GlobalMessageEvent::class);
    }

    public function testDoesNotReviveDeadRaidBossWhenScheduledRaidIsNotRunning(): void
    {
        Event::fake();

        $gameMap = $this->createGameMap();
        $monster = $this->createMonster([
            'game_map_id' => $gameMap->id,
        ]);
        $item = $this->createItem();
        $location = $this->createLocation([
            'game_map_id' => $gameMap->id,
        ]);
        $raid = $this->createRaid([
            'raid_boss_id' => $monster->id,
            'raid_monster_ids' => [$monster->id],
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [$location->id],
            'artifact_item_id' => $item->id,
        ]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->createEvent([
            'raid_id' => $raid->id,
            'type' => EventType::RAID_EVENT,
            'started_at' => now(),
            'ends_at' => now()->addWeek(),
        ]);
        $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'raid_id' => $raid->id,
            'currently_running' => false,
        ]);
        $raidBoss = RaidBoss::create([
            'raid_id' => $raid->id,
            'raid_boss_id' => $monster->id,
            'boss_max_hp' => 100,
            'boss_current_hp' => 0,
        ]);
        $participation = RaidBossParticipation::create([
            'character_id' => $character->id,
            'raid_id' => $raid->id,
            'attacks_left' => 0,
            'damage_dealt' => 100,
            'killed_boss' => true,
        ]);

        $this->artisan('ressurect:raid-boss');

        $this->assertEquals(0, $raidBoss->refresh()->boss_current_hp);
        $this->assertNotNull($participation->fresh());
        Event::assertNotDispatched(GlobalMessageEvent::class);
    }
}
