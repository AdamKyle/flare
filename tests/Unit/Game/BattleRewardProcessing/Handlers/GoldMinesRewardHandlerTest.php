<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Handlers;

use App\Flare\Models\Event as FlareEvent;
use App\Flare\Models\Location;
use App\Flare\Values\LocationType;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\BattleRewardProcessing\Handlers\GoldMinesRewardHandler;
use App\Game\Events\Values\EventType;
use Facades\App\Flare\Calculators\DropCheckCalculator;
use Facades\App\Flare\RandomNumber\RandomNumberGenerator;
use Facades\App\Game\Core\Handlers\AnnouncementHandler;
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

class GoldMinesRewardHandlerTest extends TestCase
{
    use CreateCharacterAutomation, CreateEvent, CreateItem, CreateItemAffix, CreateLocation, CreateMonster, RefreshDatabase;

    private ?GoldMinesRewardHandler $handler;

    public function setUp(): void
    {
        parent::setUp();

        $this->handler = resolve(GoldMinesRewardHandler::class);
        Cache::forget('monsters');
    }

    public function tearDown(): void
    {
        $this->handler = null;
        Cache::forget('monsters');

        parent::tearDown();
    }

    public function testReturnsCharacterWhenLocationDoesNotExist(): void
    {
        $characterFactory = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $character = $characterFactory->getCharacter();

        $character->map()->update([
            'character_position_x' => 12,
            'character_position_y' => 12,
        ]);

        $character->update([
            'gold' => 10,
            'gold_dust' => 20,
            'shards' => 30,
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
        ]);

        $result = $this->handler->handleFightingAtGoldMines($character->refresh(), $monster)->refresh();

        $this->assertEquals($character->id, $result->id);
        $this->assertEquals(10, $result->gold);
        $this->assertEquals(20, $result->gold_dust);
        $this->assertEquals(30, $result->shards);
    }

    public function testReturnsCharacterWhenLocationTypeIsNull(): void
    {
        $characterFactory = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $character = $characterFactory->getCharacter();

        $character->map()->update([
            'character_position_x' => 12,
            'character_position_y' => 12,
        ]);

        $character->update([
            'gold' => 10,
            'gold_dust' => 20,
            'shards' => 30,
        ]);

        $this->createLocation([
            'game_map_id' => $character->map->game_map_id,
            'x' => 12,
            'y' => 12,
            'type' => LocationType::ALCHEMY_CHURCH,
            'name' => 'invalid_type_location',
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
        ]);

        $result = $this->handler->handleFightingAtGoldMines($character->refresh(), $monster)->refresh();

        $this->assertEquals(10, $result->gold);
        $this->assertEquals(20, $result->gold_dust);
        $this->assertEquals(30, $result->shards);
    }

