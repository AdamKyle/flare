<?php

namespace Tests\Feature\Game\Kingdoms\Requests;

use App\Flare\Models\GameUnit;
use App\Flare\Models\UnitInQueue;
use App\Game\Kingdoms\Jobs\CapitalCityQueueUpUnitRequests;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class CapitalCityUnitRequestTest extends TestCase
{
    use RefreshDatabase;

    public function testCapitalCityRecruitRejectsManuallyQueuedUnit(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $capitalCity = $characterFactory->kingdomManagement()->assignKingdom([
            'is_capital' => true,
            'x_position' => 16,
            'y_position' => 16,
        ])->getKingdom();
        $targetKingdom = $characterFactory->kingdomManagement()->assignKingdom([
            'current_wood' => 1000,
            'current_clay' => 1000,
            'current_stone' => 1000,
            'current_iron' => 1000,
            'current_population' => 1000,
            'x_position' => 32,
            'y_position' => 16,
        ])->getKingdom();
        $character = $characterFactory->getCharacter();
        $unit = GameUnit::factory()->create(['name' => 'Spearmen']);
        UnitInQueue::factory()->create([
            'character_id' => $character->id,
            'kingdom_id' => $targetKingdom->id,
            'game_unit_id' => $unit->id,
            'amount' => 10,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdom/capital-city/recruit-unit-requests/' . $character->id . '/' . $capitalCity->id, [
                'request_data' => [[
                    'kingdom_id' => $targetKingdom->id,
                    'unit_requests' => [[
                        'unit_name' => $unit->name,
                        'unit_amount' => 1,
                    ]],
                ]],
            ]);

        $response->assertOk();
        Queue::assertPushed(CapitalCityQueueUpUnitRequests::class);
    }

    public function testCapitalCityRecruitRejectsCapitalCityQueuedUnit(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $capitalCity = $characterFactory->kingdomManagement()->assignKingdom([
            'is_capital' => true,
            'x_position' => 16,
            'y_position' => 16,
        ])->getKingdom();
        $targetKingdomManagement = $characterFactory->kingdomManagement()->assignKingdom([
            'current_wood' => 1000,
            'current_clay' => 1000,
            'current_stone' => 1000,
            'current_iron' => 1000,
            'current_population' => 1000,
            'x_position' => 32,
            'y_position' => 16,
        ]);
        $targetKingdom = $targetKingdomManagement->getKingdom();
        $character = $characterFactory->getCharacter();
        $unit = GameUnit::factory()->create(['name' => 'Spearmen']);
        $targetKingdomManagement->assignCapitalCityUnitQueue([
            'character_id' => $character->id,
            'kingdom_id' => $targetKingdom->id,
            'requested_kingdom' => $capitalCity->id,
            'unit_request_data' => [[
                'name' => $unit->name,
                'amount' => 10,
                'secondary_status' => CapitalCityQueueStatus::RECRUITING,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::RECRUITING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdom/capital-city/recruit-unit-requests/' . $character->id . '/' . $capitalCity->id, [
                'request_data' => [[
                    'kingdom_id' => $targetKingdom->id,
                    'unit_requests' => [[
                        'unit_name' => $unit->name,
                        'unit_amount' => 1,
                    ]],
                ]],
            ]);

        $response->assertOk();
        Queue::assertPushed(CapitalCityQueueUpUnitRequests::class);
    }

    public function testCapitalCityValidNonQueuedRecruitmentDispatchesRequest(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $capitalCity = $characterFactory->kingdomManagement()->assignKingdom([
            'is_capital' => true,
            'x_position' => 16,
            'y_position' => 16,
        ])->getKingdom();
        $targetKingdom = $characterFactory->kingdomManagement()->assignKingdom([
            'current_wood' => 1000,
            'current_clay' => 1000,
            'current_stone' => 1000,
            'current_iron' => 1000,
            'current_population' => 1000,
            'x_position' => 32,
            'y_position' => 16,
        ])->getKingdom();
        $character = $characterFactory->getCharacter();
        $unit = GameUnit::factory()->create(['name' => 'Spearmen']);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdom/capital-city/recruit-unit-requests/' . $character->id . '/' . $capitalCity->id, [
                'request_data' => [[
                    'kingdom_id' => $targetKingdom->id,
                    'unit_requests' => [[
                        'unit_name' => $unit->name,
                        'unit_amount' => 1,
                    ]],
                ]],
            ]);

        $response->assertOk();
        Queue::assertPushed(CapitalCityQueueUpUnitRequests::class);
    }

    public function testCapitalCityRecruitIgnoresExpiredManualQueuedUnit(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $capitalCity = $characterFactory->kingdomManagement()->assignKingdom([
            'is_capital' => true,
            'x_position' => 16,
            'y_position' => 16,
        ])->getKingdom();
        $targetKingdom = $characterFactory->kingdomManagement()->assignKingdom([
            'current_wood' => 1000,
            'current_clay' => 1000,
            'current_stone' => 1000,
            'current_iron' => 1000,
            'current_population' => 1000,
            'x_position' => 32,
            'y_position' => 16,
        ])->getKingdom();
        $character = $characterFactory->getCharacter();
        $unit = GameUnit::factory()->create(['name' => 'Spearmen']);
        UnitInQueue::factory()->create([
            'character_id' => $character->id,
            'kingdom_id' => $targetKingdom->id,
            'game_unit_id' => $unit->id,
            'amount' => 10,
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdom/capital-city/recruit-unit-requests/' . $character->id . '/' . $capitalCity->id, [
                'request_data' => [[
                    'kingdom_id' => $targetKingdom->id,
                    'unit_requests' => [[
                        'unit_name' => $unit->name,
                        'unit_amount' => 1,
                    ]],
                ]],
            ]);

        $response->assertOk();
        Queue::assertPushed(CapitalCityQueueUpUnitRequests::class);
    }
}
