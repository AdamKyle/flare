<?php

namespace Tests\Unit\Game\Core\Services;

use App\Flare\Values\LocationType;
use App\Game\Core\Services\DropCheckService;
use Facades\App\Flare\Calculators\DropCheckCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMonster;

class DropCheckServiceLocationTypeTest extends TestCase
{
    use CreateItem, CreateLocation, CreateMonster, RefreshDatabase;

    public function test_manual_quest_item_drops_use_location_type(): void
    {
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $character->map->update([
            'character_position_x' => 16,
            'character_position_y' => 16,
        ]);

        $location = $this->createLocation([
            'game_map_id' => $character->map->game_map_id,
            'x' => 16,
            'y' => 16,
            'type' => LocationType::SPECIAL->value,
        ]);
        $questItem = $this->createItem([
            'type' => 'quest',
            'drop_location_id' => $location->id,
            'item_suffix_id' => null,
            'item_prefix_id' => null,
        ]);
        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'drop_check' => 0,
        ]);

        DropCheckCalculator::shouldReceive('fetchDropCheckChance')->andReturn(false);
        DropCheckCalculator::shouldReceive('fetchDifficultItemChance')->andReturn(true);

        $plan = resolve(DropCheckService::class)->planDrops($character->refresh(), $monster, 1, 1.0);

        $this->assertTrue(collect($plan['drops'])->contains(function (array $drop) use ($questItem): bool {
            return $drop['item_id'] === $questItem->id
                && $drop['source'] === 'special_location_quest_drop';
        }));
    }
}
