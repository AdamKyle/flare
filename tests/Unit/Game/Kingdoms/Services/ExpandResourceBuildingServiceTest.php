<?php

namespace Tests\Unit\Game\Kingdoms\Services;

use App\Flare\Models\KingdomBuildingExpansion;
use App\Game\Kingdoms\Service\ExpandResourceBuildingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameBuilding;

class ExpandResourceBuildingServiceTest extends TestCase
{
    use CreateGameBuilding, RefreshDatabase;

    public function testCancelExpansionReturnsNoExpansionInProgressWhenNoQueueExists(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdom = $characterFactory->kingdomManagement()->assignKingdom()->getKingdom();
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
        KingdomBuildingExpansion::create([
            'kingdom_building_id' => $building->id,
            'kingdom_id' => $kingdom->id,
            'expansion_type' => 0,
            'expansion_count' => 1,
            'expansions_left' => 7,
            'minutes_until_next_expansion' => 30,
            'resource_costs' => ['wood' => 100],
            'gold_bars_cost' => 100,
            'resource_increases' => 100,
        ]);

        $result = resolve(ExpandResourceBuildingService::class)->cancelExpansion($building->refresh());

        $this->assertSame('There is no expansion in progress to cancel.', $result['message']);
        $this->assertSame(422, $result['status']);
    }
}
