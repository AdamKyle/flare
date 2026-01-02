<?php

namespace Tests\Unit\Game\BattleRewardProcessing\Handlers;

use App\Flare\Models\Character;
use App\Flare\Models\Event as FlareEvent;
use App\Flare\Models\Location;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Values\LocationType;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\BattleRewardProcessing\Handlers\TheOldChurchRewardHandler;
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

class TheOldChurchRewardHandlerTest extends TestCase
{
    use CreateCharacterAutomation, CreateEvent, CreateItem, CreateItemAffix, CreateLocation, CreateMonster, RefreshDatabase;

    private ?TheOldChurchRewardHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = resolve(TheOldChurchRewardHandler::class);
        Cache::forget('monsters');
    }

    protected function tearDown(): void
    {
        $this->handler = null;
        Cache::forget('monsters');

        parent::tearDown();
    }

    public function test_returns_character_when_location_does_not_exist(): void
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

        $result = $this->handler->handleFightingAtTheOldChurch($character->refresh(), $monster)->refresh();

        $this->assertEquals($character->id, $result->id);
        $this->assertEquals(10, $result->gold);
        $this->assertEquals(20, $result->gold_dust);
        $this->assertEquals(30, $result->shards);
    }

    public function test_returns_character_when_location_type_is_null(): void
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
            'type' => null,
            'name' => 'null_type_location',
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
        ]);

        $result = $this->handler->handleFightingAtTheOldChurch($character->refresh(), $monster)->refresh();

        $this->assertEquals(10, $result->gold);
        $this->assertEquals(20, $result->gold_dust);
        $this->assertEquals(30, $result->shards);
    }

    public function test_returns_character_when_location_is_not_the_old_church(): void
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
            'name' => 'not_the_old_church',
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
        ]);

        $result = $this->handler->handleFightingAtTheOldChurch($character->refresh(), $monster)->refresh();

        $this->assertEquals(10, $result->gold);
        $this->assertEquals(20, $result->gold_dust);
        $this->assertEquals(30, $result->shards);
    }

    public function test_returns_character_when_character_does_not_have_quest_item(): void
    {
        RandomNumberGenerator::shouldReceive('generateRandomNumber')->never();
        RandomNumberGenerator::shouldReceive('generateTrueRandomNumber')->never();

        DropCheckCalculator::shouldReceive('fetchDifficultItemChance')->never();

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

        $this->createTheOldChurchLocation($character);

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
        ]);

        $result = $this->handler->handleFightingAtTheOldChurch($character->refresh(), $monster)->refresh();

        $this->assertEquals(10, $result->gold);
        $this->assertEquals(20, $result->gold_dust);
        $this->assertEquals(30, $result->shards);
        $this->assertFalse(FlareEvent::where('type', EventType::THE_OLD_CHURCH)->exists());
    }

    public function test_currency_reward_caps_without_event(): void
    {
        RandomNumberGenerator::shouldReceive('generateRandomNumber')->times(3)->andReturn(100000);

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

    public function test_currency_reward_caps_with_event(): void
    {
        RandomNumberGenerator::shouldReceive('generateRandomNumber')->times(3)->andReturn(100000);

        $characterFactory = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $character = $characterFactory->getCharacter();

        $character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD,
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
        ]);

        $event = $this->createEvent([
            'type' => EventType::THE_OLD_CHURCH,
        ]);

        $result = $this->handler->currencyReward($character->refresh(), $event)->refresh();

        $this->assertEquals(MaxCurrenciesValue::MAX_GOLD, $result->gold);
        $this->assertEquals(MaxCurrenciesValue::MAX_GOLD_DUST, $result->gold_dust);
        $this->assertEquals(MaxCurrenciesValue::MAX_SHARDS, $result->shards);
    }

    public function test_handle_rewards_currency_but_returns_early_when_automations_are_running(): void
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

        $character = $this->giveQuestItemToCharacter($characterFactory);

        $location = $this->createTheOldChurchLocation($character);

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

        $result = $this->handler->handleFightingAtTheOldChurch($character, $monster)->refresh();

        $afterSlots = $result->inventory->slots()->count();

        $this->assertEquals(10, $result->gold);
        $this->assertEquals(10, $result->gold_dust);
        $this->assertEquals(10, $result->shards);
        $this->assertEquals($beforeSlots, $afterSlots);
        $this->assertFalse(FlareEvent::where('type', EventType::THE_OLD_CHURCH)->exists());
    }

    public function test_handle_does_not_reward_items_when_monster_not_in_cache_list(): void
    {
        RandomNumberGenerator::shouldReceive('generateRandomNumber')->times(3)->andReturn(10);
        RandomNumberGenerator::shouldReceive('generateTrueRandomNumber')->never();

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

        $character = $this->giveQuestItemToCharacter($characterFactory);

        $location = $this->createTheOldChurchLocation($character);

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

        $result = $this->handler->handleFightingAtTheOldChurch($character->refresh(), $monster)->refresh();

        $afterSlots = $result->inventory->slots()->count();

        $this->assertEquals(10, $result->gold);
        $this->assertEquals(10, $result->gold_dust);
        $this->assertEquals(10, $result->shards);
        $this->assertEquals($beforeSlots, $afterSlots);
        $this->assertFalse(FlareEvent::where('type', EventType::THE_OLD_CHURCH)->exists());
    }

    public function test_handle_does_not_reward_items_when_monster_is_before_halfway(): void
    {
        RandomNumberGenerator::shouldReceive('generateRandomNumber')->times(3)->andReturn(10);
        RandomNumberGenerator::shouldReceive('generateTrueRandomNumber')->never();

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

        $character = $this->giveQuestItemToCharacter($characterFactory);

        $location = $this->createTheOldChurchLocation($character);

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

        $result = $this->handler->handleFightingAtTheOldChurch($character->refresh(), $targetMonster)->refresh();

        $afterSlots = $result->inventory->slots()->count();

        $this->assertEquals(10, $result->gold);
        $this->assertEquals(10, $result->gold_dust);
        $this->assertEquals(10, $result->shards);
        $this->assertEquals($beforeSlots, $afterSlots);
        $this->assertFalse(FlareEvent::where('type', EventType::THE_OLD_CHURCH)->exists());
    }

    public function test_handle_clamps_looting_chance_when_over_cap(): void
    {
        RandomNumberGenerator::shouldReceive('generateRandomNumber')->times(3)->andReturn(1);
        RandomNumberGenerator::shouldReceive('generateTrueRandomNumber')->once()->andReturn(0);

        DropCheckCalculator::shouldReceive('fetchDifficultItemChance')
            ->once()
            ->withArgs(function ($chance, $maxRoll) {
                return abs($chance - 0.15) < 0.00001 && (int) $maxRoll === 1000;
            })
            ->andReturnFalse();

        $characterFactory = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $character = $characterFactory->getCharacter();

        $character->map()->update([
            'character_position_x' => 12,
            'character_position_y' => 12,
        ]);

        $character = $this->giveQuestItemToCharacter($characterFactory);

        $character->skills()
            ->whereHas('baseSkill', function ($query) {
                $query->where('name', 'Looting');
            })
            ->update([
                'level' => 999,
            ]);

        $character = $character->refresh();

        $location = $this->createTheOldChurchLocation($character);

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

        $result = $this->handler->handleFightingAtTheOldChurch($character->refresh(), $targetMonster)->refresh();

        $afterSlots = $result->inventory->slots()->count();

        $this->assertEquals($beforeSlots, $afterSlots);
        $this->assertFalse(FlareEvent::where('type', EventType::THE_OLD_CHURCH)->exists());
    }

    public function test_handle_uses_event_overrides_looting_chance_and_halves_max_roll(): void
    {
        $this->createEvent([
            'type' => EventType::THE_OLD_CHURCH,
        ]);

        DropCheckCalculator::shouldReceive('fetchDifficultItemChance')
            ->once()
            ->withArgs(function ($chance, $maxRoll) {
                return abs($chance - 0.30) < 0.00001 && (int) $maxRoll === 500;
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

        $character = $this->giveQuestItemToCharacter($characterFactory);

        $location = $this->createTheOldChurchLocation($character->refresh());

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

        $result = $this->handler->handleFightingAtTheOldChurch($character->refresh(), $targetMonster)->refresh();

        $afterSlots = $result->inventory->slots()->count();

        $this->assertEquals($beforeSlots, $afterSlots);
    }

    public function test_handle_does_not_reward_item_when_drop_check_fails_but_event_roll_is_not_high_enough(): void
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

        $character = $this->giveQuestItemToCharacter($characterFactory);

        $location = $this->createTheOldChurchLocation($character->refresh());

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

        $result = $this->handler->handleFightingAtTheOldChurch($character->refresh(), $targetMonster)->refresh();

        $afterSlots = $result->inventory->slots()->count();

        $this->assertEquals($beforeSlots, $afterSlots);
        $this->assertFalse(FlareEvent::where('type', EventType::THE_OLD_CHURCH)->exists());
    }

    public function test_handle_does_not_reward_item_when_inventory_is_full(): void
    {
        RandomNumberGenerator::shouldReceive('generateRandomNumber')->times(3)->andReturn(1);
        RandomNumberGenerator::shouldReceive('generateTrueRandomNumber')->once()->andReturn(0);

        $characterFactory = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $character = $characterFactory->getCharacter();

        $character->map()->update([
            'character_position_x' => 12,
            'character_position_y' => 12,
        ]);

        $character = $this->giveQuestItemToCharacter($characterFactory);
        $character = $this->fillInventoryUntilFull($character->refresh());

        $location = $this->createTheOldChurchLocation($character->refresh());

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

        $result = $this->handler->handleFightingAtTheOldChurch($character->refresh(), $targetMonster)->refresh();

        $afterSlots = $result->inventory->slots()->count();

        $this->assertEquals($beforeSlots, $afterSlots);
        $this->assertFalse(FlareEvent::where('type', EventType::THE_OLD_CHURCH)->exists());
    }

    public function test_handle_does_not_add_slot_when_reward_item_query_returns_null(): void
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

        $character = $this->giveQuestItemToCharacter($characterFactory);

        $location = $this->createTheOldChurchLocation($character->refresh());

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

        $result = $this->handler->handleFightingAtTheOldChurch($character->refresh(), $targetMonster)->refresh();

        $afterSlots = $result->inventory->slots()->count();

        $this->assertEquals($beforeSlots, $afterSlots);
        $this->assertFalse(FlareEvent::where('type', EventType::THE_OLD_CHURCH)->exists());
    }

    public function test_handle_rewards_item_when_drop_check_passes_and_inventory_not_full(): void
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
            'specialty_type' => ItemSpecialtyType::CORRUPTED_ICE,
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

        $character = $this->giveQuestItemToCharacter($characterFactory);

        $location = $this->createTheOldChurchLocation($character->refresh());

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

        $result = $this->handler->handleFightingAtTheOldChurch($character->refresh(), $targetMonster)->refresh();

        $afterSlots = $result->inventory->slots()->count();

        $this->assertEquals($beforeSlots + 1, $afterSlots);

        $newItem = $result->inventory->slots()->latest('id')->first()->item;

        $this->assertNotNull($newItem->item_prefix_id);
        $this->assertNotNull($newItem->item_suffix_id);
        $this->assertFalse(FlareEvent::where('type', EventType::THE_OLD_CHURCH)->exists());
    }

    public function test_handle_creates_event_when_random_at_threshold(): void
    {
        RandomNumberGenerator::shouldReceive('generateRandomNumber')->times(3)->andReturn(1);
        RandomNumberGenerator::shouldReceive('generateTrueRandomNumber')->once()->andReturn(999);

        DropCheckCalculator::shouldReceive('fetchDifficultItemChance')->once()->andReturnFalse();

        AnnouncementHandler::shouldReceive('createAnnouncement')->once()->with('the_old_house');

        $characterFactory = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
        $character = $characterFactory->getCharacter();

        $character->map()->update([
            'character_position_x' => 12,
            'character_position_y' => 12,
        ]);

        $character = $this->giveQuestItemToCharacter($characterFactory);

        $location = $this->createTheOldChurchLocation($character->refresh());

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

        $this->handler->handleFightingAtTheOldChurch($character->refresh(), $targetMonster)->refresh();

        $this->assertTrue(FlareEvent::where('type', EventType::THE_OLD_CHURCH)->exists());
    }

    public function test_handle_does_not_attempt_to_create_event_when_event_already_exists(): void
    {
        $this->createEvent([
            'type' => EventType::THE_OLD_CHURCH,
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

        $character = $this->giveQuestItemToCharacter($characterFactory);

        $location = $this->createTheOldChurchLocation($character->refresh());

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

        $this->handler->handleFightingAtTheOldChurch($character->refresh(), $targetMonster)->refresh();

        $this->assertEquals(1, FlareEvent::where('type', EventType::THE_OLD_CHURCH)->count());
    }

    private function createTheOldChurchLocation(Character $character): Location
    {
        $map = $character->map->refresh();

        return $this->createLocation([
            'game_map_id' => $character->map->game_map_id,
            'x' => $map->character_position_x,
            'y' => $map->character_position_y,
            'type' => LocationType::THE_OLD_CHURCH,
            'name' => 'the_old_church_'.uniqid('', true),
        ]);
    }

    private function giveQuestItemToCharacter(CharacterFactory $characterFactory): Character
    {
        $questItem = $this->createItem([
            'effect' => ItemEffectsValue::THE_OLD_CHURCH,
            'type' => 'quest',
        ]);

        return $characterFactory->inventoryManagement()->giveItem($questItem)->getCharacter();
    }

    private function fillInventoryUntilFull(Character $character): Character
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
}
