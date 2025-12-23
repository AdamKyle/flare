<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Handlers;

use App\Flare\Models\Event as FlareEvent;
use App\Flare\Models\Location;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Values\LocationType;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\BattleRewardProcessing\Handlers\PurgatorySmithHouseRewardHandler;
use App\Game\Events\Values\EventType;
use Facades\App\Flare\Calculators\DropCheckCalculator;
use Facades\App\Flare\RandomNumber\RandomNumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterAutomation;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMonster;

class PurgatorySmithHouseRewardHandlerTest extends TestCase
{
    use CreateCharacterAutomation, CreateEvent, CreateItem, CreateItemAffix, CreateLocation, CreateMonster, RefreshDatabase;

    private ?PurgatorySmithHouseRewardHandler $handler;

    public function setUp(): void
    {
        parent::setUp();

        $this->handler = resolve(PurgatorySmithHouseRewardHandler::class);
        Cache::forget('monsters');
    }

    public function tearDown(): void
    {
        \Mockery::close();

        $this->handler = null;
        Cache::forget('monsters');

        parent::tearDown();
    }

    public function testReturnsCharacterWhenLocationDoesNotExist(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $character = $characterFactory->getCharacter();

        $character->update([
            'x_position' => 12,
            'y_position' => 12,
            'gold_dust' => 10,
            'shards' => 20,
            'copper_coins' => 30,
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
        ]);

        $result = $this->handler->handleFightingAtPurgatorySmithHouse($character->refresh(), $monster);

        $result = $result->refresh();

        $this->assertEquals($character->id, $result->id);
        $this->assertEquals(10, $result->gold_dust);
        $this->assertEquals(20, $result->shards);
        $this->assertEquals(30, $result->copper_coins);
    }

    public function testReturnsCharacterWhenLocationTypeIsInvalid(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $character = $characterFactory->getCharacter();

        $character->update([
            'x_position' => 12,
            'y_position' => 12,
            'gold_dust' => 10,
            'shards' => 20,
            'copper_coins' => 30,
        ]);

        $this->createLocation([
            'game_map_id' => $character->map->game_map_id,
            'x' => 12,
            'y' => 12,
            'type' => 999999,
            'name' => 'invalid_type_location',
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
        ]);

        $result = $this->handler->handleFightingAtPurgatorySmithHouse($character->refresh(), $monster);

        $result = $result->refresh();

        $this->assertEquals(10, $result->gold_dust);
        $this->assertEquals(20, $result->shards);
        $this->assertEquals(30, $result->copper_coins);
    }

    public function testReturnsCharacterWhenLocationIsNotSmithHouse(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $character = $characterFactory->getCharacter();

        $character->map()->update([
            'character_position_x' => 12,
            'character_position_y' => 12,
        ]);

        $character = $character->refresh();

        $character->update([
            'gold_dust' => 10,
            'shards' => 20,
            'copper_coins' => 30,
        ]);

        $this->createLocation([
            'game_map_id' => $character->map->game_map_id,
            'x' => 12,
            'y' => 12,
            'type' => LocationType::PURGATORY_DUNGEONS,
            'enemy_strength_type' => 1,
            'name' => 'not_smith_house',
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
        ]);

        $result = $this->handler->handleFightingAtPurgatorySmithHouse($character->refresh(), $monster);

        $result = $result->refresh();

        $this->assertEquals(10, $result->gold_dust);
        $this->assertEquals(20, $result->shards);
        $this->assertEquals(30, $result->copper_coins);
    }

