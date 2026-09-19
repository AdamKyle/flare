<?php

namespace Tests\Unit\Game\Core\Services;

use App\Flare\Models\Item;
use App\Game\Battle\Services\BattleDrop;
use App\Game\Core\Services\DropCheckService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateMonster;

class DropCheckServiceApplyPlannedDropsTest extends TestCase
{
    use CreateItem, CreateMonster, MockeryPHPUnitIntegration, RefreshDatabase;

    public function test_duplicate_planned_item_ids_are_applied_as_separate_rewards(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $item = $this->createItem(['type' => 'weapon']);
        $appliedItemIds = [];
        $battleDrop = Mockery::mock(BattleDrop::class);
        $battleDrop->shouldReceive('setMonster')->once()->andReturnSelf();
        $battleDrop->shouldReceive('setSpecialLocation')->once()->with(null)->andReturnSelf();
        $battleDrop->shouldReceive('setGameMapBonus')->once()->andReturnSelf();
        $battleDrop->shouldReceive('setLootingChance')->once()->andReturnSelf();
        $battleDrop->shouldReceive('resetRewardTotals')->once()->andReturnSelf();
        $battleDrop->shouldReceive('applyPlannedItem')
            ->twice()
            ->withArgs(function ($char, Item $appliedItem, bool $isMythic) use (&$appliedItemIds, $item): bool {
                $appliedItemIds[] = $appliedItem->id;

                return $appliedItem->id === $item->id && $isMythic === false;
            });
        $battleDrop->shouldReceive('rewardTotals')->once()->andReturn([]);
        $this->app->instance(BattleDrop::class, $battleDrop);

        resolve(DropCheckService::class)->applyPlannedDrops($character, $monster, [
            'game_map_bonus' => 0.0,
            'looting_chance' => 0.0,
            'drops' => [
                ['item_id' => $item->id, 'is_mythic' => false, 'source' => 'monster_drop'],
                ['item_id' => $item->id, 'is_mythic' => false, 'source' => 'monster_drop'],
            ],
        ]);

        $this->assertSame([$item->id, $item->id], $appliedItemIds);
    }

    public function test_a_missing_planned_item_is_skipped_without_blocking_other_planned_items(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $item = $this->createItem(['type' => 'weapon']);
        $battleDrop = Mockery::mock(BattleDrop::class);
        $battleDrop->shouldReceive('setMonster')->once()->andReturnSelf();
        $battleDrop->shouldReceive('setSpecialLocation')->once()->with(null)->andReturnSelf();
        $battleDrop->shouldReceive('setGameMapBonus')->once()->andReturnSelf();
        $battleDrop->shouldReceive('setLootingChance')->once()->andReturnSelf();
        $battleDrop->shouldReceive('resetRewardTotals')->once()->andReturnSelf();
        $battleDrop->shouldReceive('applyPlannedItem')
            ->once()
            ->withArgs(function ($char, Item $appliedItem, bool $isMythic) use ($item): bool {
                return $appliedItem->id === $item->id;
            });
        $battleDrop->shouldReceive('rewardTotals')->once()->andReturn([]);
        $this->app->instance(BattleDrop::class, $battleDrop);

        resolve(DropCheckService::class)->applyPlannedDrops($character, $monster, [
            'game_map_bonus' => 0.0,
            'looting_chance' => 0.0,
            'drops' => [
                ['item_id' => 999999999, 'is_mythic' => false, 'source' => 'monster_drop'],
                ['item_id' => $item->id, 'is_mythic' => false, 'source' => 'monster_drop'],
            ],
        ]);
    }

    public function test_a_planned_mythic_item_is_applied_through_the_mythic_routing_flag(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $monster = $this->createMonster(['game_map_id' => $character->map->game_map_id]);
        $item = $this->createItem(['type' => 'weapon']);
        $battleDrop = Mockery::mock(BattleDrop::class);
        $battleDrop->shouldReceive('setMonster')->once()->andReturnSelf();
        $battleDrop->shouldReceive('setSpecialLocation')->once()->with(null)->andReturnSelf();
        $battleDrop->shouldReceive('setGameMapBonus')->once()->andReturnSelf();
        $battleDrop->shouldReceive('setLootingChance')->once()->andReturnSelf();
        $battleDrop->shouldReceive('resetRewardTotals')->once()->andReturnSelf();
        $battleDrop->shouldReceive('applyPlannedItem')
            ->once()
            ->withArgs(function ($char, Item $appliedItem, bool $isMythic) use ($item): bool {
                return $appliedItem->id === $item->id && $isMythic === true;
            });
        $battleDrop->shouldReceive('rewardTotals')->once()->andReturn([]);
        $this->app->instance(BattleDrop::class, $battleDrop);

        resolve(DropCheckService::class)->applyPlannedDrops($character, $monster, [
            'game_map_bonus' => 0.0,
            'looting_chance' => 0.0,
            'drops' => [
                ['item_id' => $item->id, 'is_mythic' => true, 'source' => 'king_celestial_mythic'],
            ],
        ]);
    }
}
