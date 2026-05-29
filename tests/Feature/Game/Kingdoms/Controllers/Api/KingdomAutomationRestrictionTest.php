<?php

namespace Tests\Feature\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\UnitInQueue;
use App\Flare\Values\AutomationType;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class KingdomAutomationRestrictionTest extends TestCase
{
    use RefreshDatabase;

    public function test_kingdom_details_can_be_fetched_during_exploration(): void
    {
        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdom = $characterFactory
            ->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding()
            ->getKingdom();
        $characterFactory->assignAutomation([
            'type' => AutomationType::EXPLORING,
        ]);
        $character = $characterFactory->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/player-kingdom/'.$character->id.'/'.$kingdom->id);

        $response->assertOk();
        $response->assertJsonPath('kingdom.id', $kingdom->id);
    }

    public function test_kingdom_list_can_be_fetched_during_exploration(): void
    {
        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $characterFactory
            ->kingdomManagement()
            ->assignKingdom();
        $characterFactory->assignAutomation([
            'type' => AutomationType::EXPLORING,
        ]);
        $character = $characterFactory->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/player-kingdoms/'.$character->id);

        $response->assertOk();
        $this->assertArrayHasKey('kingdoms', $response->json());
    }

    public function test_kingdom_details_can_be_fetched_during_delve(): void
    {
        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdom = $characterFactory
            ->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding()
            ->getKingdom();
        $characterFactory->assignAutomation([
            'type' => AutomationType::DELVE,
        ]);
        $character = $characterFactory->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/player-kingdom/'.$character->id.'/'.$kingdom->id);

        $response->assertOk();
        $response->assertJsonPath('kingdom.id', $kingdom->id);
    }

    public function test_kingdom_details_can_be_fetched_during_faction_loyalty(): void
    {
        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdom = $characterFactory
            ->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding()
            ->getKingdom();
        $characterFactory->assignAutomation([
            'type' => AutomationType::FACTION_LOYALTY,
        ]);
        $character = $characterFactory->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/player-kingdom/'.$character->id.'/'.$kingdom->id);

        $response->assertOk();
        $response->assertJsonPath('kingdom.id', $kingdom->id);
    }

    public function test_kingdom_mutation_rejects_during_automation(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'current_wood' => 2000,
                'current_clay' => 2000,
                'current_stone' => 2000,
                'current_iron' => 2000,
                'current_population' => 2000,
            ])
            ->assignBuilding();
        $characterFactory->assignAutomation([
            'type' => AutomationType::EXPLORING,
        ]);
        $character = $kingdomManagement->getCharacter();
        $kingdom = $kingdomManagement->getKingdom();
        $building = $kingdom->buildings()->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdoms/'.$character->id.'/upgrade-building/'.$building->id, [
                'to_level' => $building->level + 1,
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'You cannot do that while Exploration automation is running. Cancel it first.',
        ]);
        $this->assertSame(0, BuildingInQueue::where('kingdom_id', $kingdom->id)->count());
    }

    public function test_capital_city_building_reads_can_be_fetched_during_automation(): void
    {
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
        $characterFactory->assignAutomation([
            'type' => AutomationType::EXPLORING,
        ]);
        $character = $characterFactory->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/kingdom/capital-city/building-queues/'.$character->id.'/'.$capitalCity->id);

        $response->assertOk();
        $this->assertArrayHasKey('building_queues', $response->json());
    }

    public function test_capital_city_unit_reads_can_be_fetched_during_automation(): void
    {
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
        $characterFactory->assignAutomation([
            'type' => AutomationType::EXPLORING,
        ]);
        $character = $characterFactory->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/kingdom/capital-city/unit-queues/'.$character->id.'/'.$capitalCity->id);

        $response->assertOk();
        $this->assertArrayHasKey('unit_queues', $response->json());
    }

    public function test_building_mutation_rejects_when_capital_city_queue_exists(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $capitalCity = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'is_capital' => true,
            ])
            ->getKingdom();
        $kingdom = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'current_wood' => 2000,
                'current_clay' => 2000,
                'current_stone' => 2000,
                'current_iron' => 2000,
                'current_population' => 2000,
            ])
            ->assignBuilding([
                'max_level' => 5,
            ], [
                'level' => 1,
            ])
            ->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $kingdom->buildings()->first();

        CapitalCityBuildingQueue::create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'requested_kingdom' => $capitalCity->id,
            'building_request_data' => [[
                'building_name' => $building->name,
                'secondary_status' => CapitalCityQueueStatus::TRAVELING,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::TRAVELING,
            'started_at' => now(),
            'completed_at' => now()->addMinutes(10),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdoms/'.$character->id.'/upgrade-building/'.$building->id, [
                'to_level' => $building->level + 1,
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Building is already in the process of upgrading.',
        ]);
        $this->assertSame(0, BuildingInQueue::where('kingdom_id', $kingdom->id)->count());
    }

    public function test_unit_mutation_rejects_when_manual_queue_exists(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdom = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'current_wood' => 2000,
                'current_clay' => 2000,
                'current_stone' => 2000,
                'current_iron' => 2000,
                'current_population' => 2000,
            ])
            ->assignUnits([], 1)
            ->getKingdom();
        $character = $characterFactory->getCharacter();
        $gameUnit = $kingdom->units()->first()->gameUnit;

        UnitInQueue::create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'game_unit_id' => $gameUnit->id,
            'amount' => 1,
            'started_at' => now(),
            'completed_at' => now()->addMinutes(10),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdoms/'.$kingdom->id.'/recruit-units/'.$gameUnit->id, [
                'amount' => 1,
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Unit is already in the process of recruiting.',
        ]);
        $this->assertSame(1, UnitInQueue::where('kingdom_id', $kingdom->id)->count());
    }
}
