<?php

namespace Tests\Feature\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\BuildingExpansionQueue;
use App\Flare\Models\KingdomBuildingExpansion;
use App\Game\Kingdoms\Values\ResourceBuildingExpansionBaseValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class ResourceBuildingExpansionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_start_own_resource_building_expansion(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'current_wood' => 31000,
                'current_clay' => 31000,
                'current_stone' => 31000,
                'current_iron' => 31000,
                'current_steel' => 16000,
                'current_population' => 1000,
                'gold_bars' => 100,
            ])
            ->assignBuilding([
                'is_resource_building' => true,
                'increase_wood_amount' => 100,
            ]);
        $character = $kingdomManagement->getCharacter();
        $kingdom = $kingdomManagement->getKingdom();
        $building = $kingdom->buildings()->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdom/building-expansion/expand/'.$building->id.'/'.$character->id);

        $response->assertOk();
        $this->assertSame(1, BuildingExpansionQueue::where('building_id', $building->id)->count());
        $this->assertSame(0, $kingdom->refresh()->current_wood);
        $this->assertSame(0, $kingdom->refresh()->current_clay);
        $this->assertSame(0, $kingdom->refresh()->current_stone);
        $this->assertSame(0, $kingdom->refresh()->current_iron);
        $this->assertSame(0, $kingdom->refresh()->current_steel);
    }

    public function test_owner_can_cancel_own_resource_building_expansion(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'current_wood' => 1000,
                'max_wood' => 2000,
                'gold_bars' => 100,
            ])
            ->assignBuilding([
                'is_resource_building' => true,
                'increase_wood_amount' => 100,
            ]);
        $character = $kingdomManagement->getCharacter();
        $kingdom = $kingdomManagement->getKingdom();
        $building = $kingdom->buildings()->first();
        KingdomBuildingExpansion::create([
            'kingdom_building_id' => $building->id,
            'kingdom_id' => $kingdom->id,
            'expansion_type' => 0,
            'expansion_count' => 1,
            'expansions_left' => 7,
            'minutes_until_next_expansion' => 30,
            'resource_costs' => ['wood' => 100],
            'gold_bars_cost' => 100,
            'resource_increases' => ResourceBuildingExpansionBaseValue::BASE_RESOURCE_GAIN,
        ]);
        $queue = BuildingExpansionQueue::create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'building_id' => $building->id,
            'started_at' => now()->subMinutes(10),
            'completed_at' => now()->addMinutes(50),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdom/building-expansion/cancel-expand/'.$building->id.'/'.$character->id);

        $response->assertOk();
        $this->assertNull(BuildingExpansionQueue::find($queue->id));
        $this->assertGreaterThan(1000, $kingdom->refresh()->current_wood);
    }

    public function test_non_owner_cannot_expand_another_characters_resource_building(): void
    {
        Queue::fake();

        $ownerFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdomManagement = $ownerFactory
            ->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding();
        $kingdom = $kingdomManagement->getKingdom();
        $building = $kingdom->buildings()->first();

        $nonOwner = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $response = $this->actingAs($nonOwner->user)
            ->call('POST', '/api/kingdom/building-expansion/expand/'.$building->id.'/'.$nonOwner->id,
                [], [], [], ['HTTP_ACCEPT' => 'application/json']
            );

        $response->assertStatus(422);
        $response->assertJson(['error' => 'Nope. Not allowed to do that.']);
        $this->assertSame(0, BuildingExpansionQueue::where('building_id', $building->id)->count());
        $this->assertSame($kingdom->current_wood, $kingdom->refresh()->current_wood);
        $this->assertSame($kingdom->current_clay, $kingdom->refresh()->current_clay);
        $this->assertSame($kingdom->current_stone, $kingdom->refresh()->current_stone);
        $this->assertSame($kingdom->current_iron, $kingdom->refresh()->current_iron);
        $this->assertSame($kingdom->current_steel, $kingdom->refresh()->current_steel);
    }

    public function test_non_owner_cannot_cancel_another_characters_resource_building_expansion(): void
    {
        Queue::fake();

        $ownerFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdomManagement = $ownerFactory
            ->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding();
        $kingdom = $kingdomManagement->getKingdom();
        $building = $kingdom->buildings()->first();
        KingdomBuildingExpansion::create([
            'kingdom_building_id' => $building->id,
            'kingdom_id' => $kingdom->id,
            'expansion_type' => 0,
            'expansion_count' => 1,
            'expansions_left' => 7,
            'minutes_until_next_expansion' => 30,
            'resource_costs' => ['wood' => 100],
            'gold_bars_cost' => 100,
            'resource_increases' => ResourceBuildingExpansionBaseValue::BASE_RESOURCE_GAIN,
        ]);
        $queue = BuildingExpansionQueue::create([
            'character_id' => $kingdomManagement->getCharacter()->id,
            'kingdom_id' => $kingdom->id,
            'building_id' => $building->id,
            'started_at' => now()->subMinutes(10),
            'completed_at' => now()->addMinutes(50),
        ]);

        $nonOwner = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $response = $this->actingAs($nonOwner->user)
            ->call('POST', '/api/kingdom/building-expansion/cancel-expand/'.$building->id.'/'.$nonOwner->id,
                [], [], [], ['HTTP_ACCEPT' => 'application/json']
            );

        $response->assertStatus(422);
        $response->assertJson(['error' => 'Nope. Not allowed to do that.']);
        $this->assertNotNull(BuildingExpansionQueue::find($queue->id));
        $this->assertSame($kingdom->current_wood, $kingdom->refresh()->current_wood);
    }
}
