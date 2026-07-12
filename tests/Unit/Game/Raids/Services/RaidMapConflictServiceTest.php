<?php

namespace Tests\Unit\Game\Raids\Services;

use App\Flare\Models\Monster;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\ScheduledEventStatus;
use App\Game\Raids\Services\RaidMapConflictService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateRaid;
use Tests\Traits\CreateScheduledEvent;

class RaidMapConflictServiceTest extends TestCase
{
    use CreateGameMap, CreateLocation, CreateRaid, CreateScheduledEvent, RefreshDatabase;

    public function testBossLocationMapIsIncluded(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Surface']);
        $bossLocation = $this->createLocation(['game_map_id' => $gameMap->id]);

        $raid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $bossLocation->id,
            'corrupted_location_ids' => [],
        ]);

        $mapIds = (new RaidMapConflictService())->mapIdsForRaid($raid);

        $this->assertEquals([$gameMap->id], $mapIds);
    }

    public function testCorruptedLocationMapsAreIncludedAndDeduplicated(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Surface']);
        $bossLocation = $this->createLocation(['game_map_id' => $gameMap->id]);
        $corruptedLocationOne = $this->createLocation(['game_map_id' => $gameMap->id]);
        $corruptedLocationTwo = $this->createLocation(['game_map_id' => $gameMap->id]);

        $raid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $bossLocation->id,
            'corrupted_location_ids' => [$corruptedLocationOne->id, $corruptedLocationTwo->id],
        ]);

        $mapIds = (new RaidMapConflictService())->mapIdsForRaid($raid);

        $this->assertEquals([$gameMap->id], $mapIds);
    }

    public function testActiveSameMapRaidConflicts(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Surface']);
        $bossLocation = $this->createLocation(['game_map_id' => $gameMap->id]);

        $activeRaid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $bossLocation->id,
            'corrupted_location_ids' => [],
        ]);

        $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'raid_id' => $activeRaid->id,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
        ]);

        $requestedRaid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $bossLocation->id,
            'corrupted_location_ids' => [],
        ]);

        $conflict = (new RaidMapConflictService())->findActiveConflict($requestedRaid);

        $this->assertNotNull($conflict);
    }

    public function testWaitingSeasonalChildReservesMap(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Surface']);
        $bossLocation = $this->createLocation(['game_map_id' => $gameMap->id]);

        $childRaid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $bossLocation->id,
            'corrupted_location_ids' => [],
        ]);

        $parent = $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'raid_id' => null,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
        ]);

        $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'raid_id' => $childRaid->id,
            'parent_scheduled_event_id' => $parent->id,
            'status' => ScheduledEventStatus::SCHEDULED,
            'currently_running' => false,
        ]);

        $requestedRaid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $bossLocation->id,
            'corrupted_location_ids' => [],
        ]);

        $conflict = (new RaidMapConflictService())->findActiveConflict($requestedRaid);

        $this->assertNotNull($conflict);
    }

    public function testDifferentMapRaidDoesNotConflict(): void
    {
        $gameMapOne = $this->createGameMap(['name' => 'Surface']);
        $gameMapTwo = $this->createGameMap(['name' => 'Labyrinth']);
        $bossLocationOne = $this->createLocation(['game_map_id' => $gameMapOne->id]);
        $bossLocationTwo = $this->createLocation(['game_map_id' => $gameMapTwo->id]);

        $activeRaid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $bossLocationOne->id,
            'corrupted_location_ids' => [],
        ]);

        $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'raid_id' => $activeRaid->id,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
        ]);

        $requestedRaid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $bossLocationTwo->id,
            'corrupted_location_ids' => [],
        ]);

        $conflict = (new RaidMapConflictService())->findActiveConflict($requestedRaid);

        $this->assertNull($conflict);
    }

    public function testTwoRequestedSameMapRaidsConflict(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Surface']);
        $bossLocation = $this->createLocation(['game_map_id' => $gameMap->id]);

        $raidOne = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $bossLocation->id,
            'corrupted_location_ids' => [],
        ]);

        $raidTwo = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $bossLocation->id,
            'corrupted_location_ids' => [],
        ]);

        $conflicts = (new RaidMapConflictService())->requestedSetConflicts([$raidOne, $raidTwo]);

        $this->assertNotEmpty($conflicts);
    }

    public function testTwoRequestedDifferentMapRaidsDoNotConflict(): void
    {
        $gameMapOne = $this->createGameMap(['name' => 'Surface']);
        $gameMapTwo = $this->createGameMap(['name' => 'Labyrinth']);
        $bossLocationOne = $this->createLocation(['game_map_id' => $gameMapOne->id]);
        $bossLocationTwo = $this->createLocation(['game_map_id' => $gameMapTwo->id]);

        $raidOne = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $bossLocationOne->id,
            'corrupted_location_ids' => [],
        ]);

        $raidTwo = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $bossLocationTwo->id,
            'corrupted_location_ids' => [],
        ]);

        $conflicts = (new RaidMapConflictService())->requestedSetConflicts([$raidOne, $raidTwo]);

        $this->assertEmpty($conflicts);
    }
}
