<?php

namespace Tests\Unit\Game\Skills\Services;

use App\Flare\Events\UpdateSkillEvent;
use App\Flare\Models\Item;
use App\Flare\Values\CharacterClassValue;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Gems\Values\GemTypeValue;
use App\Game\Messages\Builders\ServerMessageBuilder;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Skills\Builders\GemBuilder;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Services\EnchantItemService;
use App\Game\Skills\Services\GemService;
use App\Game\Skills\Services\ItemListCostTransformerService;
use App\Game\Skills\Services\SkillCheckService;
use App\Game\Skills\Services\TrinketCraftingService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\GameSkill;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class TrinketCraftingServiceTest extends TestCase {

    use RefreshDatabase, CreateClass, CreateGameSkill, CreateItem;

    private ?CharacterFactory $character;

    private ?TrinketCraftingService $trinketCraftingService;

    private ?GameSkill $trinketSkill;

    private ?Item $trinket;

    public function setUp(): void
    {
        parent::setUp();

        $this->trinketSkill = $this->createGameSkill([
            'name' => 'Trinketry',
            'type' => SkillTypeValue::CRAFTING,
        ]);

        $this->trinket = $this->createItem([
            'type'                 => 'trinket',
            'can_craft'            => true,
            'gold_dust_cost'       => 1000,
            'copper_coin_cost'     => 1000,
            'skill_level_required' => 1,
            'skill_level_trivial'  => 10
        ]);

        $this->character = (new CharacterFactory())->createBaseCharacter()->assignSkill(
            $this->trinketSkill
        )->givePlayerLocation();

        $this->trinketCraftingService = resolve(TrinketCraftingService::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character               = null;
        $this->trinketSkill            = null;
        $this->trinketCraftingService  = null;
    }

    public function testGetTrinketsToCraft() {
        $character = $this->character->getCharacter();

        $result = $this->trinketCraftingService->fetchItemsToCraft($character);

        $this->assertNotEmpty($result);
    }

    public function testGetTrinketsToCraftAsMerchant() {
        Event::fake();

        $character = (new CharacterFactory())->createBaseCharacter([], $this->createClass([
            'name' => CharacterClassValue::MERCHANT
        ]))->assignSkill($this->trinketSkill)->getCharacter();

        $result = $this->trinketCraftingService->fetchItemsToCraft($character, true);

        $this->assertNotEmpty($result);
        $this->assertNotEquals($this->trinket->gold_dust_cost, $result[0]['gold_dust_cost']);
        $this->assertNotEquals($this->trinket->copper_coin_cost, $result[0]['copper_coin_cost']);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === 'As a Merchant you get 10% discount on creating trinketry items. The discount has been applied to the items list.';
        });
    }

    public function testCannotAffordToCraftTrinket() {
        Event::fake();

        $character = $this->character->getCharacter();

        $this->trinketCraftingService->craft($character, $this->trinket);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === 'You do not have enough of the required currencies to craft this.';
        });
    }

    public function testCannotAffordToCraftTrinketNotEnoughnCopperCoins() {
        Event::fake();

        $character = $this->character->getCharacter();

        $character->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
        ]);

        $this->trinketCraftingService->craft($character->refresh(), $this->trinket);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === 'You do not have enough of the required currencies to craft this.';
        });
    }

    public function testCannotCraftTrinketWhenTooHard() {
        Event::fake();

        $character = $this->character->getCharacter();

        $character->update([
            'gold_dust'    => MaxCurrenciesValue::MAX_GOLD_DUST,
            'copper_coins' => MaxCurrenciesValue::MAX_COPPER,
        ]);

        $character = $character->refresh();

        $this->trinket->update([
            'skill_level_required' => 500,
        ]);

        $this->trinketCraftingService->craft($character, $this->trinket->refresh());

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === resolve(ServerMessageBuilder::class)->build('to_hard_to_craft');
        });
    }

    public function testCraftTrinketWhenTooEasy() {
        Event::fake();

        $character = $this->character->getCharacter();

        $character->update([
            'gold_dust'    => MaxCurrenciesValue::MAX_GOLD_DUST,
            'copper_coins' => MaxCurrenciesValue::MAX_COPPER,
        ]);

        $character = $character->refresh();

        $this->trinket->update([
            'skill_level_trivial' => -10,
        ]);

        $this->trinketCraftingService->craft($character, $this->trinket->refresh());

        $character = $character->refresh();

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === resolve(ServerMessageBuilder::class)->build('to_easy_to_craft');
        });

        $this->assertCount(1, $character->inventory->slots->toArray());
    }

    public function testFailToCraftTheTrinket() {
        Event::fake();

        $character = $this->character->getCharacter();

        $character->update([
            'gold_dust'    => MaxCurrenciesValue::MAX_GOLD_DUST,
            'copper_coins' => MaxCurrenciesValue::MAX_COPPER,
        ]);

        $character = $character->refresh();

        $trinketService = Mockery::mock(TrinketCraftingService::class, function (MockInterface $mock) {
            $mock->makePartial()->shouldAllowMockingProtectedMethods()->shouldReceive('canCraft')->once()->andReturn(false);
        });

        $trinketService->__construct(
            resolve(CraftingService::class),
            resolve(SkillCheckService::class),
            resolve(ItemListCostTransformerService::class)
        );

        $trinketService->craft($character, $this->trinket);

        $character = $character->refresh();

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === 'You failed to craft the trinket. All your efforts fall apart before your eyes!';
        });

        $this->assertLessThan(MaxCurrenciesValue::MAX_GOLD_DUST, $character->gold_dust);
        $this->assertLessThan(MaxCurrenciesValue::MAX_SHARDS, $character->shards);
    }

    public function testCraftTheItem() {
        $character = $this->character->getCharacter();

        $character->update([
            'gold_dust'    => MaxCurrenciesValue::MAX_GOLD_DUST,
            'copper_coins' => MaxCurrenciesValue::MAX_COPPER,
        ]);

        $character = $character->refresh();

        $this->trinketCraftingService->craft($character, $this->trinket);

        $character = $character->refresh();

        $this->assertLessThan(MaxCurrenciesValue::MAX_GOLD_DUST, $character->gold_dust);
        $this->assertLessThan(MaxCurrenciesValue::MAX_SHARDS, $character->shards);
    }

    public function testCraftTheItemAndGiveitem() {
        $character = $this->character->getCharacter();

        $character->update([
            'gold_dust'    => MaxCurrenciesValue::MAX_GOLD_DUST,
            'copper_coins' => MaxCurrenciesValue::MAX_COPPER,
        ]);

        $character = $character->refresh();

        $trinketService = Mockery::mock(TrinketCraftingService::class, function (MockInterface $mock) {
            $mock->makePartial()->shouldAllowMockingProtectedMethods()->shouldReceive('canCraft')->once()->andReturn(true);
        });

        $trinketService->__construct(
            resolve(CraftingService::class),
            resolve(SkillCheckService::class),
            resolve(ItemListCostTransformerService::class)
        );

        $trinketService->craft($character, $this->trinket);

        $character = $character->refresh();

        $this->assertLessThan(MaxCurrenciesValue::MAX_GOLD_DUST, $character->gold_dust);
        $this->assertLessThan(MaxCurrenciesValue::MAX_SHARDS, $character->shards);
        $this->assertCount(1, $character->inventory->slots->toArray());
    }

    public function testCraftTheItemAsMerchant() {
        $character = (new CharacterFactory())->createBaseCharacter([], $this->createClass([
            'name' => CharacterClassValue::MERCHANT
        ]))->assignSkill($this->trinketSkill)->givePlayerLocation()->getCharacter();

        $character->update([
            'gold_dust'    => MaxCurrenciesValue::MAX_GOLD_DUST,
            'copper_coins' => MaxCurrenciesValue::MAX_COPPER,
        ]);

        $character = $character->refresh();

        $trinketService = Mockery::mock(TrinketCraftingService::class, function (MockInterface $mock) {
            $mock->makePartial()->shouldAllowMockingProtectedMethods()->shouldReceive('canCraft')->once()->andReturn(true);
        });

        $trinketService->__construct(
            resolve(CraftingService::class),
            resolve(SkillCheckService::class),
            resolve(ItemListCostTransformerService::class)
        );

        $trinketService->craft($character, $this->trinket);

        $character = $character->refresh();

        $actualGoldDustCost   = floor($this->trinket->gold_dust_cost - $this->trinket->gold_dust_cost * 0.10);
        $actualCopperCoinCost = floor($this->trinket->copper_coin_cost - $this->trinket->copper_coin_cost * 0.10);

        $this->assertEquals(MaxCurrenciesValue::MAX_GOLD_DUST - $actualGoldDustCost, $character->gold_dust);
        $this->assertLessThan(MaxCurrenciesValue::MAX_SHARDS - $actualCopperCoinCost, $character->shards);
        $this->assertCount(1, $character->inventory->slots->toArray());
    }

    public function testCraftTheItemButInventoryIsFull() {
        Event::fake();

        $character = $this->character->getCharacter();

        $character->update([
            'gold_dust'     => MaxCurrenciesValue::MAX_GOLD_DUST,
            'copper_coins'  => MaxCurrenciesValue::MAX_COPPER,
            'inventory_max' => 0,
        ]);

        $character = $character->refresh();

        $trinketService = Mockery::mock(TrinketCraftingService::class, function (MockInterface $mock) {
            $mock->makePartial()->shouldAllowMockingProtectedMethods()->shouldReceive('canCraft')->once()->andReturn(true);
        });

        $trinketService->__construct(
            resolve(CraftingService::class),
            resolve(SkillCheckService::class),
            resolve(ItemListCostTransformerService::class)
        );

        $trinketService->craft($character, $this->trinket);

        $character = $character->refresh();

        $this->assertLessThan(MaxCurrenciesValue::MAX_GOLD_DUST, $character->gold_dust);
        $this->assertLessThan(MaxCurrenciesValue::MAX_SHARDS, $character->shards);
        $this->assertCount(0, $character->inventory->slots->toArray());

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === resolve(ServerMessageBuilder::class)->build('inventory_full');
        });
    }

    public function testFetchCharacterTrinketCraftingXP() {
        $character = $this->character->getCharacter();

        $trinketCraftingXPData = $this->trinketCraftingService->fetchSkillXP($character);

        $trinketSkill = $character->skills()->where('game_skill_id', $this->trinketSkill->id)->first();

        $expected = [
            'current_xp' => 0,
            'next_level_xp' => $trinketSkill->xp_max,
            'skill_name' => $trinketSkill->baseSkill->name,
            'level' => $trinketSkill->level,
        ];

        $this->assertEquals($trinketCraftingXPData, $expected);
    }

}
