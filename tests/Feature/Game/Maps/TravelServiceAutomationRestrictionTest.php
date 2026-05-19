<?php

namespace Tests\Feature\Game\Maps;

use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\GameMap;
use App\Flare\Models\Location;
use App\Flare\Values\AttackTypeValue;
use App\Flare\Values\AutomationType;
use App\Flare\Values\LocationEffectValue;
use App\Flare\Values\LocationType;
use App\Flare\Values\MapNameValue;
use App\Game\Maps\Services\MovementService;
use App\Game\Maps\Services\SetSailService;
use App\Game\Maps\Services\TeleportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

/**
 * This is a feature / integration test around map travel while automation is running.
 */
class TravelServiceAutomationRestrictionTest extends TestCase
{
    use RefreshDatabase;

    public function testSpecialLocationExplorationBlocksTeleport(): void
    {
        $gameMap = GameMap::factory()->create([
            'name' => MapNameValue::SURFACE,
            'path' => 'surface.png',
            'default' => false,
            'can_traverse' => true,
        ]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->getCharacter();

        Location::factory()->create([
            'name' => 'Gold Mine',
            'game_map_id' => $gameMap->id,
            'x' => 16,
            'y' => 16,
            'type' => LocationType::GOLD_MINES,
            'enemy_strength_type' => LocationEffectValue::INCREASE_STATS_BY_TWO_HUNDRED_FIFTY,
        ]);
        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
            'started_in_special_location' => true,
        ]);
        $teleportService = resolve(TeleportService::class);
        $teleportService->setCoordinatesToTravelTo(32, 16)->setCost(0)->setTimeOutValue(0);

        $response = $teleportService->teleport($character);

        $this->assertEquals(422, $response['status']);
    }

    public function testSpecialLocationExplorationBlocksSetSail(): void
    {
        $gameMap = GameMap::factory()->create([
            'name' => MapNameValue::SURFACE,
            'path' => 'surface.png',
            'default' => false,
            'can_traverse' => true,
        ]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->getCharacter();

        Location::factory()->create([
            'name' => 'Gold Mine',
            'game_map_id' => $gameMap->id,
            'x' => 16,
            'y' => 16,
            'type' => LocationType::GOLD_MINES,
            'enemy_strength_type' => LocationEffectValue::INCREASE_STATS_BY_TWO_HUNDRED_FIFTY,
        ]);
        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
            'started_in_special_location' => true,
        ]);
        $setSailService = resolve(SetSailService::class);
        $setSailService->setCoordinatesToTravelTo(32, 16)->setCost(0)->setTimeOutValue(0);

        $response = $setSailService->setSail($character);

        $this->assertEquals(422, $response['status']);
    }

    public function testSpecialLocationExplorationBlocksTraverse(): void
    {
        $gameMap = GameMap::factory()->create([
            'name' => MapNameValue::SURFACE,
            'path' => 'surface.png',
            'default' => false,
            'can_traverse' => true,
        ]);
        $destinationMap = GameMap::factory()->create([
            'name' => MapNameValue::LABYRINTH,
            'path' => 'surface.png',
            'default' => false,
            'can_traverse' => true,
        ]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(16, 16, $gameMap)->getCharacter();

        Location::factory()->create([
            'name' => 'Gold Mine',
            'game_map_id' => $gameMap->id,
            'x' => 16,
            'y' => 16,
            'type' => LocationType::GOLD_MINES,
            'enemy_strength_type' => LocationEffectValue::INCREASE_STATS_BY_TWO_HUNDRED_FIFTY,
        ]);
        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
            'started_in_special_location' => true,
        ]);

        $response = resolve(MovementService::class)->updateCharacterPlane($destinationMap->id, $character);

        $this->assertEquals(422, $response['status']);
    }

    public function testRegularExplorationBlocksTeleport(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
            'started_in_special_location' => false,
        ]);
        $teleportService = resolve(TeleportService::class);
        $teleportService->setCoordinatesToTravelTo(32, 16)->setCost(0)->setTimeOutValue(0);

        $response = $teleportService->teleport($character);

        $this->assertEquals(422, $response['status']);
    }

    public function testRegularExplorationBlocksSetSail(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
            'started_in_special_location' => false,
        ]);
        $setSailService = resolve(SetSailService::class);
        $setSailService->setCoordinatesToTravelTo(32, 16)->setCost(0)->setTimeOutValue(0);

        $response = $setSailService->setSail($character);

        $this->assertEquals(422, $response['status']);
    }

    public function testRegularExplorationBlocksTraverse(): void
    {
        $destinationMap = GameMap::factory()->create([
            'name' => MapNameValue::LABYRINTH,
            'path' => 'surface.png',
            'default' => false,
            'can_traverse' => true,
        ]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
            'started_in_special_location' => false,
        ]);

        $response = resolve(MovementService::class)->updateCharacterPlane($destinationMap->id, $character);

        $this->assertEquals(422, $response['status']);
    }

    public function testBlockedTeleportDoesNotDeleteCurrentExplorationAutomation(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $automation = CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
            'started_in_special_location' => false,
        ]);
        $teleportService = resolve(TeleportService::class);
        $teleportService->setCoordinatesToTravelTo(32, 16)->setCost(0)->setTimeOutValue(0);

        $teleportService->teleport($character);

        $this->assertNotNull(CharacterAutomation::find($automation->id));
    }

    public function testBlockedSetSailDoesNotDeleteCurrentExplorationAutomation(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $automation = CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
            'started_in_special_location' => false,
        ]);
        $setSailService = resolve(SetSailService::class);
        $setSailService->setCoordinatesToTravelTo(32, 16)->setCost(0)->setTimeOutValue(0);

        $setSailService->setSail($character);

        $this->assertNotNull(CharacterAutomation::find($automation->id));
    }

    public function testBlockedTraverseDoesNotDeleteCurrentExplorationAutomation(): void
    {
        $destinationMap = GameMap::factory()->create([
            'name' => MapNameValue::LABYRINTH,
            'path' => 'surface.png',
            'default' => false,
            'can_traverse' => true,
        ]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $automation = CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'attack_type' => AttackTypeValue::ATTACK,
            'started_in_special_location' => false,
        ]);

        resolve(MovementService::class)->updateCharacterPlane($destinationMap->id, $character);

        $this->assertNotNull(CharacterAutomation::find($automation->id));
    }
}
