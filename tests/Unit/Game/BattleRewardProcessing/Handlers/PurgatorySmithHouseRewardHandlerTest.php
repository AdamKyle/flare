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

    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = resolve(PurgatorySmithHouseRewardHandler::class);
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
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $character = $characterFactory->getCharacter();

        $character->map()->update([
            'character_position_x' => 12,
            'character_position_y' => 12,
        ]);

        $character->update([
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

    public function test_returns_character_when_location_type_is_invalid(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $character = $characterFactory->getCharacter();

        $character->map()->update([
            'character_position_x' => 12,
            'character_position_y' => 12,
        ]);

        $character->update([
            'gold_dust' => 10,
            'shards' => 20,
            'copper_coins' => 30,
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

        $result = $this->handler->handleFightingAtPurgatorySmithHouse($character->refresh(), $monster);

        $result = $result->refresh();

        $this->assertEquals(10, $result->gold_dust);
        $this->assertEquals(20, $result->shards);
        $this->assertEquals(30, $result->copper_coins);
    }

    public function test_returns_character_when_location_is_not_smith_house(): void
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

    public function test_currency_reward_caps_without_event_and_without_copper_coins_item(): void
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

    public function test_currency_reward_caps_with_event_and_with_copper_coins_item(): void
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

    public function test_handle_rewards_currency_but_returns_early_when_automations_are_running(): void
    {
        RandomNumberGenerator::shouldReceive('generateRandomNumber')->andReturn(10);

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $character = $characterFactory->getCharacter();

        $character->map()->update([
            'character_position_x' => 12,
            'character_position_y' => 12,
        ]);

        $character->update([
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

    public function test_handle_does_not_reward_items_when_monster_not_in_cache_list(): void
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

        $character->map()->update([
            'character_position_x' => 12,
            'character_position_y' => 12,
        ]);

        $character->update([
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

    public function test_handle_rewards_legendary_at_halfway_or_more_and_does_not_create_event_when_random_below_threshold(): void
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

        $character->map()->update([
            'character_position_x' => 12,
            'character_position_y' => 12,
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

    public function test_handle_does_not_reward_item_when_drop_check_fails(): void
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

        $character->map()->update([
            'character_position_x' => 12,
            'character_position_y' => 12,
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

    public function test_handle_does_not_reward_item_when_inventory_is_full(): void
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

        $character->map()->update([
            'character_position_x' => 12,
            'character_position_y' => 12,
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

    public function test_handle_does_not_add_slot_when_no_eligible_purgatory_chains_item_exists(): void
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

        $character->map()->update([
            'character_position_x' => 12,
            'character_position_y' => 12,
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

    public function test_handle_final_monster_rewards_legendary_and_mythic_and_creates_event(): void
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

        $character->map()->update([
            'character_position_x' => 12,
            'character_position_y' => 12,
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

    public function test_handle_clamps_looting_chance_when_over_cap(): void
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

    public function test_handle_uses_event_overrides_looting_chance_and_halves_max_roll(): void
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

        $character->map()->update([
            'character_position_x' => 12,
            'character_position_y' => 12,
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

    public function test_handle_does_not_add_slot_when_reward_item_query_returns_null(): void
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

        $character->map()->update([
            'character_position_x' => 12,
            'character_position_y' => 12,
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

    public function test_handle_aggregates_currencies_for_kill_count(): void
    {
        RandomNumberGenerator::shouldReceive('generateRandomNumber')->twice()->andReturn(10);

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $character = $characterFactory->getCharacter();

        $character->update([
            'gold_dust' => 0,
            'shards' => 0,
            'copper_coins' => 0,
        ]);

        $character->map()->update([
            'character_position_x' => 12,
            'character_position_y' => 12,
        ]);

        $this->createSmithHouseLocation($character->refresh());

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
        ]);

        $this->createExploringAutomation([
            'character_id' => $character->id,
        ]);

        $result = $this->handler->handleFightingAtPurgatorySmithHouse($character->refresh(), $monster, 10)->refresh();

        $this->assertEquals(100, $result->gold_dust);
        $this->assertEquals(100, $result->shards);
        $this->assertEquals(0, $result->copper_coins);
    }

    public function test_handle_rewards_legendary_per_kill_count_when_halfway_or_more(): void
    {
        DropCheckCalculator::shouldReceive('fetchDifficultItemChance')->andReturnTrue();

        RandomNumberGenerator::shouldReceive('generateRandomNumber')->andReturn(1);
        RandomNumberGenerator::shouldReceive('generateTrueRandomNumber')->once()->andReturn(0);

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

        $character->map()->update([
            'character_position_x' => 12,
            'character_position_y' => 12,
        ]);

        $character = $character->refresh();
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

        $beforeSlots = $character->inventory->slots()->count();

        $result = $this->handler->handleFightingAtPurgatorySmithHouse($character->refresh(), $targetMonster, 10)->refresh();

        $afterSlots = $result->inventory->slots()->count();

        $this->assertEquals($beforeSlots + 10, $afterSlots);

        $newItems = $result->inventory->slots()->latest('id')->take(10)->get()->pluck('item');

        $this->assertFalse($newItems->contains(function ($item) {
            return (bool) $item->is_mythic;
        }));

        $this->assertFalse(FlareEvent::where('type', EventType::PURGATORY_SMITH_HOUSE)->exists());
    }

    public function test_handle_final_monster_caps_mythic_to_one_per_batch(): void
    {
        DropCheckCalculator::shouldReceive('fetchDifficultItemChance')->andReturnTrue();

        RandomNumberGenerator::shouldReceive('generateRandomNumber')->andReturn(1);
        RandomNumberGenerator::shouldReceive('generateTrueRandomNumber')->once()->andReturn(0);

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

        $character->map()->update([
            'character_position_x' => 12,
            'character_position_y' => 12,
        ]);

        $character = $character->refresh();
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

        $beforeSlots = $character->inventory->slots()->count();

        $result = $this->handler->handleFightingAtPurgatorySmithHouse($character->refresh(), $targetMonster, 15)->refresh();

        $afterSlots = $result->inventory->slots()->count();

        $this->assertEquals($beforeSlots + 16, $afterSlots);

        $newItems = $result->inventory->slots()->latest('id')->take(16)->get()->pluck('item');

        $this->assertEquals(1, $newItems->filter(function ($item) {
            return (bool) $item->is_mythic;
        })->count());

        $this->assertFalse(FlareEvent::where('type', EventType::PURGATORY_SMITH_HOUSE)->exists());
    }

    public function test_handle_does_not_reward_item_when_inventory_is_full_after_drop_check_passes(): void
    {
        RandomNumberGenerator::shouldReceive('generateRandomNumber')->andReturn(1);
        RandomNumberGenerator::shouldReceive('generateTrueRandomNumber')->once()->andReturn(0);

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $character = $characterFactory->getCharacter();

        $character->map()->update([
            'character_position_x' => 12,
            'character_position_y' => 12,
        ]);

        $character = $character->refresh();

        $character->inventory_max = $character->totalInventoryCount() + 1;
        $character->save();

        $character = $character->refresh();

        DropCheckCalculator::shouldReceive('fetchDifficultItemChance')
            ->once()
            ->andReturnUsing(function () use ($character) {
                $fillerItem = $this->createItem([
                    'type' => 'weapon',
                ]);

                $character->inventory->slots()->create([
                    'inventory_id' => $character->inventory->id,
                    'item_id' => $fillerItem->id,
                ]);

                return true;
            });

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

        $beforeSlots = $character->inventory->slots()->count();

        $result = $this->handler->handleFightingAtPurgatorySmithHouse($character->refresh(), $targetMonster)->refresh();

        $afterSlots = $result->inventory->slots()->count();

        $this->assertEquals($beforeSlots + 1, $afterSlots);
        $this->assertFalse(FlareEvent::where('type', EventType::PURGATORY_SMITH_HOUSE)->exists());
    }

    private function createSmithHouseLocation($character): Location
    {
        $map = $character->map->refresh();

        return $this->createLocation([
            'game_map_id' => $character->map->game_map_id,
            'x' => $map->character_position_x,
            'y' => $map->character_position_y,
            'type' => LocationType::PURGATORY_SMITH_HOUSE,
            'name' => 'smith_house_'.uniqid('', true),
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
}
