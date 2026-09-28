<?php

namespace Tests\Unit\Game\Skills\Services;

use App\Flare\Models\GameSkill;
use App\Flare\Models\Item;
use App\Flare\Pagination\Pagination;
use App\Game\Character\CharacterInventory\Jobs\DisenchantMany;
use App\Game\Character\CharacterInventory\Services\CharacterInventoryService;
use App\Game\Character\CharacterInventory\Services\InventorySetService;
use App\Game\Character\CharacterInventory\Transformers\InventorySetOptionTransformer;
use App\Game\Character\CharacterInventory\Transformers\InventoryTransformer;
use App\Game\Character\CharacterSheet\Events\UpdateCharacterBaseDetailsEvent;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Core\Currency\Services\CurrencyLimit;
use App\Game\Core\Events\UpdateCharacterInventoryCountEvent;
use App\Game\Core\Items\Enricher\ItemEnricherFactory;
use App\Game\Core\Items\Transformers\Api\UsableItemTransformer as ApiUsableItemTransformer;
use App\Game\Core\Items\Transformers\EquippableItemTransformer;
use App\Game\Core\Items\Transformers\QuestItemTransformer;
use App\Game\Core\Items\Values\ItemEffectType;
use App\Game\Events\Services\GlobalEventGoalEligibilityService;
use App\Game\Messages\Builders\ServerMessageBuilder;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Types\CraftingMessageTypes;
use App\Game\Skills\Events\UpdateCharacterEnchantingList;
use App\Game\Skills\Events\UpdateSkillEvent;
use App\Game\Skills\Services\DisenchantService;
use App\Game\Skills\Services\EnchantingAffixService;
use App\Game\Skills\Services\MassDisenchantService;
use App\Game\Skills\Services\SkillBonusContextService;
use App\Game\Skills\Services\SkillBonusService;
use App\Game\Skills\Services\SkillCheckService;
use App\Game\Skills\Services\UpdateCharacterSkillsService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use League\Fractal\Manager;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterCacheDataFactory;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class DisenchantServiceTest extends TestCase
{
    use CreateClass, CreateGameSkill, CreateItem, CreateItemAffix, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?Item $itemToDisenchant;

    private ?GameSkill $enchantingSkill;

    private ?GameSkill $disenchantingSkill;

    private ?EnchantingAffixService $enchantingAffixService;

    private ?RandomNumberGenerator $randomNumberGenerator;

    private ?SkillCheckService $skillCheckService;

    private ?ChanceCalculator $chanceCalculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enchantingSkill = $this->createGameSkill([
            'name' => 'Enchanting',
            'type' => SkillTypeValue::ENCHANTING->value,
        ]);

        $this->disenchantingSkill = $this->createGameSkill([
            'name' => 'Disenchanting',
            'type' => SkillTypeValue::DISENCHANTING->value,
        ]);

        $this->character = (new CharacterFactory)->createBaseCharacter()->assignSkill(
            $this->disenchantingSkill
        )->assignSkill($this->enchantingSkill)->givePlayerLocation();

        $characterStatBuilder = (new CharacterCacheDataFactory)->buildCharacterStatBuilder();

        $this->enchantingAffixService = new EnchantingAffixService(
            $characterStatBuilder,
            new GlobalEventGoalEligibilityService(),
        );

        $this->randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);
        $this->randomNumberGenerator->shouldReceive('numberBetween')->andReturnUsing(
            fn (int $minimum, int $maximum): int => intdiv($minimum + $maximum, 2)
        );

        $this->skillCheckService = new SkillCheckService($this->randomNumberGenerator, new SkillBonusService(new SkillBonusContextService));
        $this->chanceCalculator = new ChanceCalculator($this->randomNumberGenerator);

        $this->itemToDisenchant = $this->createItem([
            'cost' => 1000,
            'skill_level_required' => 1,
            'skill_level_trivial' => 100,
            'crafting_type' => 'weapon',
            'type' => 'weapon',
            'can_craft' => true,
            'default_position' => 'hammer',
            'item_prefix_id' => $this->createItemAffix([
                'type' => 'prefix',
            ]),
            'item_suffix_id' => $this->createItemAffix([
                'type' => 'prefix',
            ]),
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->itemToDisenchant = null;
        $this->disenchantingSkill = null;
        $this->enchantingSkill = null;
        $this->enchantingAffixService = null;
        $this->randomNumberGenerator = null;
        $this->skillCheckService = null;
        $this->chanceCalculator = null;
    }

    public function test_disenchant_the_item_and_remove_the_item_from_the_inventory(): void
    {
        Event::fake();

        $character = $this->character->inventoryManagement()->giveItem($this->itemToDisenchant)->getCharacter();

        $slot = $character->inventory->slots->first();

        new DisenchantService(
            $this->skillCheckService,
            $this->randomNumberGenerator,
            $this->chanceCalculator,
            $this->enchantingAffixService,
        )->setUp($character)->disenchantWithSkill($slot);

        $character = $character->refresh();

        $this->assertEmpty($character->inventory->slots);

        Event::assertDispatched(UpdateCharacterEnchantingList::class);
    }

    public function test_disenchant_the_item_and_remove_the_item_from_the_inventory_with_quest_item_for_gold_dust_rush(): void
    {
        Event::fake();

        $character = $this->character->inventoryManagement()->giveItem($this->itemToDisenchant)->giveItem(
            $this->createItem([
                'type' => 'quest',
                'effect' => ItemEffectType::GOLD_DUST_RUSH->value,
            ])
        )->getCharacter();

        $slot = $character->inventory->slots->first();

        new DisenchantService(
            $this->skillCheckService,
            $this->randomNumberGenerator,
            $this->chanceCalculator,
            $this->enchantingAffixService,
        )->setUp($character)->disenchantWithSkill($slot);

        $character = $character->refresh();

        $this->assertCount(1, $character->inventory->slots->toArray());

        Event::assertDispatched(UpdateCharacterEnchantingList::class);
    }

    public function test_disenchant_item_successfully(): void
    {
        Event::fake();

        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class, function (MockInterface $mock): void {
            $mock->shouldReceive('numberBetween')->with(1, 400)->andReturn(400, 1);
            $mock->shouldReceive('numberBetween')->with(2, 1150)->andReturn(1000);
        });

        $character = $this->character->inventoryManagement()->giveItem($this->itemToDisenchant)->getCharacter();

        $slot = $character->inventory->slots->first();

        new DisenchantService(
            new SkillCheckService($randomNumberGenerator, new SkillBonusService(new SkillBonusContextService)),
            $randomNumberGenerator,
            new ChanceCalculator($randomNumberGenerator),
            $this->enchantingAffixService,
        )->setUp($character)->disenchantWithSkill($slot);

        $character = $character->refresh();

        $this->assertEmpty($character->inventory->slots);
        $this->assertSame(1000, $character->gold_dust);

        Event::assertDispatched(UpdateCharacterEnchantingList::class);
    }

    public function test_gold_dust_rush_requires_quest_effect(): void
    {
        Event::fake();

        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class, function (MockInterface $mock): void {
            $mock->shouldReceive('numberBetween')->with(1, 400)->andReturn(400, 1);
            $mock->shouldReceive('numberBetween')->with(2, 1150)->andReturn(1000);
        });

        $character = $this->character->inventoryManagement()->giveItem($this->itemToDisenchant)->getCharacter();

        $slot = $character->inventory->slots->first();

        new DisenchantService(
            new SkillCheckService($randomNumberGenerator, new SkillBonusService(new SkillBonusContextService)),
            $randomNumberGenerator,
            new ChanceCalculator($randomNumberGenerator),
            $this->enchantingAffixService,
        )->setUp($character)->disenchantWithSkill($slot);

        $character = $character->refresh();

        $this->assertSame(1000, $character->gold_dust);
    }

    public function test_gold_dust_rush_does_not_proc_on_a_failed_one_in_hundred_roll(): void
    {
        Event::fake();

        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class, function (MockInterface $mock): void {
            $mock->shouldReceive('numberBetween')->with(1, 400)->andReturn(400, 1);
            $mock->shouldReceive('numberBetween')->with(2, 1150)->andReturn(1000);
            $mock->shouldReceive('numberBetween')->with(1, 100)->andReturn(2);
        });

        $character = $this->character->inventoryManagement()->giveItem($this->itemToDisenchant)->giveItem($this->createItem([
            'type' => 'quest',
            'effect' => ItemEffectType::GOLD_DUST_RUSH->value,
        ]))->getCharacter();

        $slot = $character->inventory->slots->first();

        new DisenchantService(
            new SkillCheckService($randomNumberGenerator, new SkillBonusService(new SkillBonusContextService)),
            $randomNumberGenerator,
            new ChanceCalculator($randomNumberGenerator),
            $this->enchantingAffixService,
        )->setUp($character)->disenchantWithSkill($slot);

        $character = $character->refresh();

        $this->assertSame(1000, $character->gold_dust);
    }

    public function test_successful_one_in_hundred_roll_awards_gold_dust_rush_percentage(): void
    {
        Event::fake();

        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class, function (MockInterface $mock): void {
            $mock->shouldReceive('numberBetween')->with(1, 400)->andReturn(400, 1);
            $mock->shouldReceive('numberBetween')->with(2, 1150)->andReturn(1000);
            $mock->shouldReceive('numberBetween')->with(1, 100)->andReturn(1);
        });

        $character = $this->character->inventoryManagement()->giveItem($this->itemToDisenchant)->giveItem($this->createItem([
            'type' => 'quest',
            'effect' => ItemEffectType::GOLD_DUST_RUSH->value,
        ]))->getCharacter();

        $slot = $character->inventory->slots->first();

        new DisenchantService(
            new SkillCheckService($randomNumberGenerator, new SkillBonusService(new SkillBonusContextService)),
            $randomNumberGenerator,
            new ChanceCalculator($randomNumberGenerator),
            $this->enchantingAffixService,
        )->setUp($character)->disenchantWithSkill($slot);

        $character = $character->refresh();

        $this->assertSame(1050, $character->gold_dust);

        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function test_disenchant_fail_to_item(): void
    {
        Event::fake();

        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class, function (MockInterface $mock): void {
            $mock->shouldReceive('numberBetween')->with(1, 400)->andReturn(1, 400);
        });

        $character = $this->character->inventoryManagement()->giveItem($this->itemToDisenchant)->getCharacter();

        $slot = $character->inventory->slots->first();

        new DisenchantService(
            new SkillCheckService($randomNumberGenerator, new SkillBonusService(new SkillBonusContextService)),
            $randomNumberGenerator,
            new ChanceCalculator($randomNumberGenerator),
            $this->enchantingAffixService,
        )->setUp($character)->disenchantWithSkill($slot);

        $character = $character->refresh();

        $this->assertEmpty($character->inventory->slots);
        $this->assertEquals(1, $character->gold_dust);

        Event::assertDispatched(UpdateCharacterEnchantingList::class);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === new ServerMessageBuilder()->build(CraftingMessageTypes::FAILED_TO_DISENCHANT);
        });
    }

    public function test_failed_disenchant_does_not_trigger_gold_dust_rush_even_with_quest_item(): void
    {
        Event::fake();

        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class, function (MockInterface $mock): void {
            $mock->shouldReceive('numberBetween')->with(1, 400)->andReturn(1, 400);
            $mock->shouldReceive('numberBetween')->with(1, 100)->never();
        });

        $character = $this->character->inventoryManagement()->giveItem($this->itemToDisenchant)->giveItem($this->createItem([
            'type' => 'quest',
            'effect' => ItemEffectType::GOLD_DUST_RUSH->value,
        ]))->getCharacter();

        $slot = $character->inventory->slots->first();

        new DisenchantService(
            new SkillCheckService($randomNumberGenerator, new SkillBonusService(new SkillBonusContextService)),
            $randomNumberGenerator,
            new ChanceCalculator($randomNumberGenerator),
            $this->enchantingAffixService,
        )->setUp($character)->disenchantWithSkill($slot);

        $character = $character->refresh();

        $this->assertEquals(1, $character->gold_dust);
    }

    public function test_disenchant_item_successfully_and_get_max_gold_dust_from_a_rush(): void
    {
        Event::fake();

        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class, function (MockInterface $mock): void {
            $mock->shouldReceive('numberBetween')->with(1, 400)->andReturn(400, 1);
            $mock->shouldReceive('numberBetween')->with(2, 1150)->andReturn(1000);
            $mock->shouldReceive('numberBetween')->with(1, 100)->andReturn(1);
        });

        $character = $this->character->inventoryManagement()->giveItem($this->itemToDisenchant)->giveItem($this->createItem([
            'type' => 'quest',
            'effect' => ItemEffectType::GOLD_DUST_RUSH->value,
        ]))->getCharacter();

        $character->update([
            'gold_dust' => CurrencyLimit::MAX_GOLD_DUST - 1,
        ]);

        $character = $character->refresh();

        $slot = $character->inventory->slots->first();

        new DisenchantService(
            new SkillCheckService($randomNumberGenerator, new SkillBonusService(new SkillBonusContextService)),
            $randomNumberGenerator,
            new ChanceCalculator($randomNumberGenerator),
            $this->enchantingAffixService,
        )->setUp($character)->disenchantWithSkill($slot);

        $character = $character->refresh();

        $this->assertCount(1, $character->inventory->slots);
        $this->assertEquals(CurrencyLimit::MAX_GOLD_DUST, $character->gold_dust);

        Event::assertDispatched(UpdateCharacterEnchantingList::class);
    }

    public function test_disenchant_item_successfully_and_do_not_get_a_gold_rush_but_do_max_gold_dust(): void
    {
        Event::fake();

        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class, function (MockInterface $mock): void {
            $mock->shouldReceive('numberBetween')->with(1, 400)->andReturn(400, 1);
            $mock->shouldReceive('numberBetween')->with(2, 1150)->andReturn(1000);
            $mock->shouldReceive('numberBetween')->with(1, 100)->andReturn(2);
        });

        $character = $this->character->inventoryManagement()->giveItem($this->itemToDisenchant)->giveItem($this->createItem([
            'type' => 'quest',
            'effect' => ItemEffectType::GOLD_DUST_RUSH->value,
        ]))->getCharacter();

        $character->update([
            'gold_dust' => CurrencyLimit::MAX_GOLD_DUST - 1,
        ]);

        $character = $character->refresh();

        $slot = $character->inventory->slots->first();

        new DisenchantService(
            new SkillCheckService($randomNumberGenerator, new SkillBonusService(new SkillBonusContextService)),
            $randomNumberGenerator,
            new ChanceCalculator($randomNumberGenerator),
            $this->enchantingAffixService,
        )->setUp($character)->disenchantWithSkill($slot);

        $character = $character->refresh();

        $this->assertCount(1, $character->inventory->slots);
        $this->assertEquals(CurrencyLimit::MAX_GOLD_DUST, $character->gold_dust);
    }

    public function test_do_not_give_player_gold_dust_rush_when_gold_dust_already_capped(): void
    {
        Event::fake();

        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class, function (MockInterface $mock): void {
            $mock->shouldReceive('numberBetween')->with(1, 400)->andReturn(400, 1);
        });

        $character = $this->character->inventoryManagement()->giveItem($this->itemToDisenchant)->giveItem($this->createItem([
            'type' => 'quest',
            'effect' => ItemEffectType::GOLD_DUST_RUSH->value,
        ]))->getCharacter();

        $character->update([
            'gold_dust' => CurrencyLimit::MAX_GOLD_DUST,
        ]);

        $character = $character->refresh();

        $slot = $character->inventory->slots->first();

        new DisenchantService(
            new SkillCheckService($randomNumberGenerator, new SkillBonusService(new SkillBonusContextService)),
            $randomNumberGenerator,
            new ChanceCalculator($randomNumberGenerator),
            $this->enchantingAffixService,
        )->setUp($character)->disenchantWithSkill($slot);

        $character = $character->refresh();

        $this->assertCount(1, $character->inventory->slots->toArray());
        $this->assertEquals(CurrencyLimit::MAX_GOLD_DUST, $character->gold_dust);

        Event::assertDispatched(UpdateCharacterEnchantingList::class);
        Event::assertDispatched(UpdateSkillEvent::class);
    }

    public function test_multi_disenchant_rolls_gold_dust_rush_once_for_the_batch(): void
    {
        Event::fake();

        $this->instance(
            RandomNumberGenerator::class,
            Mockery::mock(RandomNumberGenerator::class, function (MockInterface $mock): void {
                $mock->shouldReceive('numberBetween')->with(1, 400)->andReturn(400, 1);
                $mock->shouldReceive('numberBetween')->with(2, 1150)->andReturn(1000);
                $mock->shouldReceive('numberBetween')->with(1, 100)->once()->andReturn(1);
            })
        );

        $character = $this->character->inventoryManagement()->giveItemMultipleTimes($this->itemToDisenchant, 2)->giveItem($this->createItem([
            'type' => 'quest',
            'effect' => ItemEffectType::GOLD_DUST_RUSH->value,
        ]))->getCharacter();

        DisenchantMany::dispatch($character, [$this->itemToDisenchant->id, $this->itemToDisenchant->id]);

        $character = $character->refresh();

        $this->assertEquals(2100, $character->gold_dust);
    }

    public function test_failed_selected_disenchant_does_not_trigger_gold_dust_rush(): void
    {
        Event::fake();

        $this->instance(
            RandomNumberGenerator::class,
            Mockery::mock(RandomNumberGenerator::class, function (MockInterface $mock): void {
                $mock->shouldReceive('numberBetween')->with(1, 400)->andReturn(1, 400);
                $mock->shouldReceive('numberBetween')->with(1, 100)->never();
            })
        );

        $character = $this->character->inventoryManagement()->giveItem($this->itemToDisenchant)->giveItem($this->createItem([
            'type' => 'quest',
            'effect' => ItemEffectType::GOLD_DUST_RUSH->value,
        ]))->getCharacter();

        DisenchantMany::dispatch($character, [$this->itemToDisenchant->id]);

        $character = $character->refresh();

        $this->assertEquals(1, $character->gold_dust);
    }

    public function test_failed_selected_disenchant_does_not_count_failed_gold_dust_toward_rush_bonus(): void
    {
        Event::fake();

        $this->instance(
            RandomNumberGenerator::class,
            Mockery::mock(RandomNumberGenerator::class, function (MockInterface $mock): void {
                $mock->shouldReceive('numberBetween')->with(1, 400)->andReturn(1, 400);
                $mock->shouldReceive('numberBetween')->with(1, 100)->never();
            })
        );

        $character = $this->character->inventoryManagement()->giveItemMultipleTimes($this->itemToDisenchant, 20)->giveItem($this->createItem([
            'type' => 'quest',
            'effect' => ItemEffectType::GOLD_DUST_RUSH->value,
        ]))->getCharacter();

        DisenchantMany::dispatch($character, array_fill(0, 20, $this->itemToDisenchant->id));

        $character = $character->refresh();

        $this->assertEquals(20, $character->gold_dust);
    }

    public function test_call_disenchant_item_and_succeed(): void
    {
        Event::fake();

        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class, function (MockInterface $mock): void {
            $mock->shouldReceive('numberBetween')->with(1, 400)->andReturn(400, 1);
            $mock->shouldReceive('numberBetween')->with(2, 1150)->andReturn(1000);
        });

        $character = $this->character->getCharacter();

        new DisenchantService(
            new SkillCheckService($randomNumberGenerator, new SkillBonusService(new SkillBonusContextService)),
            $randomNumberGenerator,
            new ChanceCalculator($randomNumberGenerator),
            $this->enchantingAffixService,
        )->setUp($character)->disenchantItemWithSkill();

        $character = $character->refresh();

        $this->assertSame(1000, $character->gold_dust);

        Event::assertDispatched(UpdateSkillEvent::class);
        Event::assertNotDispatched(UpdateCharacterInventoryCountEvent::class);
        Event::assertNotDispatched(UpdateCharacterBaseDetailsEvent::class);
    }

    public function test_call_disenchant_item_and_succeed_but_get_no_gold_dust_when_maxed(): void
    {
        Event::fake();

        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class, function (MockInterface $mock): void {
            $mock->shouldReceive('numberBetween')->with(1, 400)->andReturn(400, 1);
        });

        $character = $this->character->getCharacter();

        $character->update([
            'gold_dust' => CurrencyLimit::MAX_GOLD_DUST,
        ]);

        new DisenchantService(
            new SkillCheckService($randomNumberGenerator, new SkillBonusService(new SkillBonusContextService)),
            $randomNumberGenerator,
            new ChanceCalculator($randomNumberGenerator),
            $this->enchantingAffixService,
        )->setUp($character)->disenchantItemWithSkill();

        $character = $character->refresh();

        $this->assertEquals(CurrencyLimit::MAX_GOLD_DUST, $character->gold_dust);

        Event::assertNotDispatched(UpdateCharacterInventoryCountEvent::class);
    }

    public function test_call_disenchant_item_and_fail(): void
    {
        Event::fake();

        $character = $this->character->getCharacter();

        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class, function (MockInterface $mock): void {
            $mock->shouldReceive('numberBetween')->with(1, 400)->andReturn(1, 400);
        });

        new DisenchantService(
            new SkillCheckService($randomNumberGenerator, new SkillBonusService(new SkillBonusContextService)),
            $randomNumberGenerator,
            new ChanceCalculator($randomNumberGenerator),
            $this->enchantingAffixService,
        )->setUp($character)->disenchantItemWithSkill();

        $character = $character->refresh();

        $this->assertEmpty($character->inventory->slots);
        $this->assertEquals(1, $character->gold_dust);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === new ServerMessageBuilder()->build(CraftingMessageTypes::FAILED_TO_DISENCHANT);
        });
        Event::assertNotDispatched(UpdateCharacterInventoryCountEvent::class);
        Event::assertNotDispatched(UpdateCharacterBaseDetailsEvent::class);
    }

    public function test_cannot_disentchant_item_that_does_not_exist(): void
    {
        $character = $this->character->getCharacter();

        $item = $this->createItem();

        $characterInventoryService = new CharacterInventoryService(
            Mockery::mock(ItemEnricherFactory::class),
            Mockery::mock(EquippableItemTransformer::class),
            Mockery::mock(QuestItemTransformer::class),
            Mockery::mock(ApiUsableItemTransformer::class),
            Mockery::mock(InventoryTransformer::class),
            Mockery::mock(InventorySetService::class),
            Mockery::mock(MassDisenchantService::class),
            Mockery::mock(UpdateCharacterSkillsService::class),
            Mockery::mock(DisenchantService::class),
            new Pagination(new Manager()),
            Mockery::mock(Manager::class),
            Mockery::mock(InventorySetOptionTransformer::class),
        );

        $result = $characterInventoryService->setCharacter($character)->disenchantItem($item->id);

        $this->assertEquals('No item found to disenchant.', $result['message']);
        $this->assertEquals(422, $result['status']);
    }

    public function test_cannot_disentchant_item_that_is_not_enchanted(): void
    {
        Event::fake();

        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class, function (MockInterface $mock): void {
            $mock->shouldReceive('numberBetween')->with(1, 400)->andReturn(400, 1);
            $mock->shouldReceive('numberBetween')->with(2, 1150)->andReturn(1000);
        });

        $item = $this->createItem();

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $slot = $character->inventory->slots->first();

        $result = new DisenchantService(
            new SkillCheckService($randomNumberGenerator, new SkillBonusService(new SkillBonusContextService)),
            $randomNumberGenerator,
            new ChanceCalculator($randomNumberGenerator),
            $this->enchantingAffixService,
        )->setUp($character)->disenchantItem($slot);

        $this->assertEquals('Disenchanted item '.$item->affix_name.' Check server message tab for Gold Dust output.', $result['message']);
        $this->assertEquals(200, $result['status']);

        $character = $character->refresh();

        $this->assertEmpty($character->inventory->slots);

        Event::assertDispatched(UpdateCharacterEnchantingList::class);
    }

    public function test_cannot_disentchant_item_is_a_quest_item(): void
    {
        $item = $this->createItem([
            'item_prefix_id' => $this->createItemAffix([
                'type' => 'prefix',
            ]),
            'item_suffix_id' => $this->createItemAffix([
                'type' => 'prefix',
            ]),
            'type' => 'quest',
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $characterInventoryService = new CharacterInventoryService(
            Mockery::mock(ItemEnricherFactory::class),
            Mockery::mock(EquippableItemTransformer::class),
            Mockery::mock(QuestItemTransformer::class),
            Mockery::mock(ApiUsableItemTransformer::class),
            Mockery::mock(InventoryTransformer::class),
            Mockery::mock(InventorySetService::class),
            Mockery::mock(MassDisenchantService::class),
            Mockery::mock(UpdateCharacterSkillsService::class),
            Mockery::mock(DisenchantService::class),
            new Pagination(new Manager()),
            Mockery::mock(Manager::class),
            Mockery::mock(InventorySetOptionTransformer::class),
        );

        $result = $characterInventoryService->setCharacter($character)->disenchantItem($item->id);

        $this->assertEquals('No item found to disenchant.', $result['message']);
        $this->assertEquals(422, $result['status']);
    }

    public function test_disenchant_item_and_do_not_return_response(): void
    {
        $character = $this->character->inventoryManagement()->giveItem($this->itemToDisenchant)->getCharacter();

        $slot = $character->inventory->slots->first();

        $result = new DisenchantService(
            $this->skillCheckService,
            $this->randomNumberGenerator,
            $this->chanceCalculator,
            $this->enchantingAffixService,
        )->setUp($character)->disenchantItem($slot, true);

        $this->assertEquals(200, $result['status']);
    }

    public function test_disenchant_item_and_return_response(): void
    {
        $character = $this->character->inventoryManagement()->giveItem($this->itemToDisenchant)->getCharacter();

        $slot = $character->inventory->slots->first();

        $result = new DisenchantService(
            $this->skillCheckService,
            $this->randomNumberGenerator,
            $this->chanceCalculator,
            $this->enchantingAffixService,
        )->setUp($character)->disenchantItem($slot);

        $this->assertEquals('Disenchanted item '.$this->itemToDisenchant->affix_name.' Check server message tab for Gold Dust output.', $result['message']);
        $this->assertEquals(200, $result['status']);
    }

    public function test_disenchant_batch_crafted_item_rewards_gold_dust_on_success(): void
    {
        Event::fake();

        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class, function (MockInterface $mock): void {
            $mock->shouldReceive('numberBetween')->with(1, 400)->andReturn(400);
            $mock->shouldReceive('numberBetween')->with(2, 1150)->andReturn(1000);
        });

        $character = $this->character->getCharacter();
        $goldDustBefore = $character->gold_dust;

        new DisenchantService(
            new SkillCheckService($randomNumberGenerator, new SkillBonusService(new SkillBonusContextService)),
            $randomNumberGenerator,
            new ChanceCalculator($randomNumberGenerator),
            $this->enchantingAffixService,
        )->setUp($character)->disenchantBatchCraftedItem();

        $character = $character->refresh();

        $this->assertGreaterThan($goldDustBefore, $character->gold_dust);

        Event::assertDispatched(UpdateSkillEvent::class);
    }

    public function test_disenchant_batch_crafted_item_grants_minimal_gold_dust_on_failure(): void
    {
        Event::fake();

        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class, function (MockInterface $mock): void {
            $mock->shouldReceive('numberBetween')->with(1, 400)->andReturn(1, 400);
        });

        $character = $this->character->getCharacter();
        $goldDustBefore = $character->gold_dust;

        new DisenchantService(
            new SkillCheckService($randomNumberGenerator, new SkillBonusService(new SkillBonusContextService)),
            $randomNumberGenerator,
            new ChanceCalculator($randomNumberGenerator),
            $this->enchantingAffixService,
        )->setUp($character)->disenchantBatchCraftedItem();

        $character = $character->refresh();

        $this->assertEquals($goldDustBefore + 1, $character->gold_dust);

        Event::assertNotDispatched(UpdateSkillEvent::class);
    }

    public function test_disenchant_dispatches_enchanting_list_update_with_the_real_remaining_inventory(): void
    {
        Event::fake();

        $remainingItem = $this->createItem([
            'cost' => 500,
            'skill_level_required' => 1,
            'skill_level_trivial' => 100,
            'crafting_type' => 'weapon',
            'type' => 'weapon',
            'can_craft' => true,
            'default_position' => 'hammer',
        ]);

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($this->itemToDisenchant)
            ->giveItem($remainingItem)
            ->getCharacter();

        $slot = $character->inventory->slots->firstWhere('item_id', $this->itemToDisenchant->id);

        new DisenchantService(
            $this->skillCheckService,
            $this->randomNumberGenerator,
            $this->chanceCalculator,
            $this->enchantingAffixService,
        )->setUp($character)->disenchantWithSkill($slot);

        Event::assertDispatched(function (UpdateCharacterEnchantingList $event) use ($remainingItem) {
            return $event->inventory->count() === 1
                && $event->inventory->first()->item_id === $remainingItem->id;
        });
    }
}
