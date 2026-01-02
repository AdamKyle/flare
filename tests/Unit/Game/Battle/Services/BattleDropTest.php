<?php

namespace Tests\Unit\Game\Battle\Services;

use App\Flare\Items\Builders\RandomItemDropBuilder;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use App\Flare\Values\MapNameValue;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Battle\Services\BattleDrop;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Types\CharacterMessageTypes;
use App\Game\Shop\Services\ShopService;
use App\Game\Skills\Services\DisenchantService;
use Facades\App\Flare\Calculators\DropCheckCalculator;
use Facades\App\Flare\Calculators\SellItemCalculator;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterAutomation;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateQuest;

class BattleDropTest extends TestCase
{
    use CreateCharacterAutomation;
    use CreateGameMap;
    use CreateItem;
    use CreateItemAffix;
    use CreateLocation;
    use CreateMonster;
    use CreateNpc;
    use CreateQuest;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config(['broadcasting.default' => 'log']);
    }

    public function test_handle_drop_returns_null_when_cannot_get_drop(): void
    {
        $gameMap = $this->createGameMap(['name' => MapNameValue::SURFACE, 'path' => 'path', 'default' => true]);

        $character = (new CharacterFactory())
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16, $gameMap)
            ->getCharacter()
            ->refresh();

        $monster = $this->createMonster(['game_map_id' => $gameMap->id]);

        $randomItemDropBuilder = Mockery::mock(RandomItemDropBuilder::class);
        $randomItemDropBuilder->shouldNotReceive('generateItem');

        $battleDrop = $this->buildBattleDrop($monster, $randomItemDropBuilder);

        $this->assertNull($battleDrop->handleDrop($character, false));
    }

    public function test_handle_drop_returns_null_when_drop_is_null(): void
    {
        $gameMap = $this->createGameMap(['name' => MapNameValue::SURFACE, 'path' => 'path', 'default' => true]);

        $character = (new CharacterFactory())
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16, $gameMap)
            ->getCharacter()
            ->refresh();

        $monster = $this->createMonster(['game_map_id' => $gameMap->id]);

        $randomItemDropBuilder = Mockery::mock(RandomItemDropBuilder::class);
        $randomItemDropBuilder->shouldReceive('generateItem')
            ->once()
            ->withArgs(function (int $maxLevel) use ($character): bool {
                return $maxLevel === $character->level;
            })
            ->andReturnNull();

        $battleDrop = $this->buildBattleDrop($monster, $randomItemDropBuilder);

        $this->assertNull($battleDrop->handleDrop($character, true));
    }

    public function test_handle_drop_returns_drop_when_no_affix(): void
    {
        $gameMap = $this->createGameMap(['name' => MapNameValue::SURFACE, 'path' => 'path', 'default' => true]);

        $character = (new CharacterFactory())
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16, $gameMap)
            ->updateUser(['auto_disenchant' => false])
            ->getCharacter()
            ->refresh();

        $monster = $this->createMonster(['game_map_id' => $gameMap->id]);

        $drop = $this->createItem([
            'type' => 'weapon',
            'skill_level_required' => 1,
            'item_suffix_id' => null,
            'item_prefix_id' => null,
            'specialty_type' => null,
        ]);

        $randomItemDropBuilder = Mockery::mock(RandomItemDropBuilder::class);
        $randomItemDropBuilder->shouldReceive('generateItem')->once()->andReturn($drop);

        $battleDrop = $this->buildBattleDrop($monster, $randomItemDropBuilder);

        $returned = $battleDrop->handleDrop($character, true, false);

        $this->assertInstanceOf(Item::class, $returned);
        $this->assertSame(0, $character->refresh()->inventory->slots()->count());
    }

    public function test_handle_drop_returns_drop_when_return_item_true_even_with_affix(): void
    {
        $gameMap = $this->createGameMap(['name' => MapNameValue::SURFACE, 'path' => 'path', 'default' => true]);

        $character = (new CharacterFactory())
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16, $gameMap)
            ->updateUser(['auto_disenchant' => false])
            ->getCharacter()
            ->refresh();

        $monster = $this->createMonster(['game_map_id' => $gameMap->id]);

        $prefix = $this->createItemAffix([
            'type' => 'prefix',
            'skill_level_required' => 1,
        ]);

        $drop = $this->createItem([
            'type' => 'weapon',
            'skill_level_required' => 1,
            'item_prefix_id' => $prefix->id,
            'item_suffix_id' => null,
            'specialty_type' => null,
        ])->refresh();

        $randomItemDropBuilder = Mockery::mock(RandomItemDropBuilder::class);
        $randomItemDropBuilder->shouldReceive('generateItem')->once()->andReturn($drop);

        $battleDrop = $this->buildBattleDrop($monster, $randomItemDropBuilder);

        $returned = $battleDrop->handleDrop($character, true, true);

        $this->assertInstanceOf(Item::class, $returned);
        $this->assertSame(0, $character->refresh()->inventory->slots()->count());
    }

    public function test_handle_drop_adds_item_to_inventory_when_drop_has_affix_and_return_item_false(): void
    {
        Event::fake([ServerMessageEvent::class, GlobalMessageEvent::class]);

        $gameMap = $this->createGameMap(['name' => MapNameValue::SURFACE, 'path' => 'path', 'default' => true]);

        $character = (new CharacterFactory())
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16, $gameMap)
            ->updateUser(['auto_disenchant' => false])
            ->getCharacter()
            ->refresh();

        $monster = $this->createMonster(['game_map_id' => $gameMap->id]);

        $prefix = $this->createItemAffix([
            'type' => 'prefix',
            'skill_level_required' => 1,
        ]);

        $drop = $this->createItem([
            'type' => 'weapon',
            'skill_level_required' => 1,
            'item_prefix_id' => $prefix->id,
            'item_suffix_id' => null,
            'specialty_type' => null,
        ])->refresh();

        $randomItemDropBuilder = Mockery::mock(RandomItemDropBuilder::class);
        $randomItemDropBuilder->shouldReceive('generateItem')->once()->andReturn($drop);

        $battleDrop = $this->buildBattleDrop($monster, $randomItemDropBuilder);

        $this->assertNull($battleDrop->handleDrop($character, true, false));

        $this->assertSame(1, $character->refresh()->inventory->slots()->count());
        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function test_handle_drop_sends_inventory_full_message_when_inventory_is_full(): void
    {
        $gameMap = $this->createGameMap(['name' => MapNameValue::SURFACE, 'path' => 'path', 'default' => true]);

        $characterFactory = (new CharacterFactory())
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16, $gameMap)
            ->updateUser(['auto_disenchant' => false])
            ->updateCharacter(['inventory_max' => 1]);

        $character = $characterFactory->getCharacter()->refresh();

        $existingItem = $this->createItem([
            'type' => 'weapon',
            'skill_level_required' => 1,
            'item_suffix_id' => null,
            'item_prefix_id' => null,
            'specialty_type' => null,
        ]);

        $characterFactory->inventoryManagement()->giveItem($existingItem);

        $character = $characterFactory->getCharacter()->refresh();

        $monster = $this->createMonster(['game_map_id' => $gameMap->id]);

        $prefix = $this->createItemAffix([
            'type' => 'prefix',
            'skill_level_required' => 1,
        ]);

        $drop = $this->createItem([
            'type' => 'weapon',
            'skill_level_required' => 1,
            'item_prefix_id' => $prefix->id,
            'item_suffix_id' => null,
            'specialty_type' => null,
        ])->refresh();

        $randomItemDropBuilder = Mockery::mock(RandomItemDropBuilder::class);
        $randomItemDropBuilder->shouldReceive('generateItem')->once()->andReturn($drop);

        ServerMessageHandler::shouldReceive('handleMessage')
            ->once()
            ->withArgs(function ($user, CharacterMessageTypes $type) use ($character): bool {
                return $user->id === $character->user->id && $type === CharacterMessageTypes::INVENTORY_IS_FULL;
            });

        $battleDrop = $this->buildBattleDrop($monster, $randomItemDropBuilder);

        $this->assertNull($battleDrop->handleDrop($character, true, false));
        $this->assertSame(1, $character->refresh()->inventory->slots()->count());
    }

    public function test_handle_drop_auto_disenchant_all_calls_disenchant_when_auto_sell_disabled(): void
    {
        $gameMap = $this->createGameMap(['name' => MapNameValue::SURFACE, 'path' => 'path', 'default' => true]);

        $character = (new CharacterFactory())
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16, $gameMap)
            ->updateUser([
                'auto_disenchant' => true,
                'auto_disenchant_amount' => 'all',
                'auto_sell_item' => false,
            ])
            ->updateCharacter(['gold_dust' => 0])
            ->getCharacter()
            ->refresh();

        $monster = $this->createMonster(['game_map_id' => $gameMap->id]);

        $prefix = $this->createItemAffix([
            'type' => 'prefix',
            'skill_level_required' => 1,
        ]);

        $drop = $this->createItem([
            'type' => 'weapon',
            'skill_level_required' => 1,
            'item_prefix_id' => $prefix->id,
            'item_suffix_id' => null,
            'specialty_type' => null,
        ])->refresh();

        $randomItemDropBuilder = Mockery::mock(RandomItemDropBuilder::class);
        $randomItemDropBuilder->shouldReceive('generateItem')->once()->andReturn($drop);

        $disenchantService = Mockery::mock(DisenchantService::class);
        $disenchantService->shouldReceive('setUp')->once()->withArgs(function (Character $passedCharacter): bool {
            return $passedCharacter->id > 0;
        })->andReturnSelf();
        $disenchantService->shouldReceive('disenchantItemWithSkill')->once();

        $shopService = Mockery::mock(ShopService::class);
        $shopService->shouldNotReceive('autoSellItem');

        $battleDrop = $this->buildBattleDropWithServices($monster, $randomItemDropBuilder, $disenchantService, $shopService);

        $this->assertNull($battleDrop->handleDrop($character, true, false));
        $this->assertSame(0, $character->refresh()->inventory->slots()->count());
    }

    public function test_handle_drop_auto_disenchant_all_calls_disenchant_when_auto_sell_enabled_but_not_capped(): void
    {
        $gameMap = $this->createGameMap(['name' => MapNameValue::SURFACE, 'path' => 'path', 'default' => true]);

        $character = (new CharacterFactory())
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16, $gameMap)
            ->updateUser([
                'auto_disenchant' => true,
                'auto_disenchant_amount' => 'all',
                'auto_sell_item' => true,
            ])
            ->updateCharacter(['gold_dust' => 0])
            ->getCharacter()
            ->refresh();

        $monster = $this->createMonster(['game_map_id' => $gameMap->id]);

        $prefix = $this->createItemAffix([
            'type' => 'prefix',
            'skill_level_required' => 1,
        ]);

        $drop = $this->createItem([
            'type' => 'weapon',
            'skill_level_required' => 1,
            'item_prefix_id' => $prefix->id,
            'item_suffix_id' => null,
            'specialty_type' => null,
        ])->refresh();

        $randomItemDropBuilder = Mockery::mock(RandomItemDropBuilder::class);
        $randomItemDropBuilder->shouldReceive('generateItem')->once()->andReturn($drop);

        $disenchantService = Mockery::mock(DisenchantService::class);
        $disenchantService->shouldReceive('setUp')->once()->andReturnSelf();
        $disenchantService->shouldReceive('disenchantItemWithSkill')->once();

        $shopService = Mockery::mock(ShopService::class);
        $shopService->shouldNotReceive('autoSellItem');

        $battleDrop = $this->buildBattleDropWithServices($monster, $randomItemDropBuilder, $disenchantService, $shopService);

        $this->assertNull($battleDrop->handleDrop($character, true, false));
        $this->assertSame(0, $character->refresh()->inventory->slots()->count());
    }

    public function test_handle_drop_auto_disenchant_all_auto_sells_when_gold_dust_capped_and_auto_sell_enabled(): void
    {
        $gameMap = $this->createGameMap(['name' => MapNameValue::SURFACE, 'path' => 'path', 'default' => true]);

        $character = (new CharacterFactory())
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16, $gameMap)
            ->updateUser([
                'auto_disenchant' => true,
                'auto_disenchant_amount' => 'all',
                'auto_sell_item' => true,
            ])
            ->updateCharacter(['gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST])
            ->getCharacter()
            ->refresh();

        $monster = $this->createMonster(['game_map_id' => $gameMap->id]);

        $prefix = $this->createItemAffix([
            'type' => 'prefix',
            'skill_level_required' => 1,
        ]);

        $drop = $this->createItem([
            'type' => 'weapon',
            'skill_level_required' => 1,
            'item_prefix_id' => $prefix->id,
            'item_suffix_id' => null,
            'specialty_type' => null,
            'cost' => 1000,
        ])->refresh();

        $randomItemDropBuilder = Mockery::mock(RandomItemDropBuilder::class);
        $randomItemDropBuilder->shouldReceive('generateItem')->once()->andReturn($drop);

        $disenchantService = Mockery::mock(DisenchantService::class);
        $disenchantService->shouldNotReceive('setUp');
        $disenchantService->shouldNotReceive('disenchantItemWithSkill');

        $shopService = Mockery::mock(ShopService::class);
        $shopService->shouldReceive('autoSellItem')
            ->once()
            ->withArgs(function (Character $passedCharacter, Item $passedItem) use ($character, $drop): bool {
                return $passedCharacter->id === $character->id && $passedItem->id === $drop->id;
            });

        $battleDrop = $this->buildBattleDropWithServices($monster, $randomItemDropBuilder, $disenchantService, $shopService);

        $this->assertNull($battleDrop->handleDrop($character, true, false));
        $this->assertSame(0, $character->refresh()->inventory->slots()->count());
    }

    public function test_handle_drop_auto_disenchant_one_billion_gives_item_when_over_threshold(): void
    {
        Event::fake([ServerMessageEvent::class, GlobalMessageEvent::class]);

        $gameMap = $this->createGameMap(['name' => MapNameValue::SURFACE, 'path' => 'path', 'default' => true]);

        $character = (new CharacterFactory())
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16, $gameMap)
            ->updateUser([
                'auto_disenchant' => true,
                'auto_disenchant_amount' => '1-billion',
                'auto_sell_item' => false,
            ])
            ->getCharacter()
            ->refresh();

        $monster = $this->createMonster(['game_map_id' => $gameMap->id]);

        $prefix = $this->createItemAffix([
            'type' => 'prefix',
            'skill_level_required' => 1,
        ]);

        $drop = $this->createItem([
            'type' => 'weapon',
            'skill_level_required' => 1,
            'item_prefix_id' => $prefix->id,
            'item_suffix_id' => null,
            'specialty_type' => null,
            'cost' => 2_000_000_000,
        ])->refresh();

        SellItemCalculator::shouldReceive('fetchSalePriceWithAffixes')
            ->once()
            ->withArgs(function (Item $passedItem) use ($drop): bool {
                return $passedItem->id === $drop->id;
            })
            ->andReturn(1_000_000_000);

        $randomItemDropBuilder = Mockery::mock(RandomItemDropBuilder::class);
        $randomItemDropBuilder->shouldReceive('generateItem')->once()->andReturn($drop);

        $battleDrop = $this->buildBattleDrop($monster, $randomItemDropBuilder);

        $this->assertNull($battleDrop->handleDrop($character, true, false));
        $this->assertSame(1, $character->refresh()->inventory->slots()->count());
        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function test_handle_drop_auto_disenchant_one_billion_auto_sells_when_under_threshold_and_gold_dust_capped(): void
    {
        $gameMap = $this->createGameMap(['name' => MapNameValue::SURFACE, 'path' => 'path', 'default' => true]);

        $character = (new CharacterFactory())
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16, $gameMap)
            ->updateUser([
                'auto_disenchant' => true,
                'auto_disenchant_amount' => '1-billion',
                'auto_sell_item' => true,
            ])
            ->updateCharacter(['gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST])
            ->getCharacter()
            ->refresh();

        $monster = $this->createMonster(['game_map_id' => $gameMap->id]);

        $prefix = $this->createItemAffix([
            'type' => 'prefix',
            'skill_level_required' => 1,
        ]);

        $drop = $this->createItem([
            'type' => 'weapon',
            'skill_level_required' => 1,
            'item_prefix_id' => $prefix->id,
            'item_suffix_id' => null,
            'specialty_type' => null,
            'cost' => 100,
        ])->refresh();

        SellItemCalculator::shouldReceive('fetchSalePriceWithAffixes')
            ->once()
            ->andReturn(1);

        $randomItemDropBuilder = Mockery::mock(RandomItemDropBuilder::class);
        $randomItemDropBuilder->shouldReceive('generateItem')->once()->andReturn($drop);

        $disenchantService = Mockery::mock(DisenchantService::class);
        $disenchantService->shouldNotReceive('setUp');
        $disenchantService->shouldNotReceive('disenchantItemWithSkill');

        $shopService = Mockery::mock(ShopService::class);
        $shopService->shouldReceive('autoSellItem')->once();

        $battleDrop = $this->buildBattleDropWithServices($monster, $randomItemDropBuilder, $disenchantService, $shopService);

        $this->assertNull($battleDrop->handleDrop($character, true, false));
        $this->assertSame(0, $character->refresh()->inventory->slots()->count());
    }

    public function test_handle_drop_auto_disenchant_does_nothing_when_amount_is_unknown(): void
    {
        $gameMap = $this->createGameMap(['name' => MapNameValue::SURFACE, 'path' => 'path', 'default' => true]);

        $character = (new CharacterFactory())
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16, $gameMap)
            ->updateUser([
                'auto_disenchant' => true,
                'auto_disenchant_amount' => 'unknown',
                'auto_sell_item' => false,
            ])
            ->getCharacter()
            ->refresh();

        $monster = $this->createMonster(['game_map_id' => $gameMap->id]);

        $prefix = $this->createItemAffix([
            'type' => 'prefix',
            'skill_level_required' => 1,
        ]);

        $drop = $this->createItem([
            'type' => 'weapon',
            'skill_level_required' => 1,
            'item_prefix_id' => $prefix->id,
            'item_suffix_id' => null,
            'specialty_type' => null,
        ])->refresh();

        $randomItemDropBuilder = Mockery::mock(RandomItemDropBuilder::class);
        $randomItemDropBuilder->shouldReceive('generateItem')->once()->andReturn($drop);

        $disenchantService = Mockery::mock(DisenchantService::class);
        $disenchantService->shouldNotReceive('setUp');
        $disenchantService->shouldNotReceive('disenchantItemWithSkill');

        $shopService = Mockery::mock(ShopService::class);
        $shopService->shouldNotReceive('autoSellItem');

        $battleDrop = $this->buildBattleDropWithServices($monster, $randomItemDropBuilder, $disenchantService, $shopService);

        $this->assertNull($battleDrop->handleDrop($character, true, false));
        $this->assertSame(0, $character->refresh()->inventory->slots()->count());
    }

    public function test_give_mythic_item_creates_inventory_slot_and_dispatches_global_message(): void
    {
        Event::fake([ServerMessageEvent::class, GlobalMessageEvent::class]);

        $gameMap = $this->createGameMap(['name' => MapNameValue::SURFACE, 'path' => 'path', 'default' => true]);

        $character = (new CharacterFactory())
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16, $gameMap)
            ->getCharacter()
            ->refresh();

        $item = $this->createItem([
            'type' => 'weapon',
            'skill_level_required' => 1,
            'item_suffix_id' => null,
            'item_prefix_id' => null,
            'specialty_type' => null,
        ]);

        $battleDrop = $this->buildBattleDrop($this->createMonster(['game_map_id' => $gameMap->id]));

        $battleDrop->giveMythicItem($character, $item);

        $this->assertSame(1, $character->refresh()->inventory->slots()->count());
        Event::assertDispatched(ServerMessageEvent::class);
        Event::assertDispatched(GlobalMessageEvent::class);
    }

    public function test_handle_monster_quest_drop_returns_null_when_no_quest_item(): void
    {
        $gameMap = $this->createGameMap(['name' => MapNameValue::SURFACE, 'path' => 'path', 'default' => true]);

        $character = (new CharacterFactory())
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16, $gameMap)
            ->getCharacter()
            ->refresh();

        $monster = $this->createMonster([
            'game_map_id' => $gameMap->id,
            'quest_item_id' => null,
        ]);

        $battleDrop = $this->buildBattleDrop($monster)->setLootingChance(1.0)->setGameMapBonus(1.0);

        $this->assertNull($battleDrop->handleMonsterQuestDrop($character, false));
    }

    public function test_handle_monster_quest_drop_returns_null_when_drop_check_fails(): void
    {
        $gameMap = $this->createGameMap(['name' => MapNameValue::SURFACE, 'path' => 'path', 'default' => true]);

        $character = (new CharacterFactory())
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16, $gameMap)
            ->getCharacter()
            ->refresh();

        $questItem = $this->createItem([
            'type' => 'quest',
            'drop_location_id' => null,
            'item_suffix_id' => null,
            'item_prefix_id' => null,
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $gameMap->id,
            'quest_item_id' => $questItem->id,
        ])->refresh();

        DropCheckCalculator::shouldReceive('fetchQuestItemDropCheck')->once()->andReturnFalse();

        $battleDrop = $this->buildBattleDrop($monster)->setLootingChance(1.0)->setGameMapBonus(0.0);

        $this->assertNull($battleDrop->handleMonsterQuestDrop($character, false));
        $this->assertSame(0, $character->refresh()->inventory->slots()->count());
    }

    public function test_handle_monster_quest_drop_returns_quest_item_when_return_item_true_and_drop_check_passes(): void
    {
        $gameMap = $this->createGameMap(['name' => MapNameValue::SURFACE, 'path' => 'path', 'default' => true]);

        $character = (new CharacterFactory())
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16, $gameMap)
            ->getCharacter()
            ->refresh();

        $questItem = $this->createItem([
            'type' => 'quest',
            'drop_location_id' => null,
            'item_suffix_id' => null,
            'item_prefix_id' => null,
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $gameMap->id,
            'quest_item_id' => $questItem->id,
        ])->refresh();

        DropCheckCalculator::shouldReceive('fetchQuestItemDropCheck')->once()->andReturnTrue();

        $battleDrop = $this->buildBattleDrop($monster)->setLootingChance(1.0)->setGameMapBonus(0.0);

        $returned = $battleDrop->handleMonsterQuestDrop($character, true);

        $this->assertInstanceOf(Item::class, $returned);
        $this->assertSame($questItem->id, $returned->id);
        $this->assertSame(0, $character->refresh()->inventory->slots()->count());
    }

    public function test_handle_monster_quest_drop_gives_quest_item_when_drop_check_passes_and_return_item_false(): void
    {
        Event::fake([ServerMessageEvent::class, GlobalMessageEvent::class]);

        $gameMap = $this->createGameMap(['name' => MapNameValue::SURFACE, 'path' => 'path', 'default' => true]);

        $character = (new CharacterFactory())
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16, $gameMap)
            ->updateUser(['auto_disenchant' => false])
            ->getCharacter()
            ->refresh();

        $questItem = $this->createItem([
            'type' => 'quest',
            'drop_location_id' => null,
            'item_suffix_id' => null,
            'item_prefix_id' => null,
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $gameMap->id,
            'quest_item_id' => $questItem->id,
        ])->refresh();

        DropCheckCalculator::shouldReceive('fetchQuestItemDropCheck')->once()->andReturnTrue();

        $battleDrop = $this->buildBattleDrop($monster)->setLootingChance(1.0)->setGameMapBonus(0.0);

        $this->assertNull($battleDrop->handleMonsterQuestDrop($character, false));
        $this->assertSame(1, $character->refresh()->inventory->slots()->count());
        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function test_handle_monster_quest_drop_does_not_give_quest_item_if_already_in_inventory(): void
    {
        Event::fake([ServerMessageEvent::class, GlobalMessageEvent::class]);

        $gameMap = $this->createGameMap(['name' => MapNameValue::SURFACE, 'path' => 'path', 'default' => true]);

        $characterFactory = (new CharacterFactory())
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16, $gameMap)
            ->updateUser(['auto_disenchant' => false]);

        $character = $characterFactory->getCharacter()->refresh();

        $questItem = $this->createItem([
            'type' => 'quest',
            'drop_location_id' => null,
            'item_suffix_id' => null,
            'item_prefix_id' => null,
        ]);

        $characterFactory->inventoryManagement()->giveItem($questItem);

        $character = $characterFactory->getCharacter()->refresh();

        $monster = $this->createMonster([
            'game_map_id' => $gameMap->id,
            'quest_item_id' => $questItem->id,
        ])->refresh();

        DropCheckCalculator::shouldReceive('fetchQuestItemDropCheck')->once()->andReturnTrue();

        $battleDrop = $this->buildBattleDrop($monster)->setLootingChance(1.0)->setGameMapBonus(0.0);

        $this->assertNull($battleDrop->handleMonsterQuestDrop($character, false));

        $this->assertSame(1, $character->refresh()->inventory->slots()->count());
        Event::assertNotDispatched(GlobalMessageEvent::class);
    }

    public function test_handle_special_location_quest_item_returns_when_exploring_automation_exists(): void
    {
        $gameMap = $this->createGameMap(['name' => MapNameValue::SURFACE, 'path' => 'path', 'default' => true]);

        $characterFactory = (new CharacterFactory())
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16, $gameMap);

        $character = $characterFactory->getCharacter()->refresh();

        $this->createExploringAutomation(['character_id' => $character->id]);

        $location = $this->createLocation();

        $battleDrop = $this->buildBattleDrop($this->createMonster(['game_map_id' => $gameMap->id]))
            ->setSpecialLocation($location)
            ->setLootingChance(1.0);

        $battleDrop->handleSpecialLocationQuestItem($character);

        $this->assertSame(0, $character->refresh()->inventory->slots()->count());
    }

    public function test_handle_special_location_quest_item_does_nothing_when_no_items(): void
    {
        $gameMap = $this->createGameMap(['name' => MapNameValue::SURFACE, 'path' => 'path', 'default' => true]);

        $character = (new CharacterFactory())
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16, $gameMap)
            ->getCharacter()
            ->refresh();

        $location = $this->createLocation();

        $battleDrop = $this->buildBattleDrop($this->createMonster(['game_map_id' => $gameMap->id]))
            ->setSpecialLocation($location)
            ->setLootingChance(1.0);

        $battleDrop->handleSpecialLocationQuestItem($character);

        $this->assertSame(0, $character->refresh()->inventory->slots()->count());
    }

    public function test_handle_special_location_quest_item_does_nothing_when_drop_check_fails(): void
    {
        $gameMap = $this->createGameMap(['name' => MapNameValue::SURFACE, 'path' => 'path', 'default' => true]);

        $character = (new CharacterFactory())
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16, $gameMap)
            ->getCharacter()
            ->refresh();

        $location = $this->createLocation();

        $questItem = $this->createItem([
            'type' => 'quest',
            'drop_location_id' => $location->id,
            'item_suffix_id' => null,
            'item_prefix_id' => null,
        ]);

        $npc = $this->createNpc();

        $this->createQuest([
            'npc_id' => $npc->id,
            'item_id' => $questItem->id,
        ]);

        DropCheckCalculator::shouldReceive('fetchDifficultItemChance')->once()->andReturnFalse();

        $battleDrop = $this->buildBattleDrop($this->createMonster(['game_map_id' => $gameMap->id]))
            ->setSpecialLocation($location)
            ->setLootingChance(0.10);

        $battleDrop->handleSpecialLocationQuestItem($character);

        $this->assertSame(0, $character->refresh()->inventory->slots()->count());
    }

    public function test_handle_drop_covers_get_max_level_based_on_plane_branches(): void
    {
        $cases = [
            [MapNameValue::SURFACE, 49, 49],
            [MapNameValue::SURFACE, 50, 50],
            [MapNameValue::SURFACE, 99, 50],
            [MapNameValue::LABYRINTH, 149, 149],
            [MapNameValue::LABYRINTH, 150, 150],
            [MapNameValue::LABYRINTH, 999, 150],
            [MapNameValue::DUNGEONS, 239, 239],
            [MapNameValue::DUNGEONS, 240, 240],
            [MapNameValue::DUNGEONS, 999, 240],
            [MapNameValue::HELL, 299, 299],
            [MapNameValue::HELL, 300, 300],
            [MapNameValue::HELL, 999, 300],
            [MapNameValue::PURGATORY, 1, 300],
        ];

        foreach ($cases as $case) {
            [$mapName, $level, $expectedMax] = $case;

            Cache::flush();

            $gameMap = $this->createGameMap(['name' => $mapName, 'path' => 'path', 'default' => false]);

            $character = (new CharacterFactory())
                ->createBaseCharacter()
                ->givePlayerLocation(16, 16, $gameMap)
                ->updateCharacter(['level' => $level])
                ->getCharacter()
                ->refresh();

            $monster = $this->createMonster(['game_map_id' => $gameMap->id]);

            $randomItemDropBuilder = Mockery::mock(RandomItemDropBuilder::class);
            $randomItemDropBuilder->shouldReceive('generateItem')
                ->once()
                ->withArgs(function (int $maxLevel) use ($expectedMax): bool {
                    return $maxLevel === $expectedMax;
                })
                ->andReturnNull();

            $battleDrop = $this->buildBattleDrop($monster, $randomItemDropBuilder);

            $this->assertNull($battleDrop->handleDrop($character, true, false));
        }

        $this->assertTrue(true);
    }

    public function test_handle_special_location_quest_item_gives_eligible_quest_item_and_clamps_looting_chance(): void
    {
        Event::fake([ServerMessageEvent::class, GlobalMessageEvent::class]);

        $gameMap = $this->createGameMap(['name' => MapNameValue::SURFACE, 'path' => 'path', 'default' => true]);

        $characterFactory = (new CharacterFactory())
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16, $gameMap)
            ->updateUser(['auto_disenchant' => false]);

        $character = $characterFactory->getCharacter()->refresh();

        $location = $this->createLocation();

        $npc = $this->createNpc();

        $alreadyHasItem = $this->createItem([
            'type' => 'quest',
            'drop_location_id' => $location->id,
            'item_suffix_id' => null,
            'item_prefix_id' => null,
        ]);

        $characterFactory->inventoryManagement()->giveItem($alreadyHasItem);

        $character = $character->refresh()->load('inventory.slots');

        $this->createQuest([
            'npc_id' => $npc->id,
            'item_id' => $alreadyHasItem->id,
        ]);

        $completedQuestItem = $this->createItem([
            'type' => 'quest',
            'drop_location_id' => $location->id,
            'item_suffix_id' => null,
            'item_prefix_id' => null,
        ]);

        $completedQuest = $this->createQuest([
            'npc_id' => $npc->id,
            'item_id' => $completedQuestItem->id,
        ]);

        $this->createCompletedQuest([
            'character_id' => $character->id,
            'quest_id' => $completedQuest->id,
        ]);

        $availableQuestItem = $this->createItem([
            'type' => 'quest',
            'drop_location_id' => $location->id,
            'item_suffix_id' => null,
            'item_prefix_id' => null,
        ]);

        $this->createQuest([
            'npc_id' => $npc->id,
            'item_id' => $availableQuestItem->id,
        ]);

        DropCheckCalculator::shouldReceive('fetchDifficultItemChance')
            ->once()
            ->withArgs(function (float $chance, int $maxRoll): bool {
                return abs($chance - 0.45) < 0.000001 && $maxRoll === 100;
            })
            ->andReturnTrue();

        $battleDrop = $this->buildBattleDrop($this->createMonster(['game_map_id' => $gameMap->id]))
            ->setSpecialLocation($location)
            ->setLootingChance(1.0);

        $battleDrop->handleSpecialLocationQuestItem($character);

        $character = $character->refresh();

        $this->assertSame(2, $character->inventory->slots()->count());
        $this->assertTrue(
            $character->inventory->slots()->where('item_id', $availableQuestItem->id)->exists()
        );
        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function test_handle_special_location_quest_item_covers_return_doesnt_have_when_no_quest_exists_for_item(): void
    {
        $gameMap = $this->createGameMap(['name' => MapNameValue::SURFACE, 'path' => 'path', 'default' => true]);

        $character = (new CharacterFactory())
            ->createBaseCharacter()
            ->givePlayerLocation(16, 16, $gameMap)
            ->updateUser(['auto_disenchant' => false])
            ->getCharacter()
            ->refresh()
            ->load('inventory.slots');

        $location = $this->createLocation();

        $questItemWithNoQuest = $this->createItem([
            'type' => 'quest',
            'drop_location_id' => $location->id,
            'item_suffix_id' => null,
            'item_prefix_id' => null,
        ]);

        DropCheckCalculator::shouldReceive('fetchDifficultItemChance')
            ->once()
            ->withArgs(function (float $chance, int $maxRoll): bool {
                return abs($chance - 0.45) < 0.000001 && $maxRoll === 100;
            })
            ->andReturnFalse();

        $battleDrop = $this->buildBattleDrop($this->createMonster(['game_map_id' => $gameMap->id]))
            ->setSpecialLocation($location)
            ->setLootingChance(1.0);

        $battleDrop->handleSpecialLocationQuestItem($character);

        $this->assertSame(0, $character->refresh()->inventory->slots()->count());
        $this->assertFalse(
            $character->refresh()->inventory->slots()->where('item_id', $questItemWithNoQuest->id)->exists()
        );
    }

    private function buildBattleDrop(Monster $monster, ?RandomItemDropBuilder $randomItemDropBuilder = null): BattleDrop
    {
        $randomItemDropBuilder = $randomItemDropBuilder ?? Mockery::mock(RandomItemDropBuilder::class);
        $disenchantService = Mockery::mock(DisenchantService::class);
        $shopService = Mockery::mock(ShopService::class);

        return $this->buildBattleDropWithServices($monster, $randomItemDropBuilder, $disenchantService, $shopService);
    }

    private function buildBattleDropWithServices(
        Monster $monster,
        RandomItemDropBuilder $randomItemDropBuilder,
        DisenchantService $disenchantService,
        ShopService $shopService,
        ?Location $locationWithEffect = null
    ): BattleDrop {
        $battleDrop = new BattleDrop($randomItemDropBuilder, $disenchantService, $shopService);

        $battleDrop->setMonster($monster)
            ->setSpecialLocation($locationWithEffect)
            ->setLootingChance(0.0)
            ->setGameMapBonus(0.0);

        return $battleDrop;
    }
}
