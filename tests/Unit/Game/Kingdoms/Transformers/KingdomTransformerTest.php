<?php

namespace Tests\Unit\Game\Kingdoms\Transformers;

use App\Flare\Models\CapitalCityBuildingQueue;
use App\Game\Kingdoms\Transformers\KingdomTransformer;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameBuilding;
use Tests\Traits\CreateKingdom;

class KingdomTransformerTest extends TestCase
{
    use CreateGameBuilding, CreateKingdom, RefreshDatabase;

    public function testTransformIncludesEstimatedHourlyProduction(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $kingdom = $this->createKingdom([
            'character_id' => $character->id,
            'game_map_id' => $character->map->game_map_id,
            'treasury' => 0,
            'gold_bars' => 0,
        ]);

        $kingdom->buildings()->create([
            'game_building_id' => $this->createGameBuilding(['is_resource_building' => true, 'increase_stone_amount' => 100, 'increase_clay_amount' => 0, 'increase_wood_amount' => 0, 'increase_iron_amount' => 0])->id,
            'kingdom_id' => $kingdom->id,
            'level' => 1,
            'max_defence' => 100,
            'max_durability' => 100,
            'current_durability' => 100,
            'current_defence' => 100,
        ]);

        $result = resolve(KingdomTransformer::class)->transform($kingdom->refresh());

        $this->assertSame([
            'stone' => 100.0,
            'clay' => 0.0,
            'wood' => 0.0,
            'iron' => 0.0,
            'population' => 0.0,
        ], $result['estimated_hourly_production']);
    }

    public function testTransformIncludesCapitalCityBuildingQueues(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $kingdom = $this->createKingdom([
            'character_id' => $character->id,
            'game_map_id' => $character->map->game_map_id,
            'treasury' => 0,
            'gold_bars' => 0,
        ]);
        $capitalCity = $this->createKingdom([
            'character_id' => $character->id,
            'game_map_id' => $character->map->game_map_id,
            'is_capital' => true,
        ]);
        $building = $kingdom->buildings()->create([
            'game_building_id' => $this->createGameBuilding()->id,
            'kingdom_id' => $kingdom->id,
            'level' => 1,
            'max_defence' => 100,
            'max_durability' => 100,
            'current_durability' => 100,
            'current_defence' => 100,
        ]);
        $startedAt = now()->subMinute();
        $completedAt = now()->addHour();

        CapitalCityBuildingQueue::create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'requested_kingdom' => $capitalCity->id,
            'building_request_data' => [[
                'building_id' => $building->id,
                'building_name' => $building->name,
                'type' => 'upgrade',
                'missing_costs' => [],
                'secondary_status' => CapitalCityQueueStatus::BUILDING,
                'from_level' => $building->level,
                'to_level' => $building->level + 1,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::BUILDING,
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
        ]);

        $result = resolve(KingdomTransformer::class)->transform($kingdom->refresh());

        $this->assertCount(1, $result['building_queue']);
        $this->assertSame($building->id, $result['building_queue'][0]['building_id']);
        $this->assertSame($startedAt->toDateTimeString(), $result['building_queue'][0]['started_at']->toDateTimeString());
        $this->assertSame($completedAt->toDateTimeString(), $result['building_queue'][0]['completed_at']->toDateTimeString());
        $this->assertTrue($result['building_queue'][0]['is_capital_city_managed']);
    }
}
