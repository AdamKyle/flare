<?php

namespace Tests\Feature\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\BuildingInQueue;
use App\Flare\Values\AutomationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class KingdomAutomationRestrictionTest extends TestCase
{
    use RefreshDatabase;

    public function testKingdomDetailsCanBeFetchedDuringExploration(): void
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
            ->call('GET', '/api/player-kingdom/' . $character->id . '/' . $kingdom->id);

        $response->assertOk();
        $response->assertJsonPath('kingdom.id', $kingdom->id);
    }

    public function testKingdomListCanBeFetchedDuringExploration(): void
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
            ->call('GET', '/api/player-kingdoms/' . $character->id);

        $response->assertOk();
        $this->assertArrayHasKey('kingdoms', $response->json());
    }

    public function testKingdomDetailsCanBeFetchedDuringDelve(): void
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
            ->call('GET', '/api/player-kingdom/' . $character->id . '/' . $kingdom->id);

        $response->assertOk();
        $response->assertJsonPath('kingdom.id', $kingdom->id);
    }

    public function testKingdomDetailsCanBeFetchedDuringFactionLoyalty(): void
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
            ->call('GET', '/api/player-kingdom/' . $character->id . '/' . $kingdom->id);

        $response->assertOk();
        $response->assertJsonPath('kingdom.id', $kingdom->id);
    }

    public function testKingdomMutationRejectsDuringAutomation(): void
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
            ->call('POST', '/api/kingdoms/' . $character->id . '/upgrade-building/' . $building->id, [
                'to_level' => $building->level + 1,
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'You cannot do that while Exploration automation is running. Cancel it first.',
        ]);
        $this->assertSame(0, BuildingInQueue::where('kingdom_id', $kingdom->id)->count());
    }
}