    public function testCurrencyRewardCapsWithoutEventAndWithoutCopperCoinsItem(): void
    {
        RandomNumberGenerator::shouldReceive('generateRandomNumber')->andReturn(1000);

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $character = $characterFactory->getCharacter();

        $character->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST - 10,
            'shards' => MaxCurrenciesValue::MAX_SHARDS - 10,
            'copper_coins' => 123,
        ]);

        $result = $this->handler->currencyReward($character->refresh(), null);

        $this->assertEquals(MaxCurrenciesValue::MAX_GOLD_DUST, $result->gold_dust);
        $this->assertEquals(MaxCurrenciesValue::MAX_SHARDS, $result->shards);
        $this->assertEquals(123, $result->copper_coins);
    }

    public function testCurrencyRewardCapsWithEventAndWithCopperCoinsItem(): void
    {
        RandomNumberGenerator::shouldReceive('generateRandomNumber')->andReturn(5000);

        $copperCoinsItem = $this->createItem([
            'effect' => ItemEffectsValue::GET_COPPER_COINS,
            'type' => 'quest',
        ]);

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $character = $characterFactory->inventoryManagement()->giveItem($copperCoinsItem)->getCharacter();

        $character->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
            'copper_coins' => MaxCurrenciesValue::MAX_COPPER,
        ]);

        $event = $this->createEvent([
            'type' => EventType::PURGATORY_SMITH_HOUSE,
        ]);

        $result = $this->handler->currencyReward($character->refresh(), $event);

        $this->assertEquals(MaxCurrenciesValue::MAX_GOLD_DUST, $result->gold_dust);
        $this->assertEquals(MaxCurrenciesValue::MAX_SHARDS, $result->shards);
        $this->assertEquals(MaxCurrenciesValue::MAX_COPPER, $result->copper_coins);
    }

    public function testHandleRewardsCurrencyButReturnsEarlyWhenAutomationsAreRunning(): void
    {
        RandomNumberGenerator::shouldReceive('generateRandomNumber')->andReturn(10);

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $character = $characterFactory->getCharacter();

        $character->update([
            'x_position' => 12,
            'y_position' => 12,
            'gold_dust' => 0,
            'shards' => 0,
            'copper_coins' => 0,
        ]);

        $location = $this->createSmithHouseLocation($character);

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
        ]);

        Cache::put('monsters', [
            $location->name => [
                ['id' => $monster->id],
            ],
        ]);

        $this->createExploringAutomation([
            'character_id' => $character->id,
        ]);

        $character = $character->refresh();
        $beforeSlots = $character->inventory->slots()->count();

        $result = $this->handler->handleFightingAtPurgatorySmithHouse($character, $monster);

        $result = $result->refresh();
        $afterSlots = $result->inventory->slots()->count();

        $this->assertEquals(10, $result->gold_dust);
        $this->assertEquals(10, $result->shards);
        $this->assertEquals(0, $result->copper_coins);
        $this->assertEquals($beforeSlots, $afterSlots);
    }

    public function testHandleDoesNotRewardItemsWhenMonsterNotInCacheList(): void
    {
        RandomNumberGenerator::shouldReceive('generateRandomNumber')->andReturn(10);

        $this->createItemAffix([
            'type' => 'prefix',
        ]);

        $this->createItemAffix([
            'type' => 'suffix',
        ]);

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $character = $characterFactory->getCharacter();

        $character->update([
            'x_position' => 12,
            'y_position' => 12,
            'gold_dust' => 0,
            'shards' => 0,
            'copper_coins' => 0,
        ]);

        $location = $this->createSmithHouseLocation($character);

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
        ]);

        Cache::put('monsters', [
            $location->name => [
                ['id' => $monster->id + 999],
                ['id' => $monster->id + 1000],
                ['id' => $monster->id + 1001],
                ['id' => $monster->id + 1002],
            ],
        ]);

        $beforeSlots = $character->refresh()->inventory->slots()->count();

        $result = $this->handler->handleFightingAtPurgatorySmithHouse($character->refresh(), $monster);

        $result = $result->refresh();
        $afterSlots = $result->inventory->slots()->count();

        $this->assertEquals(10, $result->gold_dust);
        $this->assertEquals(10, $result->shards);
        $this->assertEquals($beforeSlots, $afterSlots);
        $this->assertFalse(FlareEvent::where('type', EventType::PURGATORY_SMITH_HOUSE)->exists());
    }

    public function testHandleRewardsLegendaryAtHalfwayOrMoreAndDoesNotCreateEventWhenRandomBelowThreshold(): void
    {
        DropCheckCalculator::shouldReceive('fetchDifficultItemChance')->andReturnTrue();

        RandomNumberGenerator::shouldReceive('generateRandomNumber')->andReturn(1);
        RandomNumberGenerator::shouldReceive('generateTrueRandomNumber')->andReturn(0);

        $this->createItemAffix([
            'type' => 'prefix',
        ]);

        $this->createItemAffix([
            'type' => 'suffix',
        ]);

        $this->createItem([
            'specialty_type' => ItemSpecialtyType::PURGATORY_CHAINS,
            'item_prefix_id' => null,
            'item_suffix_id' => null,
            'type' => 'weapon',
        ]);

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $character = $characterFactory->getCharacter();

        $lootingSkill = $character->skills()
            ->whereHas('baseSkill', function ($query) {
                $query->where('name', 'Looting');
            })->first();

        if (! is_null($lootingSkill)) {
            $lootingSkill->update([
                'skill_bonus' => 0.20,
            ]);
        }

        $character->update([
            'x_position' => 12,
            'y_position' => 12,
        ]);

        $location = $this->createSmithHouseLocation($character);

        $monsters = [
            $this->createMonster(['game_map_id' => $character->map->game_map_id]),
            $this->createMonster(['game_map_id' => $character->map->game_map_id]),
            $this->createMonster(['game_map_id' => $character->map->game_map_id]),
            $this->createMonster(['game_map_id' => $character->map->game_map_id]),
        ];

        $targetMonster = $monsters[2];

        Cache::put('monsters', [
            $location->name => [
                ['id' => $monsters[0]->id],
                ['id' => $monsters[1]->id],
                ['id' => $monsters[2]->id],
                ['id' => $monsters[3]->id],
            ],
        ]);

        $beforeSlots = $character->refresh()->inventory->slots()->count();

        $result = $this->handler->handleFightingAtPurgatorySmithHouse($character->refresh(), $targetMonster);

        $result = $result->refresh();
        $afterSlots = $result->inventory->slots()->count();

        $this->assertEquals($beforeSlots + 1, $afterSlots);

        $newItem = $result->inventory->slots()->latest('id')->first()->item;

        $this->assertFalse((bool) $newItem->is_mythic);
        $this->assertNotNull($newItem->item_prefix_id);
        $this->assertNotNull($newItem->item_suffix_id);
        $this->assertFalse(FlareEvent::where('type', EventType::PURGATORY_SMITH_HOUSE)->exists());
    }

    public function testHandleDoesNotRewardItemWhenDropCheckFails(): void
    {
        RandomNumberGenerator::shouldReceive('generateRandomNumber')->andReturn(1000);
        RandomNumberGenerator::shouldReceive('generateTrueRandomNumber')->andReturn(0);

        $this->createItemAffix([
            'type' => 'prefix',
        ]);

        $this->createItemAffix([
            'type' => 'suffix',
        ]);

        $this->createItem([
            'specialty_type' => ItemSpecialtyType::PURGATORY_CHAINS,
            'item_prefix_id' => null,
            'item_suffix_id' => null,
            'type' => 'weapon',
        ]);

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $character = $characterFactory->getCharacter();

        $lootingSkill = $character->skills()
            ->whereHas('baseSkill', function ($query) {
                $query->where('name', 'Looting');
            })->first();

        if (! is_null($lootingSkill)) {
            $lootingSkill->update([
                'skill_bonus' => 0.0,
            ]);
        }

        $character->update([
            'x_position' => 12,
            'y_position' => 12,
        ]);

        $location = $this->createSmithHouseLocation($character);

        $monsters = [
            $this->createMonster(['game_map_id' => $character->map->game_map_id]),
            $this->createMonster(['game_map_id' => $character->map->game_map_id]),
            $this->createMonster(['game_map_id' => $character->map->game_map_id]),
            $this->createMonster(['game_map_id' => $character->map->game_map_id]),
        ];

        $targetMonster = $monsters[2];

        Cache::put('monsters', [
            $location->name => [
                ['id' => $monsters[0]->id],
                ['id' => $monsters[1]->id],
                ['id' => $monsters[2]->id],
                ['id' => $monsters[3]->id],
            ],
        ]);

        $beforeSlots = $character->refresh()->inventory->slots()->count();

        $result = $this->handler->handleFightingAtPurgatorySmithHouse($character->refresh(), $targetMonster);

        $result = $result->refresh();
        $afterSlots = $result->inventory->slots()->count();

        $this->assertEquals($beforeSlots, $afterSlots);
    }

    public function testHandleDoesNotRewardItemWhenInventoryIsFull(): void
    {
        DropCheckCalculator::shouldReceive('fetchDifficultItemChance')->andReturnTrue();

        RandomNumberGenerator::shouldReceive('generateRandomNumber')->andReturn(1);
        RandomNumberGenerator::shouldReceive('generateTrueRandomNumber')->andReturn(0);

        $this->createItemAffix([
            'type' => 'prefix',
        ]);

        $this->createItemAffix([
            'type' => 'suffix',
        ]);

        $this->createItem([
            'specialty_type' => ItemSpecialtyType::PURGATORY_CHAINS,
            'item_prefix_id' => null,
            'item_suffix_id' => null,
            'type' => 'weapon',
        ]);

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $character = $characterFactory->getCharacter();

        $character->update([
            'x_position' => 12,
            'y_position' => 12,
        ]);

        $character = $this->fillInventoryUntilFull($character);

        $location = $this->createSmithHouseLocation($character);

        $monsters = [
            $this->createMonster(['game_map_id' => $character->map->game_map_id]),
            $this->createMonster(['game_map_id' => $character->map->game_map_id]),
            $this->createMonster(['game_map_id' => $character->map->game_map_id]),
            $this->createMonster(['game_map_id' => $character->map->game_map_id]),
        ];

        $targetMonster = $monsters[2];

        Cache::put('monsters', [
            $location->name => [
                ['id' => $monsters[0]->id],
                ['id' => $monsters[1]->id],
                ['id' => $monsters[2]->id],
                ['id' => $monsters[3]->id],
            ],
        ]);

        $beforeSlots = $character->refresh()->inventory->slots()->count();

        $result = $this->handler->handleFightingAtPurgatorySmithHouse($character->refresh(), $targetMonster);

        $result = $result->refresh();
        $afterSlots = $result->inventory->slots()->count();

        $this->assertEquals($beforeSlots, $afterSlots);
    }

    public function testHandleDoesNotAddSlotWhenNoEligiblePurgatoryChainsItemExists(): void
    {
        RandomNumberGenerator::shouldReceive('generateRandomNumber')->andReturn(1);
        RandomNumberGenerator::shouldReceive('generateTrueRandomNumber')->andReturn(0);

        $this->createItemAffix([
            'type' => 'prefix',
        ]);

        $this->createItemAffix([
            'type' => 'suffix',
        ]);

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $character = $characterFactory->getCharacter();

        $character->update([
            'x_position' => 12,
            'y_position' => 12,
        ]);

        $location = $this->createSmithHouseLocation($character);

        $monsters = [
            $this->createMonster(['game_map_id' => $character->map->game_map_id]),
            $this->createMonster(['game_map_id' => $character->map->game_map_id]),
            $this->createMonster(['game_map_id' => $character->map->game_map_id]),
            $this->createMonster(['game_map_id' => $character->map->game_map_id]),
        ];

        $targetMonster = $monsters[2];

        Cache::put('monsters', [
            $location->name => [
                ['id' => $monsters[0]->id],
                ['id' => $monsters[1]->id],
                ['id' => $monsters[2]->id],
                ['id' => $monsters[3]->id],
            ],
        ]);

        $beforeSlots = $character->refresh()->inventory->slots()->count();

        $result = $this->handler->handleFightingAtPurgatorySmithHouse($character->refresh(), $targetMonster);

        $result = $result->refresh();
        $afterSlots = $result->inventory->slots()->count();

        $this->assertEquals($beforeSlots, $afterSlots);
    }

    public function testHandleFinalMonsterRewardsLegendaryAndMythicAndCreatesEvent(): void
    {
        DropCheckCalculator::shouldReceive('fetchDifficultItemChance')->andReturnTrue();

        RandomNumberGenerator::shouldReceive('generateRandomNumber')->andReturn(1);
        RandomNumberGenerator::shouldReceive('generateTrueRandomNumber')->andReturn(90);

        $this->createItemAffix([
            'type' => 'prefix',
        ]);

        $this->createItemAffix([
            'type' => 'suffix',
        ]);

        $this->createItem([
            'specialty_type' => ItemSpecialtyType::PURGATORY_CHAINS,
            'item_prefix_id' => null,
            'item_suffix_id' => null,
            'type' => 'weapon',
        ]);

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $character = $characterFactory->getCharacter();

        $character->update([
            'x_position' => 12,
            'y_position' => 12,
        ]);

        $location = $this->createSmithHouseLocation($character);

        $monsters = [
            $this->createMonster(['game_map_id' => $character->map->game_map_id]),
            $this->createMonster(['game_map_id' => $character->map->game_map_id]),
            $this->createMonster(['game_map_id' => $character->map->game_map_id]),
            $this->createMonster(['game_map_id' => $character->map->game_map_id]),
        ];

        $targetMonster = $monsters[3];

        Cache::put('monsters', [
            $location->name => [
                ['id' => $monsters[0]->id],
                ['id' => $monsters[1]->id],
                ['id' => $monsters[2]->id],
                ['id' => $monsters[3]->id],
            ],
        ]);

        $beforeSlots = $character->refresh()->inventory->slots()->count();

        $result = $this->handler->handleFightingAtPurgatorySmithHouse($character->refresh(), $targetMonster);

        $result = $result->refresh();
        $afterSlots = $result->inventory->slots()->count();

        $this->assertEquals($beforeSlots + 2, $afterSlots);
        $this->assertTrue(FlareEvent::where('type', EventType::PURGATORY_SMITH_HOUSE)->exists());

        $items = $result->inventory->slots()->latest('id')->take(2)->get()->pluck('item');

        $this->assertTrue($items->contains(function ($item) {
            return (bool) $item->is_mythic;
        }));

        $this->assertTrue($items->contains(function ($item) {
            return ! $item->is_mythic;
        }));
    }

    private function createSmithHouseLocation($character): Location
    {
        return $this->createLocation([
            'game_map_id' => $character->map->game_map_id,
            'x' => $character->x_position,
            'y' => $character->y_position,
            'type' => LocationType::PURGATORY_SMITH_HOUSE,
            'name' => 'smith_house_' . uniqid('', true),
        ]);
    }

    private function fillInventoryUntilFull($character)
    {
        $iterations = 0;

        while (! $character->isInventoryFull() && $iterations < 250) {
            $item = $this->createItem([
                'type' => 'weapon',
            ]);

            $character->inventory->slots()->create([
                'inventory_id' => $character->inventory->id,
                'item_id' => $item->id,
            ]);

            $character = $character->refresh();
            $iterations++;
        }

        return $character->refresh();
    }

    public function testHandleClampsLootingChanceWhenOverCap(): void
    {
        RandomNumberGenerator::shouldReceive('generateRandomNumber')->andReturn(1);
        RandomNumberGenerator::shouldReceive('generateTrueRandomNumber')->andReturn(0);

        DropCheckCalculator::shouldReceive('fetchDifficultItemChance')->andReturnTrue();

        $this->createItemAffix([
            'type' => 'prefix',
        ]);

        $this->createItemAffix([
            'type' => 'suffix',
        ]);

        $this->createItem([
            'specialty_type' => ItemSpecialtyType::PURGATORY_CHAINS,
            'item_prefix_id' => null,
            'item_suffix_id' => null,
            'type' => 'weapon',
        ]);

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $character = $characterFactory->getCharacter();

        $character->map()->update([
            'character_position_x' => 12,
            'character_position_y' => 12,
        ]);

        $location = $this->createSmithHouseLocation($character->refresh());

        $lootingSkill = $character->skills()
            ->whereHas('baseSkill', function ($query) {
                $query->where('name', 'Looting');
            })->first();

        if (! is_null($lootingSkill)) {
            $lootingSkill->level = 999;
            $lootingSkill->save();
        }

        $character = $character->refresh();

        $monsters = [
            $this->createMonster(['game_map_id' => $character->map->game_map_id]),
            $this->createMonster(['game_map_id' => $character->map->game_map_id]),
            $this->createMonster(['game_map_id' => $character->map->game_map_id]),
            $this->createMonster(['game_map_id' => $character->map->game_map_id]),
        ];

        $targetMonster = $monsters[3];

        Cache::put('monsters', [
            $location->name => [
                ['id' => $monsters[0]->id],
                ['id' => $monsters[1]->id],
                ['id' => $monsters[2]->id],
                ['id' => $monsters[3]->id],
            ],
        ]);

        $beforeSlots = $character->inventory->slots()->count();

        $result = $this->handler->handleFightingAtPurgatorySmithHouse($character->refresh(), $targetMonster)->refresh();

        $afterSlots = $result->inventory->slots()->count();

        $this->assertEquals($beforeSlots + 2, $afterSlots);
    }

    public function testHandleUsesEventOverridesLootingChanceAndHalvesMaxRoll(): void
    {
        $this->createEvent([
            'type' => EventType::PURGATORY_SMITH_HOUSE,
        ]);

        DropCheckCalculator::shouldReceive('fetchDifficultItemChance')
            ->once()
            ->withArgs(function ($chance, $maxRoll) {
                return abs($chance - 0.30) < 0.00001 && $maxRoll === 250;
            })
            ->andReturnFalse();

        RandomNumberGenerator::shouldReceive('generateRandomNumber')->andReturn(1);

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $character = $characterFactory->getCharacter();

        $character->update([
            'x_position' => 12,
            'y_position' => 12,
        ]);

        $location = $this->createSmithHouseLocation($character);

        $monsters = [
            $this->createMonster(['game_map_id' => $character->map->game_map_id]),
            $this->createMonster(['game_map_id' => $character->map->game_map_id]),
            $this->createMonster(['game_map_id' => $character->map->game_map_id]),
            $this->createMonster(['game_map_id' => $character->map->game_map_id]),
        ];

        $targetMonster = $monsters[2];

        Cache::put('monsters', [
            $location->name => [
                ['id' => $monsters[0]->id],
                ['id' => $monsters[1]->id],
                ['id' => $monsters[2]->id],
                ['id' => $monsters[3]->id],
            ],
        ]);

        $beforeSlots = $character->refresh()->inventory->slots()->count();

        $result = $this->handler->handleFightingAtPurgatorySmithHouse($character->refresh(), $targetMonster)->refresh();

        $afterSlots = $result->inventory->slots()->count();

        $this->assertEquals($beforeSlots, $afterSlots);
    }

    public function testHandleDoesNotAddSlotWhenRewardItemQueryReturnsNull(): void
    {
        DropCheckCalculator::shouldReceive('fetchDifficultItemChance')->once()->andReturnTrue();

        RandomNumberGenerator::shouldReceive('generateRandomNumber')->andReturn(1);
        RandomNumberGenerator::shouldReceive('generateTrueRandomNumber')->andReturn(0);

        $this->createItem([
            'specialty_type' => ItemSpecialtyType::PURGATORY_CHAINS,
            'type' => 'alchemy',
            'item_prefix_id' => null,
            'item_suffix_id' => null,
        ]);

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $character = $characterFactory->getCharacter();

        $character->update([
            'x_position' => 12,
            'y_position' => 12,
        ]);

        $location = $this->createSmithHouseLocation($character);

        $monsters = [
            $this->createMonster(['game_map_id' => $character->map->game_map_id]),
            $this->createMonster(['game_map_id' => $character->map->game_map_id]),
            $this->createMonster(['game_map_id' => $character->map->game_map_id]),
            $this->createMonster(['game_map_id' => $character->map->game_map_id]),
        ];

        $targetMonster = $monsters[2];

        Cache::put('monsters', [
            $location->name => [
                ['id' => $monsters[0]->id],
                ['id' => $monsters[1]->id],
                ['id' => $monsters[2]->id],
                ['id' => $monsters[3]->id],
            ],
        ]);

        $beforeSlots = $character->refresh()->inventory->slots()->count();

        $result = $this->handler->handleFightingAtPurgatorySmithHouse($character->refresh(), $targetMonster)->refresh();

        $afterSlots = $result->inventory->slots()->count();

        $this->assertEquals($beforeSlots, $afterSlots);
    }
}
