<?php

namespace Tests\Unit\Game\Skills\Services;

use App\Flare\Models\GameSkill;
use App\Flare\Models\Item;
use App\Flare\Pagination\Pagination;
use App\Game\Character\CharacterInventory\Exceptions\BatchCraftingDestinationFullException;
use App\Game\Character\Values\CharacterClass;
use App\Game\Core\Currency\Services\CurrencyLimit;
use App\Game\Core\Items\Transformers\CraftingItemPreviewTransformer;
use App\Game\Messages\Builders\ServerMessageBuilder;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Types\CharacterMessageTypes;
use App\Game\Messages\Types\CraftingMessageTypes;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Services\ItemListCostTransformerService;
use App\Game\Skills\Services\SkillCheckService;
use App\Game\Skills\Services\SkillService;
use App\Game\Skills\Services\TrinketCraftingService;
use App\Game\Skills\Transformers\TrinketCraftingItemTransformer;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;

class TrinketCraftingServiceTest extends TestCase
{
    use CreateClass, CreateGameSkill, CreateItem, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?TrinketCraftingService $trinketCraftingService;

    private ?GameSkill $trinketSkill;

    private ?Item $trinket;

    protected function setUp(): void
    {
        parent::setUp();

        $this->trinketSkill = $this->createGameSkill([
            'name' => 'Trinketry',
            'type' => SkillTypeValue::CRAFTING->value,
        ]);

        $this->trinket = $this->createItem([
            'type' => 'trinket',
            'can_craft' => true,
            'gold_dust_cost' => 1000,
            'copper_coin_cost' => 1000,
            'skill_level_required' => 1,
            'skill_level_trivial' => 10,
        ]);

        $this->character = (new CharacterFactory)->createBaseCharacter()->assignSkill(
            $this->trinketSkill
        )->givePlayerLocation();

        $this->trinketCraftingService = resolve(TrinketCraftingService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->trinketSkill = null;
        $this->trinketCraftingService = null;
    }

    public function test_get_trinkets_to_craft()
    {
        $character = $this->character->getCharacter();

        $result = $this->trinketCraftingService->fetchItemsToCraft($character);

        $this->assertNotEmpty($result);
    }

    public function test_get_trinkets_to_craft_as_merchant()
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass([
            'name' => CharacterClass::MERCHANT->value,
        ]))->assignSkill($this->trinketSkill)->getCharacter();

        $result = $this->trinketCraftingService->fetchItemsToCraft($character, true);