    public function testCurrencyRewardCapsWithoutEvent(): void
    {
        RandomNumberGenerator::shouldReceive('generateRandomNumber')->times(3)->andReturn(1000);

        $characterFactory = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $character = $characterFactory->getCharacter();

        $character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD - 10,
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST - 10,
            'shards' => MaxCurrenciesValue::MAX_SHARDS - 10,
        ]);

        $result = $this->handler->currencyReward($character->refresh(), null)->refresh();

        $this->assertEquals(MaxCurrenciesValue::MAX_GOLD, $result->gold);
        $this->assertEquals(MaxCurrenciesValue::MAX_GOLD_DUST, $result->gold_dust);
        $this->assertEquals(MaxCurrenciesValue::MAX_SHARDS, $result->shards);
    }

    public function testCurrencyRewardCapsWithEvent(): void
    {
        RandomNumberGenerator::shouldReceive('generateRandomNumber')->times(3)->andReturn(5000);

        $characterFactory = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $character = $characterFactory->getCharacter();

        $character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD,
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
        ]);

        $event = $this->createEvent([
            'type' => EventType::GOLD_MINES,
        ]);

        $result = $this->handler->currencyReward($character->refresh(), $event)->refresh();

        $this->assertEquals(MaxCurrenciesValue::MAX_GOLD, $result->gold);
        $this->assertEquals(MaxCurrenciesValue::MAX_GOLD_DUST, $result->gold_dust);
        $this->assertEquals(MaxCurrenciesValue::MAX_SHARDS, $result->shards);
    }

    public function testHandleRewardsCurrencyButReturnsEarlyWhenAutomationsAreRunning(): void
    {
        RandomNumberGenerator::shouldReceive('generateRandomNumber')->times(3)->andReturn(10);

        $characterFactory = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $character = $characterFactory->getCharacter();

        $character->map()->update([
            'character_position_x' => 12,
            'character_position_y' => 12,
        ]);

        $character->update([
            'gold' => 0,
            'gold_dust' => 0,
            'shards' => 0,
        ]);

        $location = $this->createGoldMinesLocation($character);

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

        $result = $this->handler->handleFightingAtGoldMines($character, $monster)->refresh();

        $afterSlots = $result->inventory->slots()->count();

        $this->assertEquals(10, $result->gold);
        $this->assertEquals(10, $result->gold_dust);
        $this->assertEquals(10, $result->shards);
        $this->assertEquals($beforeSlots, $afterSlots);
        $this->assertFalse(FlareEvent::where('type', EventType::GOLD_MINES)->exists());
    }

    public function testHandleDoesNotRewardItemsWhenMonsterNotInCacheList(): void
    {
        RandomNumberGenerator::shouldReceive('generateRandomNumber')->times(3)->andReturn(10);

        $characterFactory = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $character = $characterFactory->getCharacter();

        $character->map()->update([
            'character_position_x' => 12,
            'character_position_y' => 12,
        ]);

        $character->update([
            'gold' => 0,
            'gold_dust' => 0,
            'shards' => 0,
        ]);

        $location = $this->createGoldMinesLocation($character);

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

        $result = $this->handler->handleFightingAtGoldMines($character->refresh(), $monster)->refresh();

        $afterSlots = $result->inventory->slots()->count();

        $this->assertEquals(10, $result->gold);
        $this->assertEquals(10, $result->gold_dust);
        $this->assertEquals(10, $result->shards);
        $this->assertEquals($beforeSlots, $afterSlots);
        $this->assertFalse(FlareEvent::where('type', EventType::GOLD_MINES)->exists());
    }

    public function testHandleDoesNotRewardItemsWhenMonsterIsBeforeHalfway(): void
    {
        RandomNumberGenerator::shouldReceive('generateRandomNumber')->times(3)->andReturn(10);

        $characterFactory = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $character = $characterFactory->getCharacter();

        $character->map()->update([
            'character_position_x' => 12,
            'character_position_y' => 12,
        ]);

        $character->update([
            'gold' => 0,
            'gold_dust' => 0,
            'shards' => 0,
        ]);

        $location = $this->createGoldMinesLocation($character);

        $monsters = [
            $this->createMonster(['game_map_id' => $character->map->game_map_id]),
            $this->createMonster(['game_map_id' => $character->map->game_map_id]),
            $this->createMonster(['game_map_id' => $character->map->game_map_id]),
            $this->createMonster(['game_map_id' => $character->map->game_map_id]),
        ];

        $targetMonster = $monsters[1];

        Cache::put('monsters', [
            $location->name => [
                ['id' => $monsters[0]->id],
                ['id' => $monsters[1]->id],
                ['id' => $monsters[2]->id],
                ['id' => $monsters[3]->id],
            ],
        ]);

        $beforeSlots = $character->refresh()->inventory->slots()->count();

        $result = $this->handler->handleFightingAtGoldMines($character->refresh(), $targetMonster)->refresh();

        $afterSlots = $result->inventory->slots()->count();

        $this->assertEquals(10, $result->gold);
        $this->assertEquals(10, $result->gold_dust);
        $this->assertEquals(10, $result->shards);
        $this->assertEquals($beforeSlots, $afterSlots);
        $this->assertFalse(FlareEvent::where('type', EventType::GOLD_MINES)->exists());
    }

    public function testHandleClampsLootingChanceWhenOverCap(): void
    {
        RandomNumberGenerator::shouldReceive('generateRandomNumber')->times(3)->andReturn(1);
        RandomNumberGenerator::shouldReceive('generateTrueRandomNumber')->once()->andReturn(0);

        DropCheckCalculator::shouldReceive('fetchDifficultItemChance')
            ->once()
            ->andReturnFalse();

        $characterFactory = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $character = $characterFactory->getCharacter();

        $character->map()->update([
            'character_position_x' => 12,
            'character_position_y' => 12,
        ]);

        $lootingSkill = $character->skills()
            ->whereHas('baseSkill', function ($query) {
                $query->where('name', 'Looting');
            })->first();

        if (!is_null($lootingSkill)) {
            $lootingSkill->update([
                'skill_bonus' => 1,
            ]);
        }

        $character = $character->refresh();

        $location = $this->createGoldMinesLocation($character);

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

        $beforeSlots = $character->inventory->slots()->count();

        $result = $this->handler->handleFightingAtGoldMines($character, $targetMonster)->refresh();

        $afterSlots = $result->inventory->slots()->count();

        $this->assertEquals($beforeSlots, $afterSlots);
        $this->assertFalse(FlareEvent::where('type', EventType::GOLD_MINES)->exists());
    }

    public function testHandleUsesEventOverridesLootingChanceAndHalvesMaxRoll(): void
    {
        $this->createEvent([
            'type' => EventType::GOLD_MINES,
        ]);

        DropCheckCalculator::shouldReceive('fetchDifficultItemChance')
            ->once()
            ->withArgs(function ($chance, $maxRoll) {
                return abs($chance - 0.30) < 0.00001 && $maxRoll == 500;
            })
            ->andReturnFalse();

        RandomNumberGenerator::shouldReceive('generateRandomNumber')->times(3)->andReturn(1);
        RandomNumberGenerator::shouldReceive('generateTrueRandomNumber')->never();

        $characterFactory = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $character = $characterFactory->getCharacter();

        $character->map()->update([
            'character_position_x' => 12,
            'character_position_y' => 12,
        ]);

        $location = $this->createGoldMinesLocation($character->refresh());

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

        $result = $this->handler->handleFightingAtGoldMines($character->refresh(), $targetMonster)->refresh();

        $afterSlots = $result->inventory->slots()->count();

        $this->assertEquals($beforeSlots, $afterSlots);
    }

    public function testHandleDoesNotRewardItemWhenDropCheckFailsButCanCreateEvent(): void
    {
        RandomNumberGenerator::shouldReceive('generateRandomNumber')->times(3)->andReturn(1);
        RandomNumberGenerator::shouldReceive('generateTrueRandomNumber')->once()->andReturn(0);

        DropCheckCalculator::shouldReceive('fetchDifficultItemChance')->once()->andReturnFalse();

        $characterFactory = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $character = $characterFactory->getCharacter();

        $character->map()->update([
            'character_position_x' => 12,
            'character_position_y' => 12,
        ]);

        $location = $this->createGoldMinesLocation($character->refresh());

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

        $result = $this->handler->handleFightingAtGoldMines($character->refresh(), $targetMonster)->refresh();

        $afterSlots = $result->inventory->slots()->count();

        $this->assertEquals($beforeSlots, $afterSlots);
        $this->assertFalse(FlareEvent::where('type', EventType::GOLD_MINES)->exists());
    }

    public function testHandleDoesNotRewardItemWhenInventoryIsFull(): void
    {
        RandomNumberGenerator::shouldReceive('generateRandomNumber')->times(3)->andReturn(1);
        RandomNumberGenerator::shouldReceive('generateTrueRandomNumber')->once()->andReturn(0);

        DropCheckCalculator::shouldReceive('fetchDifficultItemChance')->once()->andReturnTrue();

        $characterFactory = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $character = $characterFactory->getCharacter();

        $character->map()->update([
            'character_position_x' => 12,
            'character_position_y' => 12,
        ]);

        $character = $this->fillInventoryUntilFull($character);

        $location = $this->createGoldMinesLocation($character->refresh());

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

        $result = $this->handler->handleFightingAtGoldMines($character->refresh(), $targetMonster)->refresh();

        $afterSlots = $result->inventory->slots()->count();

        $this->assertEquals($beforeSlots, $afterSlots);
    }

    public function testHandleDoesNotAddSlotWhenRewardItemQueryReturnsNull(): void
    {
        RandomNumberGenerator::shouldReceive('generateRandomNumber')->times(3)->andReturn(1);
        RandomNumberGenerator::shouldReceive('generateTrueRandomNumber')->once()->andReturn(0);

        DropCheckCalculator::shouldReceive('fetchDifficultItemChance')->once()->andReturnTrue();

        $this->createItem([
            'type' => 'alchemy',
            'specialty_type' => null,
            'item_prefix_id' => null,
            'item_suffix_id' => null,
        ]);

        $characterFactory = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $character = $characterFactory->getCharacter();

        $character->map()->update([
            'character_position_x' => 12,
            'character_position_y' => 12,
        ]);

        $location = $this->createGoldMinesLocation($character->refresh());

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

        $result = $this->handler->handleFightingAtGoldMines($character->refresh(), $targetMonster)->refresh();

        $afterSlots = $result->inventory->slots()->count();

        $this->assertEquals($beforeSlots, $afterSlots);
        $this->assertFalse(FlareEvent::where('type', EventType::GOLD_MINES)->exists());
    }

    public function testHandleRewardsItemWhenDropCheckPassesAndInventoryNotFull(): void
    {
        RandomNumberGenerator::shouldReceive('generateRandomNumber')->times(3)->andReturn(1);
        RandomNumberGenerator::shouldReceive('generateTrueRandomNumber')->once()->andReturn(0);

        DropCheckCalculator::shouldReceive('fetchDifficultItemChance')->once()->andReturnTrue();

        $this->createItemAffix([
            'type' => 'prefix',
        ]);

        $this->createItemAffix([
            'type' => 'suffix',
        ]);

        $this->createItem([
            'specialty_type' => null,
            'item_prefix_id' => null,
            'item_suffix_id' => null,
            'type' => 'weapon',
        ]);

        $characterFactory = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $character = $characterFactory->getCharacter();

        $character->map()->update([
            'character_position_x' => 12,
            'character_position_y' => 12,
        ]);

        $location = $this->createGoldMinesLocation($character->refresh());

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

        $result = $this->handler->handleFightingAtGoldMines($character->refresh(), $targetMonster)->refresh();

        $afterSlots = $result->inventory->slots()->count();

        $this->assertEquals($beforeSlots + 1, $afterSlots);

        $newItem = $result->inventory->slots()->latest('id')->first()->item;

        $this->assertNotNull($newItem->item_prefix_id);
        $this->assertNotNull($newItem->item_suffix_id);
        $this->assertFalse(FlareEvent::where('type', EventType::GOLD_MINES)->exists());
    }

    public function testHandleCreatesEventWhenRandomAtThreshold(): void
    {
        RandomNumberGenerator::shouldReceive('generateRandomNumber')->times(3)->andReturn(1);
        RandomNumberGenerator::shouldReceive('generateTrueRandomNumber')->once()->andReturn(999);

        DropCheckCalculator::shouldReceive('fetchDifficultItemChance')->once()->andReturnFalse();

        AnnouncementHandler::shouldReceive('createAnnouncement')->once()->with('gold_mines');

        $characterFactory = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $character = $characterFactory->getCharacter();

        $character->map()->update([
            'character_position_x' => 12,
            'character_position_y' => 12,
        ]);

        $location = $this->createGoldMinesLocation($character->refresh());

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

        $this->handler->handleFightingAtGoldMines($character->refresh(), $targetMonster)->refresh();

        $this->assertTrue(FlareEvent::where('type', EventType::GOLD_MINES)->exists());
    }

    public function testHandleDoesNotAttemptToCreateEventWhenEventAlreadyExists(): void
    {
        $this->createEvent([
            'type' => EventType::GOLD_MINES,
        ]);

        RandomNumberGenerator::shouldReceive('generateRandomNumber')->times(3)->andReturn(1);
        RandomNumberGenerator::shouldReceive('generateTrueRandomNumber')->never();

        DropCheckCalculator::shouldReceive('fetchDifficultItemChance')->once()->andReturnFalse();

        $characterFactory = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $character = $characterFactory->getCharacter();

        $character->map()->update([
            'character_position_x' => 12,
            'character_position_y' => 12,
        ]);

        $location = $this->createGoldMinesLocation($character->refresh());

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

        $this->handler->handleFightingAtGoldMines($character->refresh(), $targetMonster)->refresh();

        $this->assertEquals(1, FlareEvent::where('type', EventType::GOLD_MINES)->count());
    }

    public function testReturnsCharacterWhenLocationIsNotGoldMines(): void
    {
        $characterFactory = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $character = $characterFactory->getCharacter();

        $character->map()->update([
            'character_position_x' => 12,
            'character_position_y' => 12,
        ]);

        $character->update([
            'gold' => 10,
            'gold_dust' => 20,
            'shards' => 30,
        ]);

        $this->createLocation([
            'game_map_id' => $character->map->game_map_id,
            'x' => 12,
            'y' => 12,
            'type' => LocationType::ALCHEMY_CHURCH,
            'name' => 'not_gold_mines',
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
        ]);

        $result = $this->handler->handleFightingAtGoldMines($character->refresh(), $monster)->refresh();

        $this->assertEquals(10, $result->gold);
        $this->assertEquals(20, $result->gold_dust);
        $this->assertEquals(30, $result->shards);
    }

    public function testReturnsCharacterWhenLocationIsNotGoldMinesWhenAlchemyChurch(): void
    {
        $characterFactory = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $character = $characterFactory->getCharacter();

        $character->map()->update([
            'character_position_x' => 12,
            'character_position_y' => 12,
        ]);

        $character->update([
            'gold' => 10,
            'gold_dust' => 20,
            'shards' => 30,
        ]);

        $this->createLocation([
            'game_map_id' => $character->map->game_map_id,
            'x' => 12,
            'y' => 12,
            'type' => LocationType::ALCHEMY_CHURCH,
            'name' => 'not_gold_mines',
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
        ]);

        $result = $this->handler->handleFightingAtGoldMines($character->refresh(), $monster)->refresh();

        $this->assertEquals(10, $result->gold);
        $this->assertEquals(20, $result->gold_dust);
        $this->assertEquals(30, $result->shards);
    }

    public function testHandleClampsLootingChanceWhenOverCapByLevel(): void
    {
        RandomNumberGenerator::shouldReceive('generateRandomNumber')->times(3)->andReturn(1);
        RandomNumberGenerator::shouldReceive('generateTrueRandomNumber')->once()->andReturn(0);

        DropCheckCalculator::shouldReceive('fetchDifficultItemChance')
            ->once()
            ->withArgs(function ($chance, $maxRoll) {
                return abs($chance - 0.15) < 0.00001 && $maxRoll === 1000;
            })
            ->andReturnFalse();

        $characterFactory = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $character = $characterFactory->getCharacter();

        $character->map()->update([
            'character_position_x' => 12,
            'character_position_y' => 12,
        ]);

        $lootingSkill = $character->skills()
            ->whereHas('baseSkill', function ($query) {
                $query->where('name', 'Looting');
            })->first();

        if (!is_null($lootingSkill)) {
            $lootingSkill->level = 999;
            $lootingSkill->save();
        }

        $character = $character->refresh();

        $location = $this->createGoldMinesLocation($character);

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

        $beforeSlots = $character->inventory->slots()->count();

        $result = $this->handler->handleFightingAtGoldMines($character->refresh(), $targetMonster)->refresh();

        $afterSlots = $result->inventory->slots()->count();

        $this->assertEquals($beforeSlots, $afterSlots);
        $this->assertFalse(FlareEvent::where('type', EventType::GOLD_MINES)->exists());
    }

    private function createGoldMinesLocation($character): Location
    {
        $map = $character->map->refresh();

        return $this->createLocation([
            'game_map_id' => $character->map->game_map_id,
            'x' => $map->character_position_x,
            'y' => $map->character_position_y,
            'type' => LocationType::GOLD_MINES,
            'name' => 'gold_mines_' . uniqid('', true),
        ]);
    }

    private function fillInventoryUntilFull($character)
    {
        $iterations = 0;

        while (!$character->isInventoryFull() && $iterations < 250) {
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
}
