<?php

namespace Tests\Unit\Flare\Services;

use App\Flare\Services\CharacterCurrencyRewardService;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\LocationType;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Events\Values\EventType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterAutomation;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateScheduledEvent;

class CharacterCurrencyRewardServiceTest extends TestCase
{
    use CreateCharacterAutomation, CreateGameMap, CreateItem, CreateLocation, CreateMonster, CreateScheduledEvent, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?CharacterCurrencyRewardService $characterCurrencyRewardService;

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $this->characterCurrencyRewardService = resolve(CharacterCurrencyRewardService::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->characterCurrencyRewardService = null;
    }

    public function test_set_character_and_get_character()
    {
        $character = $this->character->getCharacter();

        $serviceCharacter = $this->characterCurrencyRewardService->setCharacter($character)->getCharacter();

        $this->assertEquals($character->id, $serviceCharacter->id);
    }

    public function test_give_currencies_awards_gold_when_not_logged_in()
    {
        $character = $this->character->getCharacter();
        $character->update([
            'gold' => 0,
        ]);

        $monster = $this->createMonster([
            'gold' => 50,
            'game_map_id' => $character->map->game_map_id,
        ]);

        $this->characterCurrencyRewardService
            ->setCharacter($character->refresh())
            ->giveCurrencies($monster);

        $character = $this->characterCurrencyRewardService->getCharacter();

        $this->assertEquals(50, $character->gold);
    }

    public function test_give_currencies_awards_gold_when_logged_in()
    {
        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->createSessionForCharacter();

        $character = $characterFactory->getCharacter();
        $character->update([
            'gold' => 0,
        ]);

        $monster = $this->createMonster([
            'gold' => 10,
            'game_map_id' => $character->map->game_map_id,
        ]);

        $this->characterCurrencyRewardService
            ->setCharacter($character->refresh())
            ->giveCurrencies($monster);

        $character = $this->characterCurrencyRewardService->getCharacter();

        $this->assertEquals(10, $character->gold);
    }

    public function test_give_currencies_awards_gold_when_auto_battling()
    {
        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->createSessionForCharacter();

        $character = $characterFactory->getCharacter();

        $this->createExploringAutomation([
            'character_id' => $character->id,
        ]);

        $character->update([
            'gold' => 0,
        ]);

        $monster = $this->createMonster([
            'gold' => 10,
            'game_map_id' => $character->map->game_map_id,
        ]);

        $this->characterCurrencyRewardService
            ->setCharacter($character->refresh())
            ->giveCurrencies($monster);

        $character = $this->characterCurrencyRewardService->getCharacter();

        $this->assertEquals(10, $character->gold);
    }