        $this->assertNotEmpty($result);
        $this->assertNotEquals($this->trinket->gold_dust_cost, $result[0]['gold_dust_cost']);
        $this->assertNotEquals($this->trinket->copper_coin_cost, $result[0]['copper_coin_cost']);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === 'As a Merchant you get 10% discount on creating trinketry items. The discount has been applied to the items list.';
        });
    }

    public function test_crafting_cost_returns_discounted_gold_dust_and_copper_for_merchant(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass([
            'name' => CharacterClass::MERCHANT->value,
        ]))->assignSkill($this->trinketSkill)->getCharacter();
        $character->update(['gold_dust' => 1000, 'copper_coins' => 1000]);

        $cost = $this->trinketCraftingService->craftingCost($character->refresh(), $this->trinket);

        $this->assertSame(900, $cost['gold_dust']['required']);
        $this->assertSame(900, $cost['copper_coins']['required']);
        $this->assertSame(0, $cost['gold_dust']['missing']);
        $this->assertSame(0, $cost['copper_coins']['missing']);
    }

    public function test_batch_destination_race_rolls_back_both_crafting_currencies(): void
    {
        Event::fake();
        $character = $this->character->getCharacter();
        $character->skills()->where('game_skill_id', $this->trinketSkill->id)->update(['xp' => 40]);
        $character->update(['gold_dust' => 2000, 'copper_coins' => 3000]);
        $trinketService = Mockery::mock(TrinketCraftingService::class, function (MockInterface $mock) {
            $mock->makePartial()->shouldAllowMockingProtectedMethods()->shouldReceive('canCraft')->once()->andReturn(true);
        });
        $trinketService->__construct(
            resolve(CraftingService::class),
            resolve(SkillCheckService::class),
            resolve(ItemListCostTransformerService::class),
            resolve(SkillService::class),
            resolve(Pagination::class),
            resolve(CraftingItemPreviewTransformer::class),
            resolve(TrinketCraftingItemTransformer::class),
        );

        try {
            $trinketService->craftForBatch(
                $character->refresh(),
                $this->trinket,
                true,
                fn (): null => null,
            );
            $this->fail('Expected the destination race to reject the retained trinket.');
        } catch (BatchCraftingDestinationFullException) {
            $this->addToAssertionCount(1);
        }

        $this->assertSame(2000, $character->refresh()->gold_dust);
        $this->assertSame(3000, $character->refresh()->copper_coins);
        $this->assertSame(40, $character->skills()->where('game_skill_id', $this->trinketSkill->id)->first()->xp);
        $this->assertSame(1, $character->skills()->where('game_skill_id', $this->trinketSkill->id)->first()->level);
    }

    public function test_successful_batch_trinket_craft_awards_trinketry_xp_after_destination_acceptance(): void
    {
        Event::fake();
        $character = $this->character->getCharacter();
        $character->update(['gold_dust' => 2000, 'copper_coins' => 2000]);
        $trinketService = Mockery::mock(TrinketCraftingService::class, function (MockInterface $mock) {
            $mock->makePartial()->shouldAllowMockingProtectedMethods()->shouldReceive('canCraft')->once()->andReturn(true);
        });
        $trinketService->__construct(
            resolve(CraftingService::class),
            resolve(SkillCheckService::class),
            resolve(ItemListCostTransformerService::class),
            resolve(SkillService::class),
            resolve(Pagination::class),
            resolve(CraftingItemPreviewTransformer::class),
            resolve(TrinketCraftingItemTransformer::class),
        );

        $result = $trinketService->craftForBatch(
            $character->refresh(),
            $this->trinket,
            true,
            fn (Item $item): array => ['item_id' => $item->id],
        );

        $trinketrySkill = $character->skills()->where('game_skill_id', $this->trinketSkill->id)->first();
        $this->assertSame(25, $trinketrySkill->xp);
        $this->assertTrue($result['success']);
        $this->assertSame($this->trinket->id, $result['item']->id);
        $this->assertSame(['item_id' => $this->trinket->id], $result['destination']);
    }

    public function test_successful_batch_trinket_craft_without_retained_destination_awards_trinketry_xp(): void
    {
        Event::fake();
        $character = $this->character->getCharacter();
        $character->update(['gold_dust' => 2000, 'copper_coins' => 2000]);
        $trinketService = Mockery::mock(TrinketCraftingService::class, function (MockInterface $mock) {
            $mock->makePartial()->shouldAllowMockingProtectedMethods()->shouldReceive('canCraft')->once()->andReturn(true);
        });
        $trinketService->__construct(
            resolve(CraftingService::class),
            resolve(SkillCheckService::class),
            resolve(ItemListCostTransformerService::class),
            resolve(SkillService::class),
            resolve(Pagination::class),
            resolve(CraftingItemPreviewTransformer::class),
            resolve(TrinketCraftingItemTransformer::class),
        );

        $result = $trinketService->craftForBatch($character->refresh(), $this->trinket, true);

        $trinketrySkill = $character->skills()->where('game_skill_id', $this->trinketSkill->id)->first();
        $this->assertSame(25, $trinketrySkill->xp);
        $this->assertTrue($result['success']);
        $this->assertNull($result['destination']);
    }

    public function test_failed_batch_trinket_roll_does_not_award_xp(): void
    {
        Event::fake();
        $character = $this->character->getCharacter();
        $character->skills()->where('game_skill_id', $this->trinketSkill->id)->update(['xp' => 40]);
        $character->update(['gold_dust' => 2000, 'copper_coins' => 2000]);
        $trinketService = Mockery::mock(TrinketCraftingService::class, function (MockInterface $mock) {
            $mock->makePartial()->shouldAllowMockingProtectedMethods()->shouldReceive('canCraft')->once()->andReturn(false);
        });
        $trinketService->__construct(
            resolve(CraftingService::class),
            resolve(SkillCheckService::class),
            resolve(ItemListCostTransformerService::class),
            resolve(SkillService::class),
            resolve(Pagination::class),
            resolve(CraftingItemPreviewTransformer::class),
            resolve(TrinketCraftingItemTransformer::class),
        );

        $result = $trinketService->craftForBatch($character->refresh(), $this->trinket, true);

        $trinketrySkill = $character->skills()->where('game_skill_id', $this->trinketSkill->id)->first();
        $this->assertFalse($result['success']);
        $this->assertSame('failed_roll', $result['reason']);
        $this->assertSame(40, $trinketrySkill->xp);
        $this->assertSame(1, $trinketrySkill->level);
    }

    public function test_too_easy_batch_trinket_does_not_award_xp(): void
    {
        Event::fake();
        $character = $this->character->getCharacter();
        $character->skills()->where('game_skill_id', $this->trinketSkill->id)->update(['level' => 11, 'xp' => 40]);
        $character->update(['gold_dust' => 2000, 'copper_coins' => 2000]);

        $result = $this->trinketCraftingService->craftForBatch($character->refresh(), $this->trinket, true);

        $trinketrySkill = $character->skills()->where('game_skill_id', $this->trinketSkill->id)->first();
        $this->assertTrue($result['success']);
        $this->assertSame(40, $trinketrySkill->xp);
        $this->assertSame(11, $trinketrySkill->level);
    }

    public function test_unaffordable_batch_trinket_does_not_award_xp(): void
    {
        Event::fake();
        $character = $this->character->getCharacter();
        $character->skills()->where('game_skill_id', $this->trinketSkill->id)->update(['xp' => 40]);

        $result = $this->trinketCraftingService->craftForBatch($character->refresh(), $this->trinket, true);

        $trinketrySkill = $character->skills()->where('game_skill_id', $this->trinketSkill->id)->first();
        $this->assertFalse($result['success']);
        $this->assertSame('not_enough_currency', $result['reason']);
        $this->assertSame(40, $trinketrySkill->xp);
        $this->assertSame(1, $trinketrySkill->level);
    }

    public function test_cannot_afford_to_craft_trinket()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        $this->trinketCraftingService->craft($character, $this->trinket);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === 'You do not have enough of the required currencies to craft this.';
        });
    }

    public function test_cannot_afford_to_craft_trinket_not_enoughn_copper_coins()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        $character->update([
            'gold_dust' => CurrencyLimit::MAX_GOLD_DUST,
        ]);

        $this->trinketCraftingService->craft($character->refresh(), $this->trinket);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === 'You do not have enough of the required currencies to craft this.';
        });
    }

    public function test_cannot_craft_trinket_when_too_hard()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        $character->update([
            'gold_dust' => CurrencyLimit::MAX_GOLD_DUST,
            'copper_coins' => CurrencyLimit::MAX_COPPER,
        ]);

        $character = $character->refresh();

        $this->trinket->update([
            'skill_level_required' => 500,
        ]);

        $this->trinketCraftingService->craft($character, $this->trinket->refresh());

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === resolve(ServerMessageBuilder::class)->build(CraftingMessageTypes::TO_HARD_TO_CRAFT);
        });
    }

    public function test_craft_trinket_when_too_easy()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        $character->update([
            'gold_dust' => CurrencyLimit::MAX_GOLD_DUST,
            'copper_coins' => CurrencyLimit::MAX_COPPER,
        ]);

        $character = $character->refresh();

        $this->trinket->update([
            'skill_level_trivial' => -10,
        ]);

        $this->trinketCraftingService->craft($character, $this->trinket->refresh());

        $character = $character->refresh();

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === resolve(ServerMessageBuilder::class)->build(CraftingMessageTypes::TO_EASY_TO_CRAFT);
        });

        $this->assertCount(1, $character->inventory->slots->toArray());
    }

    public function test_fail_to_craft_the_trinket()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        $character->update([
            'gold_dust' => CurrencyLimit::MAX_GOLD_DUST,
            'copper_coins' => CurrencyLimit::MAX_COPPER,
        ]);

        $character = $character->refresh();

        $trinketService = Mockery::mock(TrinketCraftingService::class, function (MockInterface $mock) {
            $mock->makePartial()->shouldAllowMockingProtectedMethods()->shouldReceive('canCraft')->once()->andReturn(false);
        });

        $trinketService->__construct(
            resolve(CraftingService::class),
            resolve(SkillCheckService::class),
            resolve(ItemListCostTransformerService::class),
            resolve(SkillService::class),
            resolve(Pagination::class),
            resolve(CraftingItemPreviewTransformer::class),
            resolve(TrinketCraftingItemTransformer::class),
        );

        $trinketService->craft($character, $this->trinket);

        $character = $character->refresh();

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === 'You failed to craft the trinket. All your efforts fall apart before your eyes!';
        });

        $this->assertLessThan(CurrencyLimit::MAX_GOLD_DUST, $character->gold_dust);
        $this->assertLessThan(CurrencyLimit::MAX_SHARDS, $character->shards);
    }

    public function test_craft_the_item()
    {
        $character = $this->character->getCharacter();

        $character->update([
            'gold_dust' => CurrencyLimit::MAX_GOLD_DUST,
            'copper_coins' => CurrencyLimit::MAX_COPPER,
        ]);

        $character = $character->refresh();

        $this->trinketCraftingService->craft($character, $this->trinket);

        $character = $character->refresh();

        $this->assertLessThan(CurrencyLimit::MAX_GOLD_DUST, $character->gold_dust);
        $this->assertLessThan(CurrencyLimit::MAX_SHARDS, $character->shards);
    }

    public function test_craft_the_item_and_giveitem()
    {
        $character = $this->character->getCharacter();

        $character->update([
            'gold_dust' => CurrencyLimit::MAX_GOLD_DUST,
            'copper_coins' => CurrencyLimit::MAX_COPPER,
        ]);

        $character = $character->refresh();

        $trinketService = Mockery::mock(TrinketCraftingService::class, function (MockInterface $mock) {
            $mock->makePartial()->shouldAllowMockingProtectedMethods()->shouldReceive('canCraft')->once()->andReturn(true);
        });

        $trinketService->__construct(
            resolve(CraftingService::class),
            resolve(SkillCheckService::class),
            resolve(ItemListCostTransformerService::class),
            resolve(SkillService::class),
            resolve(Pagination::class),
            resolve(CraftingItemPreviewTransformer::class),
            resolve(TrinketCraftingItemTransformer::class),
        );

        $trinketService->craft($character, $this->trinket);

        $character = $character->refresh();

        $this->assertLessThan(CurrencyLimit::MAX_GOLD_DUST, $character->gold_dust);
        $this->assertLessThan(CurrencyLimit::MAX_SHARDS, $character->shards);
        $this->assertCount(1, $character->inventory->slots->toArray());
    }

    public function test_craft_the_item_as_merchant()
    {
        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass([
            'name' => CharacterClass::MERCHANT->value,
        ]))->assignSkill($this->trinketSkill)->givePlayerLocation()->getCharacter();

        $character->update([
            'gold_dust' => CurrencyLimit::MAX_GOLD_DUST,
            'copper_coins' => CurrencyLimit::MAX_COPPER,
        ]);

        $character = $character->refresh();

        $trinketService = Mockery::mock(TrinketCraftingService::class, function (MockInterface $mock) {
            $mock->makePartial()->shouldAllowMockingProtectedMethods()->shouldReceive('canCraft')->once()->andReturn(true);
        });

        $trinketService->__construct(
            resolve(CraftingService::class),
            resolve(SkillCheckService::class),
            resolve(ItemListCostTransformerService::class),
            resolve(SkillService::class),
            resolve(Pagination::class),
            resolve(CraftingItemPreviewTransformer::class),
            resolve(TrinketCraftingItemTransformer::class),
        );

        $trinketService->craft($character, $this->trinket);

        $character = $character->refresh();

        $actualGoldDustCost = floor($this->trinket->gold_dust_cost - $this->trinket->gold_dust_cost * 0.10);
        $actualCopperCoinCost = floor($this->trinket->copper_coin_cost - $this->trinket->copper_coin_cost * 0.10);

        $this->assertEquals(CurrencyLimit::MAX_GOLD_DUST - $actualGoldDustCost, $character->gold_dust);
        $this->assertLessThan(CurrencyLimit::MAX_SHARDS - $actualCopperCoinCost, $character->shards);
        $this->assertCount(1, $character->inventory->slots->toArray());
    }

    public function test_craft_the_item_but_inventory_is_full()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        $character->update([
            'gold_dust' => CurrencyLimit::MAX_GOLD_DUST,
            'copper_coins' => CurrencyLimit::MAX_COPPER,
            'inventory_max' => 0,
        ]);

        $character = $character->refresh();

        $trinketService = Mockery::mock(TrinketCraftingService::class, function (MockInterface $mock) {
            $mock->makePartial()->shouldAllowMockingProtectedMethods()->shouldReceive('canCraft')->once()->andReturn(true);
        });

        $trinketService->__construct(
            resolve(CraftingService::class),
            resolve(SkillCheckService::class),
            resolve(ItemListCostTransformerService::class),
            resolve(SkillService::class),
            resolve(Pagination::class),
            resolve(CraftingItemPreviewTransformer::class),
            resolve(TrinketCraftingItemTransformer::class),
        );

        $trinketService->craft($character, $this->trinket);

        $character = $character->refresh();

        $this->assertLessThan(CurrencyLimit::MAX_GOLD_DUST, $character->gold_dust);
        $this->assertLessThan(CurrencyLimit::MAX_SHARDS, $character->shards);
        $this->assertCount(0, $character->inventory->slots->toArray());

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === resolve(ServerMessageBuilder::class)->build(CharacterMessageTypes::INVENTORY_IS_FULL);
        });
    }

    public function test_fetch_character_trinket_crafting_xp()
    {
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

    public function test_find_trinketry_skill_returns_the_characters_trinketry_skill(): void
    {
        $character = $this->character->getCharacter();

        $skill = $this->trinketCraftingService->findTrinketrySkill($character);

        $this->assertNotNull($skill);
        $this->assertSame('Trinketry', $skill->baseSkill->name);
    }

    public function test_find_meaningful_batch_item_returns_highest_requirement_eligible_item(): void
    {
        $character = $this->character->getCharacter();

        $this->trinketSkill->update(['max_level' => 20]);
        $character->skills()->where('game_skill_id', $this->trinketSkill->id)->update(['level' => 10]);

        $strongerTrinket = $this->createItem([
            'type' => 'trinket',
            'can_craft' => true,
            'gold_dust_cost' => 10,
            'copper_coin_cost' => 10,
            'skill_level_required' => 10,
            'skill_level_trivial' => 100,
        ]);

        $item = $this->trinketCraftingService->findMeaningfulBatchItem($character->refresh());

        $this->assertNotNull($item);
        $this->assertSame($strongerTrinket->id, $item->id);
    }

    public function test_find_meaningful_batch_item_returns_null_when_nothing_is_eligible(): void
    {
        $character = $this->character->getCharacter();

        Item::where('type', 'trinket')->update(['skill_level_trivial' => 0]);

        $item = $this->trinketCraftingService->findMeaningfulBatchItem($character);

        $this->assertNull($item);
    }
}
