<?php

namespace Tests\Unit\Game\Maps\Transformers;

use App\Flare\Models\GameMap;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Game\Maps\Transformers\LocationTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationTransformerTest extends TestCase
{
    use RefreshDatabase;

    public function testTransformIncludesQuestRewardItem(): void
    {
        $gameMap = GameMap::factory()->create();
        $questRewardItem = Item::factory()->create([
            'name' => 'Quest Reward',
        ]);
        $location = Location::factory()->create([
            'game_map_id' => $gameMap->id,
            'quest_reward_item_id' => $questRewardItem->id,
        ])->load(['map', 'questRewardItem']);

        $result = resolve(LocationTransformer::class)->transform($location);

        $this->assertSame([
            'id' => $questRewardItem->id,
            'affix_name' => $questRewardItem->affix_name,
        ], $result['quest_reward_item']);
    }

    public function testTransformIncludesNullQuestRewardItem(): void
    {
        $gameMap = GameMap::factory()->create();
        $location = Location::factory()->create([
            'game_map_id' => $gameMap->id,
            'quest_reward_item_id' => null,
        ])->load(['map', 'questRewardItem']);

        $result = resolve(LocationTransformer::class)->transform($location);

        $this->assertNull($result['quest_reward_item']);
    }

    public function testTransformIncludesGameMapName(): void
    {
        $gameMap = GameMap::factory()->create([
            'name' => 'Test Map',
        ]);
        $location = Location::factory()->create([
            'game_map_id' => $gameMap->id,
        ])->load('map');

        $result = resolve(LocationTransformer::class)->transform($location);

        $this->assertSame($gameMap->name, $result['game_map_name']);
    }
}
