<?php

namespace Tests\Feature\Game\Kingdoms;

use App\Game\Kingdoms\Requests\ResourceRequest;
use App\Game\Kingdoms\Service\ResourceTransferService;
use App\Game\Kingdoms\Values\BuildingCosts;
use App\Game\Kingdoms\Values\UnitNames;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Validator;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class ResourceTransferValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_negative_amount_of_resources_is_rejected(): void
    {
        $validator = Validator::make([
            'kingdom_requesting' => 1,
            'kingdom_requesting_from' => 2,
            'amount_of_resources' => -1,
            'type_of_resource' => 'wood',
        ], (new ResourceRequest)->rules());

        $this->assertTrue($validator->fails());
    }

    public function test_zero_amount_of_resources_is_rejected(): void
    {
        $validator = Validator::make([
            'kingdom_requesting' => 1,
            'kingdom_requesting_from' => 2,
            'amount_of_resources' => 0,
            'type_of_resource' => 'wood',
        ], (new ResourceRequest)->rules());

        $this->assertTrue($validator->fails());
    }

    public function test_source_resources_do_not_increase_after_negative_amount(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $requestingKingdom = $characterFactory->kingdomManagement()->assignKingdom()->getKingdom();
        $providingKingdom = $characterFactory->kingdomManagement()->assignKingdom(['current_wood' => 100])->getKingdom();

        $result = resolve(ResourceTransferService::class)->sendOffResourceRequest($characterFactory->getCharacter(), [
            'kingdom_requesting' => $requestingKingdom->id,
            'kingdom_requesting_from' => $providingKingdom->id,
            'amount_of_resources' => -10,
            'type_of_resource' => 'wood',
            'use_air_ship' => false,
        ]);

        $this->assertSame(422, $result['status']);
        $this->assertSame(100, $providingKingdom->refresh()->current_wood);
    }

    public function test_all_resource_transfer_rejects_negative_amount(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $requestingKingdom = $characterFactory->kingdomManagement()->assignKingdom()->getKingdom();
        $providingKingdom = $characterFactory->kingdomManagement()->assignKingdom(['current_wood' => 100])->getKingdom();

        $result = resolve(ResourceTransferService::class)->sendOffResourceRequest($characterFactory->getCharacter(), [
            'kingdom_requesting' => $requestingKingdom->id,
            'kingdom_requesting_from' => $providingKingdom->id,
            'amount_of_resources' => -10,
            'type_of_resource' => 'all',
            'use_air_ship' => false,
        ]);

        $this->assertSame(422, $result['status']);
        $this->assertSame(100, $providingKingdom->refresh()->current_wood);
    }

    public function test_valid_positive_resource_transfer_still_works(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $requestingKingdom = $characterFactory->kingdomManagement()->assignKingdom()->assignBuilding(['name' => BuildingCosts::MARKET_PLACE], ['level' => 5])->getKingdom();
        $providingKingdom = $characterFactory->kingdomManagement()->assignKingdom(['current_wood' => 100, 'current_population' => 100])->assignBuilding(['name' => BuildingCosts::MARKET_PLACE], ['level' => 5])->assignUnits(['name' => UnitNames::SPEARMEN], 75)->getKingdom();

        $result = resolve(ResourceTransferService::class)->sendOffResourceRequest($characterFactory->getCharacter(), [
            'kingdom_requesting' => $requestingKingdom->id,
            'kingdom_requesting_from' => $providingKingdom->id,
            'amount_of_resources' => 10,
            'type_of_resource' => 'wood',
            'use_air_ship' => false,
        ]);

        $this->assertSame(200, $result['status']);
        $this->assertSame(90, $providingKingdom->refresh()->current_wood);
    }
}
