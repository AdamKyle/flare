<?php

namespace Tests\Unit\Flare\Models;

use App\Game\Core\Items\Values\ItemEffectType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;

class GameMapTest extends TestCase
{
    use CreateGameMap, CreateItem, RefreshDatabase;

    public function test_map_required_item_is_null_when_map_does_not_require_an_item(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Surface']);

        $this->assertNull($gameMap->map_required_item);
    }

    public function test_map_required_item_is_null_when_required_item_does_not_exist(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Labyrinth']);

        $this->assertNull($gameMap->map_required_item);
    }

    public function test_map_required_item_returns_quest_item_data_when_required_item_exists(): void
    {
        $item = $this->createItem(['effect' => ItemEffectType::LABYRINTH->value]);

        $gameMap = $this->createGameMap(['name' => 'Labyrinth']);

        $mapRequiredItem = $gameMap->map_required_item;

        $this->assertNotNull($mapRequiredItem);
        $this->assertSame($item->id, $mapRequiredItem['item_id']);
    }
}
