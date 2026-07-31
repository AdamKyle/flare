<?php

namespace Tests\Unit\Game\Maps\Transformers;

use App\Game\Maps\Transformers\LocationTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateLocation;

class LocationTransformerTest extends TestCase
{
    use CreateGameMap, CreateItem, CreateLocation, RefreshDatabase;

    public function test_transform_includes_quest_reward_item(): void
    {
        $gameMap = $this->createGameMap();
        $questRewardItem = $this->createItem([
            'name' => 'Quest Reward',
        ]);
        $location = $this->createLocation([
            'game_map_id' => $gameMap->id,
            'quest_reward_item_id' => $questRewardItem->id,
        ])->load(['map', 'questRewardItem']);

        $result = resolve(LocationTransformer::class)->transform($location);

        $this->assertSame([
            'id' => $questRewardItem->id,
            'affix_name' => $questRewardItem->affix_name,
        ], $result['quest_reward_item']);
    }

    public function test_transform_includes_null_quest_reward_item(): void
    {
        $gameMap = $this->createGameMap();
        $location = $this->createLocation([
            'game_map_id' => $gameMap->id,
            'quest_reward_item_id' => null,
        ])->load(['map', 'questRewardItem']);

        $result = resolve(LocationTransformer::class)->transform($location);

        $this->assertNull($result['quest_reward_item']);
    }

    public function test_transform_includes_game_map_name(): void
    {
        $gameMap = $this->createGameMap([
            'name' => 'Test Map',
        ]);
        $location = $this->createLocation([
            'game_map_id' => $gameMap->id,
        ])->load('map');

        $result = resolve(LocationTransformer::class)->transform($location);

        $this->assertSame($gameMap->name, $result['game_map_name']);
    }
}
