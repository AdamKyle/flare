<?php

namespace Tests\Unit\Game\Kingdoms\Services;

use App\Game\Kingdoms\Service\ResourceTransferService;
use App\Game\Kingdoms\Values\BuildingCosts;
use App\Game\Kingdoms\Values\UnitNames;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class ResourceTransferServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_exact_resource_transfer_spend_is_allowed(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $requestingKingdom = $characterFactory->kingdomManagement()->assignKingdom()->assignBuilding(['name' => BuildingCosts::MARKET_PLACE], ['level' => 5])->getKingdom();
        $providingKingdom = $characterFactory->kingdomManagement()->assignKingdom(['current_wood' => 10, 'current_population' => 100])->assignBuilding(['name' => BuildingCosts::MARKET_PLACE], ['level' => 5])->assignUnits(['name' => UnitNames::SPEARMEN], 75)->getKingdom();

        $result = resolve(ResourceTransferService::class)->sendOffResourceRequest($characterFactory->getCharacter(), [
            'kingdom_requesting' => $requestingKingdom->id,
            'kingdom_requesting_from' => $providingKingdom->id,
            'amount_of_resources' => 10,
            'type_of_resource' => 'wood',
            'use_air_ship' => false,
        ]);

        $this->assertSame(200, $result['status']);
        $this->assertSame(0, $providingKingdom->refresh()->current_wood);
    }

    public function test_overdrawn_resource_transfer_is_rejected_without_mutating_resources(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $requestingKingdom = $characterFactory->kingdomManagement()->assignKingdom()->assignBuilding(['name' => BuildingCosts::MARKET_PLACE], ['level' => 5])->getKingdom();
        $providingKingdom = $characterFactory->kingdomManagement()->assignKingdom(['current_wood' => 9, 'current_population' => 100])->assignBuilding(['name' => BuildingCosts::MARKET_PLACE], ['level' => 5])->assignUnits(['name' => UnitNames::SPEARMEN], 75)->getKingdom();

        $result = resolve(ResourceTransferService::class)->sendOffResourceRequest($characterFactory->getCharacter(), [
            'kingdom_requesting' => $requestingKingdom->id,
            'kingdom_requesting_from' => $providingKingdom->id,
            'amount_of_resources' => 10,
            'type_of_resource' => 'wood',
            'use_air_ship' => false,
        ]);

        $this->assertSame(422, $result['status']);
        $this->assertSame(9, $providingKingdom->refresh()->current_wood);
    }
}
