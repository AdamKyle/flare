<?php

namespace Tests\Unit\Game\Maps\Services;

use App\Flare\Models\Location;
use App\Flare\Values\LocationType;
use App\Game\Maps\Events\UpdateMonsterList;
use App\Game\Maps\Events\UpdateRaidMonsters;
use App\Game\Maps\Services\LocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateRaid;
use Tests\Traits\CreateScheduledEvent;

class LocationServiceTest extends TestCase
{
    use CreateMonster, CreateRaid, CreateScheduledEvent, RefreshDatabase;

    public function testRaidMonstersNotOverwrittenBySpecialLocationMonsters(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $gameMap = $character->map->gameMap;

        $location = Location::factory()->create([
            'x' => 16,
            'y' => 16,
            'game_map_id' => $gameMap->id,
            'type' => LocationType::GOLD_MINES,
        ]);

        Cache::put('special-location-monsters', [
            'location-type-' . LocationType::GOLD_MINES => [['id' => 1, 'name' => 'Gold Mine Monster']],
        ]);

        $raidBoss = $this->createMonster(['game_map_id' => $gameMap->id]);

        $raid = $this->createRaid([
            'raid_boss_id' => $raidBoss->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [$location->id],
        ]);

        $this->createScheduledEvent([
            'raid_id' => $raid->id,
            'currently_running' => true,
        ]);

        Event::fake();

        resolve(LocationService::class)->locationBasedEvents($character);

        Event::assertDispatched(UpdateRaidMonsters::class);
        Event::assertNotDispatched(UpdateMonsterList::class);
    }

    public function testNonRaidSpecialLocationUpdatesMonsters(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $gameMap = $character->map->gameMap;

        Location::factory()->create([
            'x' => 16,
            'y' => 16,
            'game_map_id' => $gameMap->id,
            'type' => LocationType::GOLD_MINES,
        ]);

        Cache::put('special-location-monsters', [
            'location-type-' . LocationType::GOLD_MINES => [['id' => 1, 'name' => 'Gold Mine Monster']],
        ]);

        Event::fake();

        resolve(LocationService::class)->locationBasedEvents($character);

        Event::assertDispatched(UpdateMonsterList::class);
    }
}
