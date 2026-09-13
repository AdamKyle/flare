<?php

namespace Tests\Unit\Game\Kingdoms\Services;

use App\Game\Kingdoms\Builders\KingdomBuilder;
use App\Game\Kingdoms\Handlers\UpdateKingdomHandler;
use App\Game\Kingdoms\Service\KingdomSettleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;

class KingdomSettleServiceTest extends TestCase
{
    use CreateGameMap, RefreshDatabase;

    public function test_settle_pre_check_rejects_a_generated_map_gem_world(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Surface']);
        $generatedMap = $this->createGameMap([
            'name' => 'Fiery Map Gem World',
            'generated_map_type' => 'map_gem',
            'generated_parent_game_map_id' => $parentMap->id,
        ]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(0, 0, $generatedMap)->getCharacter();

        $service = new KingdomSettleService(
            Mockery::mock(KingdomBuilder::class),
            Mockery::mock(UpdateKingdomHandler::class),
        );

        $result = $service->settlePreCheck($character, 'New Kingdom');

        $this->assertSame('You cannot settle a Kingdom inside a Gem World.', $result['message']);
    }

    public function test_settle_pre_check_rejects_a_generated_location_gem_world(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Surface']);
        $generatedMap = $this->createGameMap([
            'name' => 'Fiery Location Gem World',
            'generated_map_type' => 'location_gem',
            'generated_parent_game_map_id' => $parentMap->id,
        ]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(0, 0, $generatedMap)->getCharacter();

        $service = new KingdomSettleService(
            Mockery::mock(KingdomBuilder::class),
            Mockery::mock(UpdateKingdomHandler::class),
        );

        $result = $service->settlePreCheck($character, 'New Kingdom');

        $this->assertSame('You cannot settle a Kingdom inside a Gem World.', $result['message']);
    }

    public function test_can_settle_returns_false_with_the_gem_world_message_for_a_generated_map(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Surface']);
        $generatedMap = $this->createGameMap([
            'name' => 'Fiery Map Gem World',
            'generated_map_type' => 'map_gem',
            'generated_parent_game_map_id' => $parentMap->id,
        ]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(0, 0, $generatedMap)->getCharacter();

        $service = new KingdomSettleService(
            Mockery::mock(KingdomBuilder::class),
            Mockery::mock(UpdateKingdomHandler::class),
        );

        $canSettle = $service->canSettle($character);

        $this->assertFalse($canSettle);
        $this->assertSame('You cannot settle a Kingdom inside a Gem World.', $service->getErrorMessage());
    }

    public function test_can_settle_remains_true_for_an_eligible_normal_map_position(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Surface']);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(0, 0, $gameMap)->getCharacter();

        $service = new KingdomSettleService(
            Mockery::mock(KingdomBuilder::class),
            Mockery::mock(UpdateKingdomHandler::class),
        );

        $canSettle = $service->canSettle($character);

        $this->assertTrue($canSettle);
    }
}
