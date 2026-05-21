<?php

namespace Tests\Unit\Game\Kingdoms\Services;

use App\Flare\Models\BuildingExpansionQueue;
use App\Flare\Models\KingdomBuildingExpansion;
use App\Game\Kingdoms\Service\OrphanedBuildingExpansionQueueCleanupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameBuilding;

class OrphanedBuildingExpansionQueueCleanupServiceTest extends TestCase
{
    use CreateGameBuilding, RefreshDatabase;

    public function testCleanDeletesMissingBuildingQueuesOnly(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdom = $characterFactory->kingdomManagement()->assignKingdom()->getKingdom();
        $character = $characterFactory->getCharacter();
        $buildingWithoutExpansion = $kingdom->buildings()->create([
            'game_building_id' => $this->createGameBuilding([
                'is_resource_building' => true,
                'increase_wood_amount' => 100,
            ])->id,
            'kingdom_id' => $kingdom->id,
            'level' => 1,
            'max_defence' => 100,
            'max_durability' => 100,
            'current_durability' => 100,
            'current_defence' => 100,
        ]);
        $buildingWithExpansion = $kingdom->buildings()->create([
            'game_building_id' => $this->createGameBuilding([
                'is_resource_building' => true,
                'increase_stone_amount' => 100,
            ])->id,
            'kingdom_id' => $kingdom->id,
            'level' => 1,
            'max_defence' => 100,
            'max_durability' => 100,
            'current_durability' => 100,
            'current_defence' => 100,
        ]);
        KingdomBuildingExpansion::create([
            'kingdom_building_id' => $buildingWithExpansion->id,
            'kingdom_id' => $kingdom->id,
            'expansion_type' => 0,
            'expansion_count' => 1,
            'expansions_left' => 7,
            'minutes_until_next_expansion' => 30,
            'resource_costs' => ['stone' => 100],
            'gold_bars_cost' => 100,
            'resource_increases' => 100,
        ]);
        $missingBuildingQueue = BuildingExpansionQueue::create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'building_id' => 999999,
            'completed_at' => now()->addHour(),
            'started_at' => now(),
        ]);
        $firstTimeExpansionQueue = BuildingExpansionQueue::create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'building_id' => $buildingWithoutExpansion->id,
            'completed_at' => now()->addHour(),
            'started_at' => now(),
        ]);
        $validQueue = BuildingExpansionQueue::create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'building_id' => $buildingWithExpansion->id,
            'completed_at' => now()->addHour(),
            'started_at' => now(),
        ]);

        resolve(OrphanedBuildingExpansionQueueCleanupService::class)->clean();

        $this->assertNull(BuildingExpansionQueue::find($missingBuildingQueue->id));
        $this->assertNotNull(BuildingExpansionQueue::find($firstTimeExpansionQueue->id));
        $this->assertNotNull(BuildingExpansionQueue::find($validQueue->id));
    }

    public function testCleanKeepsValidFirstTimeExpansionQueues(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdom = $characterFactory->kingdomManagement()->assignKingdom()->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $kingdom->buildings()->create([
            'game_building_id' => $this->createGameBuilding([
                'is_resource_building' => true,
                'increase_wood_amount' => 100,
            ])->id,
            'kingdom_id' => $kingdom->id,
            'level' => 1,
            'max_defence' => 100,
            'max_durability' => 100,
            'current_durability' => 100,
            'current_defence' => 100,
        ]);
        $validQueue = BuildingExpansionQueue::create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'building_id' => $building->id,
            'completed_at' => now()->addHour(),
            'started_at' => now(),
        ]);

        resolve(OrphanedBuildingExpansionQueueCleanupService::class)->clean();

        $this->assertNotNull(BuildingExpansionQueue::find($validQueue->id));
    }
}
