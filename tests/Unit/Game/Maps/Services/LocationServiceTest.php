<?php

namespace Tests\Unit\Game\Maps\Services;

use App\Game\Battle\Values\CelestialConjureType;
use App\Game\Maps\Events\UpdateMonsterList;
use App\Game\Maps\Events\UpdateRaidMonsters;
use App\Game\Maps\Services\LocationService;
use App\Game\Maps\Values\LocationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCelestials;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateRaid;
use Tests\Traits\CreateScheduledEvent;

class LocationServiceTest extends TestCase
{
    use CreateCelestials, CreateLocation, CreateMonster, CreateRaid, CreateScheduledEvent, RefreshDatabase;

    public function test_raid_monsters_not_overwritten_by_special_location_monsters(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $gameMap = $character->map->gameMap;

        $location = $this->createLocation([
            'x' => 16,
            'y' => 16,
            'game_map_id' => $gameMap->id,
            'type' => LocationType::GOLD_MINES->value,
        ]);

        Cache::put('special-location-monsters', [
            'location-type-'.LocationType::GOLD_MINES->value => [['id' => 1, 'name' => 'Gold Mine Monster']],
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

    public function test_non_raid_special_location_updates_monsters(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $gameMap = $character->map->gameMap;

        $this->createLocation([
            'x' => 16,
            'y' => 16,
            'game_map_id' => $gameMap->id,
            'type' => LocationType::GOLD_MINES->value,
        ]);

        Cache::put('special-location-monsters', [
            'location-type-'.LocationType::GOLD_MINES->value => [['id' => 1, 'name' => 'Gold Mine Monster']],
        ]);

        Event::fake();

        resolve(LocationService::class)->locationBasedEvents($character);

        Event::assertDispatched(UpdateMonsterList::class);
    }

    public function test_public_celestial_at_characters_position_is_visible_in_location_data(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
        ]);

        $fight = $this->createCelestialFight([
            'monster_id' => $monster->id,
            'character_id' => null,
            'x_position' => $character->map->character_position_x,
            'y_position' => $character->map->character_position_y,
            'damaged_kingdom' => false,
            'stole_treasury' => false,
            'weakened_morale' => false,
            'conjured_at' => now(),
            'current_health' => 100,
            'max_health' => 100,
            'type' => CelestialConjureType::PUBLIC,
        ]);

        Event::fake();

        $locationData = resolve(LocationService::class)->getLocationData($character);

        $this->assertSame($fight->id, $locationData['celestial_id']);
    }

    public function test_owned_private_celestial_at_characters_position_is_visible_in_location_data(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
        ]);

        $fight = $this->createCelestialFight([
            'monster_id' => $monster->id,
            'character_id' => $character->id,
            'x_position' => $character->map->character_position_x,
            'y_position' => $character->map->character_position_y,
            'damaged_kingdom' => false,
            'stole_treasury' => false,
            'weakened_morale' => false,
            'conjured_at' => now(),
            'current_health' => 100,
            'max_health' => 100,
            'type' => CelestialConjureType::PRIVATE,
        ]);

        Event::fake();

        $locationData = resolve(LocationService::class)->getLocationData($character);

        $this->assertSame($fight->id, $locationData['celestial_id']);
    }

    public function test_another_characters_private_celestial_is_not_visible_in_location_data(): void
    {
        $characterA = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $characterB = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $monster = $this->createMonster([
            'game_map_id' => $characterA->map->game_map_id,
        ]);

        $this->createCelestialFight([
            'monster_id' => $monster->id,
            'character_id' => $characterA->id,
            'x_position' => $characterB->map->character_position_x,
            'y_position' => $characterB->map->character_position_y,
            'damaged_kingdom' => false,
            'stole_treasury' => false,
            'weakened_morale' => false,
            'conjured_at' => now(),
            'current_health' => 100,
            'max_health' => 100,
            'type' => CelestialConjureType::PRIVATE,
        ]);

        Event::fake();

        $locationData = resolve(LocationService::class)->getLocationData($characterB);

        $this->assertNull($locationData['celestial_id']);
    }
}