    public function test_distribute_gold_caps_at_max()
    {
        $character = $this->character->getCharacter();
        $character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD - 1,
        ]);

        $monster = $this->createMonster([
            'gold' => 10,
            'game_map_id' => $character->map->game_map_id,
        ]);

        $this->characterCurrencyRewardService
            ->setCharacter($character->refresh())
            ->giveCurrencies($monster);

        $character = $this->characterCurrencyRewardService->getCharacter();

        $this->assertEquals(MaxCurrenciesValue::MAX_GOLD, $character->gold);
    }

    public function test_currency_event_reward_does_nothing_when_event_not_running()
    {
        $character = $this->character->getCharacter();
        $character->update([
            'shards' => 0,
            'gold_dust' => 0,
            'copper_coins' => 0,
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'is_celestial_entity' => false,
        ]);

        $this->characterCurrencyRewardService
            ->setCharacter($character->refresh())
            ->currencyEventReward($monster);

        $character = $this->characterCurrencyRewardService->getCharacter();

        $this->assertEquals(0, $character->shards);
        $this->assertEquals(0, $character->gold_dust);
        $this->assertEquals(0, $character->copper_coins);
    }

    public function test_currency_event_reward_does_nothing_when_monster_is_celestial()
    {
        $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CURRENCY_DROPS,
            'currently_running' => true,
        ]);

        $character = $this->character->getCharacter();
        $character->update([
            'shards' => 0,
            'gold_dust' => 0,
            'copper_coins' => 0,
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'is_celestial_entity' => true,
        ]);

        $this->characterCurrencyRewardService
            ->setCharacter($character->refresh())
            ->currencyEventReward($monster);

        $character = $this->characterCurrencyRewardService->getCharacter();

        $this->assertEquals(0, $character->shards);
        $this->assertEquals(0, $character->gold_dust);
        $this->assertEquals(0, $character->copper_coins);
    }

    public function test_currency_event_reward_rewards_shards_and_gold_dust_without_copper_coins_item()
    {
        $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CURRENCY_DROPS,
            'currently_running' => true,
        ]);

        $character = $this->character->getCharacter();
        $character->update([
            'shards' => 0,
            'gold_dust' => 0,
            'copper_coins' => 0,
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'is_celestial_entity' => false,
        ]);

        $this->characterCurrencyRewardService
            ->setCharacter($character->refresh())
            ->currencyEventReward($monster);

        $character = $this->characterCurrencyRewardService->getCharacter();

        $this->assertGreaterThanOrEqual(1, $character->shards);
        $this->assertLessThanOrEqual(500, $character->shards);

        $this->assertGreaterThanOrEqual(1, $character->gold_dust);
        $this->assertLessThanOrEqual(500, $character->gold_dust);

        $this->assertEquals(0, $character->copper_coins);
    }

    public function test_currency_event_reward_rewards_and_caps_with_copper_coins_item()
    {
        $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CURRENCY_DROPS,
            'currently_running' => true,
        ]);

        $copperCoinsItem = $this->createItem([
            'effect' => ItemEffectsValue::GET_COPPER_COINS,
            'type' => 'quest',
        ]);

        $character = $this->character->inventoryManagement()->giveItem($copperCoinsItem)->getCharacter();
        $character->update([
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'copper_coins' => MaxCurrenciesValue::MAX_COPPER,
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'is_celestial_entity' => false,
        ]);

        $this->characterCurrencyRewardService
            ->setCharacter($character->refresh())
            ->currencyEventReward($monster);

        $character = $this->characterCurrencyRewardService->getCharacter();

        $this->assertEquals(MaxCurrenciesValue::MAX_SHARDS, $character->shards);
        $this->assertEquals(MaxCurrenciesValue::MAX_GOLD_DUST, $character->gold_dust);
        $this->assertEquals(MaxCurrenciesValue::MAX_COPPER, $character->copper_coins);
    }

    public function test_currency_event_reward_awards_currencies_when_auto_battling()
    {
        $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CURRENCY_DROPS,
            'currently_running' => true,
        ]);

        $character = $this->character->getCharacter();

        $this->createExploringAutomation([
            'character_id' => $character->id,
        ]);

        $character->update([
            'shards' => 0,
            'gold_dust' => 0,
            'copper_coins' => 0,
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $character->map->game_map_id,
            'is_celestial_entity' => false,
        ]);

        $this->characterCurrencyRewardService
            ->setCharacter($character->refresh())
            ->currencyEventReward($monster);

        $character = $this->characterCurrencyRewardService->getCharacter();

        $this->assertGreaterThanOrEqual(1, $character->shards);
        $this->assertLessThanOrEqual(500, $character->shards);

        $this->assertGreaterThanOrEqual(1, $character->gold_dust);
        $this->assertLessThanOrEqual(500, $character->gold_dust);
    }

    public function test_distribute_copper_coins_does_nothing_when_not_purgatory()
    {
        $surfaceGameMap = $this->createGameMap([
            'name' => 'Surface',
            'path' => 'surface',
            'kingdom_color' => '#ffffff',
        ]);

        $copperCoinsItem = $this->createItem([
            'effect' => ItemEffectsValue::GET_COPPER_COINS,
            'type' => 'quest',
        ]);

        $this->createItem([
            'effect' => ItemEffectsValue::MERCENARY_SLOT_BONUS,
            'type' => 'quest',
        ]);

        $character = $this->character->inventoryManagement()->giveItem($copperCoinsItem)->getCharacter();
        $character->update([
            'gold' => 0,
            'copper_coins' => 0,
        ]);

        $character->map->update([
            'game_map_id' => $surfaceGameMap->id,
        ]);

        $character = $character->refresh();

        $monster = $this->createMonster([
            'gold' => 0,
            'game_map_id' => $surfaceGameMap->id,
        ]);

        $this->characterCurrencyRewardService
            ->setCharacter($character)
            ->giveCurrencies($monster);

        $character = $this->characterCurrencyRewardService->getCharacter();

        $this->assertEquals(0, $character->copper_coins);
    }

    public function test_distribute_copper_coins_does_nothing_when_missing_copper_coin_slot()
    {
        $purgatoryGameMap = $this->createGameMap([
            'name' => 'Purgatory',
            'path' => 'purgatory',
            'kingdom_color' => '#ffffff',
        ]);

        $this->createItem([
            'effect' => ItemEffectsValue::GET_COPPER_COINS,
            'type' => 'quest',
        ]);

        $this->createItem([
            'effect' => ItemEffectsValue::MERCENARY_SLOT_BONUS,
            'type' => 'quest',
        ]);

        $character = $this->character->getCharacter();
        $character->update([
            'gold' => 0,
            'copper_coins' => 0,
        ]);

        $character->map->update([
            'game_map_id' => $purgatoryGameMap->id,
        ]);

        $character = $character->refresh();

        $monster = $this->createMonster([
            'gold' => 0,
            'game_map_id' => $purgatoryGameMap->id,
        ]);

        $this->characterCurrencyRewardService
            ->setCharacter($character)
            ->giveCurrencies($monster);

        $character = $this->characterCurrencyRewardService->getCharacter();

        $this->assertEquals(0, $character->copper_coins);
    }

    public function test_distribute_copper_coins_sets_to_max_when_slot_present_and_no_dungeon_and_no_merc()
    {
        $purgatoryGameMap = $this->createGameMap([
            'name' => 'Purgatory',
            'path' => 'purgatory',
            'kingdom_color' => '#ffffff',
        ]);

        $copperCoinsItem = $this->createItem([
            'effect' => ItemEffectsValue::GET_COPPER_COINS,
            'type' => 'quest',
        ]);

        $this->createItem([
            'effect' => ItemEffectsValue::MERCENARY_SLOT_BONUS,
            'type' => 'quest',
        ]);

        $character = $this->character->inventoryManagement()->giveItem($copperCoinsItem)->getCharacter();
        $character->update([
            'gold' => 0,
            'copper_coins' => 0,
        ]);

        $character->map->update([
            'game_map_id' => $purgatoryGameMap->id,
        ]);

        $character = $character->refresh();

        $monster = $this->createMonster([
            'gold' => 0,
            'game_map_id' => $purgatoryGameMap->id,
        ]);

        $this->characterCurrencyRewardService
            ->setCharacter($character)
            ->giveCurrencies($monster);

        $character = $this->characterCurrencyRewardService->getCharacter();

        $this->assertEquals(MaxCurrenciesValue::MAX_COPPER, $character->copper_coins);
    }

    public function test_distribute_copper_coins_increases_negative_copper_coins_when_dungeon_and_merc_slot()
    {
        $purgatoryGameMap = $this->createGameMap([
            'name' => 'Purgatory',
            'path' => 'purgatory',
            'kingdom_color' => '#ffffff',
        ]);

        $copperCoinsItem = $this->createItem([
            'effect' => ItemEffectsValue::GET_COPPER_COINS,
            'type' => 'quest',
        ]);

        $mercenarySlotBonusItem = $this->createItem([
            'effect' => ItemEffectsValue::MERCENARY_SLOT_BONUS,
            'type' => 'quest',
        ]);

        $characterFactory = $this->character->inventoryManagement()->giveItem($copperCoinsItem);
        $character = $characterFactory->giveItem($mercenarySlotBonusItem)->getCharacter();

        $character->update([
            'gold' => 0,
            'copper_coins' => -1000,
        ]);

        $character->map->update([
            'game_map_id' => $purgatoryGameMap->id,
            'character_position_x' => 12,
            'character_position_y' => 12,
        ]);

        $this->createLocation([
            'game_map_id' => $purgatoryGameMap->id,
            'x' => 12,
            'y' => 12,
            'type' => LocationType::PURGATORY_DUNGEONS,
            'enemy_strength_type' => 1,
        ]);

        $character = $character->refresh();

        $monster = $this->createMonster([
            'gold' => 0,
            'game_map_id' => $purgatoryGameMap->id,
        ]);

        $this->characterCurrencyRewardService
            ->setCharacter($character)
            ->giveCurrencies($monster);

        $character = $this->characterCurrencyRewardService->getCharacter();

        $this->assertGreaterThan(-1000, $character->copper_coins);
        $this->assertLessThan(MaxCurrenciesValue::COPPER, $character->copper_coins);
    }
}
