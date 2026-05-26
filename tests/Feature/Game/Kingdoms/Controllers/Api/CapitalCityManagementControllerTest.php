<?php

namespace Tests\Feature\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\CapitalCityBuildingQueue;
use App\Game\Kingdoms\Values\BuildingQueueType;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class CapitalCityManagementControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testDirectApiCannotQueueMaxLevelBuilding(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $capitalCity = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'is_capital' => true,
                'x_position' => 16,
                'y_position' => 16,
            ])
            ->getKingdom();
        $targetKingdom = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'current_wood' => 2000,
                'current_clay' => 2000,
                'current_stone' => 2000,
                'current_iron' => 2000,
                'current_population' => 2000,
                'x_position' => 32,
                'y_position' => 16,
            ])
            ->assignBuilding([
                'max_level' => 1,
            ], [
                'level' => 1,
            ])
            ->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $targetKingdom->buildings()->first();

        $this->actingAs($character->user);
        $response = $this->call(
            'POST',
            '/api/kingdom/capital-city/upgrade-building-requests/' . $character->id . '/' . $capitalCity->id,
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'request_type' => 'upgrade',
                'request_data' => [[
                    'kingdomId' => $targetKingdom->id,
                    'buildingIds' => [$building->id],
                ]],
            ])
        );

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'One or more buildings are already max level.',
        ]);
        $this->assertSame(0, CapitalCityBuildingQueue::where('kingdom_id', $targetKingdom->id)->count());
        $this->assertSame(1, $building->refresh()->level);
        $this->assertSame(2000, $targetKingdom->refresh()->current_wood);
        $this->assertSame(2000, $targetKingdom->refresh()->current_clay);
        $this->assertSame(2000, $targetKingdom->refresh()->current_stone);
        $this->assertSame(2000, $targetKingdom->refresh()->current_iron);
        $this->assertSame(2000, $targetKingdom->refresh()->current_population);
    }

    public function testDirectApiRejectsBuildingAlreadyQueuedManually(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $capitalCity = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'is_capital' => true,
                'x_position' => 16,
                'y_position' => 16,
            ])
            ->getKingdom();
        $targetKingdom = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'current_wood' => 2000,
                'current_clay' => 2000,
                'current_stone' => 2000,
                'current_iron' => 2000,
                'current_population' => 2000,
                'x_position' => 32,
                'y_position' => 16,
            ])
            ->assignBuilding([
                'max_level' => 5,
            ], [
                'level' => 1,
            ])
            ->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $targetKingdom->buildings()->first();

        BuildingInQueue::factory()->create([
            'character_id' => $character->id,
            'kingdom_id' => $targetKingdom->id,
            'building_id' => $building->id,
            'from_level' => 1,
            'to_level' => 2,
            'type' => BuildingQueueType::UPGRADE,
            'started_at' => now(),
            'completed_at' => now()->addMinutes(10),
        ]);

        $this->actingAs($character->user);
        $response = $this->call(
            'POST',
            '/api/kingdom/capital-city/upgrade-building-requests/' . $character->id . '/' . $capitalCity->id,
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'request_type' => 'upgrade',
                'request_data' => [[
                    'kingdomId' => $targetKingdom->id,
                    'buildingIds' => [$building->id],
                ]],
            ])
        );

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'One or more buildings are already queued for upgrade.',
        ]);
        $this->assertSame(0, CapitalCityBuildingQueue::where('kingdom_id', $targetKingdom->id)->count());
        $this->assertSame(1, $building->refresh()->level);
        $this->assertSame(2000, $targetKingdom->refresh()->current_wood);
    }

    public function testDirectApiRejectsBuildingAlreadyQueuedByCapitalCity(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $capitalCity = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'is_capital' => true,
                'x_position' => 16,
                'y_position' => 16,
            ])
            ->getKingdom();
        $targetKingdom = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'current_wood' => 2000,
                'current_clay' => 2000,
                'current_stone' => 2000,
                'current_iron' => 2000,
                'current_population' => 2000,
                'x_position' => 32,
                'y_position' => 16,
            ])
            ->assignBuilding([
                'max_level' => 5,
            ], [
                'level' => 1,
            ])
            ->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $targetKingdom->buildings()->first();

        CapitalCityBuildingQueue::create([
            'character_id' => $character->id,
            'kingdom_id' => $targetKingdom->id,
            'requested_kingdom' => $capitalCity->id,
            'building_request_data' => [[
                'building_id' => $building->id,
                'building_name' => $building->name,
                'type' => 'upgrade',
                'missing_costs' => [],
                'secondary_status' => CapitalCityQueueStatus::TRAVELING,
                'from_level' => 1,
                'to_level' => 2,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::TRAVELING,
            'started_at' => now(),
            'completed_at' => now()->addMinutes(10),
        ]);

        $this->actingAs($character->user);
        $response = $this->call(
            'POST',
            '/api/kingdom/capital-city/upgrade-building-requests/' . $character->id . '/' . $capitalCity->id,
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'request_type' => 'upgrade',
                'request_data' => [[
                    'kingdomId' => $targetKingdom->id,
                    'buildingIds' => [$building->id],
                ]],
            ])
        );

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'One or more buildings are already queued for upgrade.',
        ]);
        $this->assertSame(1, CapitalCityBuildingQueue::where('kingdom_id', $targetKingdom->id)->count());
        $this->assertSame(1, $building->refresh()->level);
        $this->assertSame(2000, $targetKingdom->refresh()->current_wood);
    }
}
