<?php

namespace Tests\Unit\Game\Maps\Transformers;

use Tests\Traits\CreateLocation;

use Tests\Traits\CreateItem;

use Tests\Traits\CreateGameMap;

use App\Flare\Models\GameMap;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Game\Maps\Transformers\LocationTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationTransformerTest extends TestCase
{
    use CreateGameMap, CreateItem, CreateLocation, RefreshDatabase;

    public function testTransformIncludesQuestRewardItem(): void
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

    public function testTransformIncludesNullQuestRewardItem(): void
    {
        $gameMap = $this->createGameMap();
        $location = $this->createLocation([
            'game_map_id' => $gameMap->id,
            'quest_reward_item_id' => null,
        ])->load(['map', 'questRewardItem']);

        $result = resolve(LocationTransformer::class)->transform($location);

        $this->assertNull($result['quest_reward_item']);
    }

    public function testTransformIncludesGameMapName(): void
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
