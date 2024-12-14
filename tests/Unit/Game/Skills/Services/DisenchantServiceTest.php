<?php

namespace Tests\Unit\Game\Skills\Services;

use App\Flare\Models\GameSkill;
use App\Flare\Models\Item;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Character\CharacterInventory\Services\CharacterInventoryService;
use App\Game\Messages\Builders\ServerMessageBuilder;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Types\CraftingMessageTypes;
use App\Game\Skills\Events\UpdateCharacterEnchantingList;
use App\Game\Skills\Events\UpdateSkillEvent;
use App\Game\Skills\Services\DisenchantService;
use App\Game\Skills\Services\SkillCheckService;
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
use Tests\Traits\CreateItemAffix;

class DisenchantServiceTest extends TestCase
{
    use CreateClass, CreateGameSkill, CreateItem, CreateItemAffix, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?DisenchantService $disenchantService;

    private ?Item $itemToDisenchant;

    private ?GameSkill $enchantingSkill;

    private ?GameSkill $disenchantingSkill;

    public function setUp(): void
    {
        parent::setUp();

        $this->enchantingSkill = $this->createGameSkill([
            'name' => 'Enchanting',
            'type' => SkillTypeValue::ENCHANTING,
        ]);

        $this->disenchantingSkill = $this->createGameSkill([
            'name' => 'Disenchanting',
            'type' => SkillTypeValue::DISENCHANTING,
        ]);

        $this->character = (new CharacterFactory)->createBaseCharacter()->assignSkill(
            $this->disenchantingSkill
        )->assignSkill($this->enchantingSkill)->givePlayerLocation();

        $this->disenchantService = resolve(DisenchantService::class);

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

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->disenchantService = null;
        $this->itemToDisenchant = null;
        $this->disenchantingSkill = null;
        $this->enchantingSkill = null;
    }

    public function testDisenchantTheItemAndRemoveTheItemFromTheInventory()
    {
        Event::fake();

        $character = $this->character->inventoryManagement()->giveItem($this->itemToDisenchant)->getCharacter();

        $slot = $character->inventory->slots->first();

        $this->disenchantService->setUp($character)->disenchantWithSkill($slot);

        $character = $character->refresh();

        $this->assertEmpty($character->inventory->slots);

        Event::assertDispatched(UpdateCharacterEnchantingList::class);
    }

    public function testDisenchantTheItemAndRemoveTheItemFromTheInventoryWithQuestItemForGoldDustRush()
    {
        Event::fake();

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
            })
        );

        $disenchantingService = $this->app->make(DisenchantService::class);

        $character = $this->character->inventoryManagement()->giveItem($this->itemToDisenchant)->giveItem(
            $this->createItem([
                'type' => 'quest',
                'effect' => ItemEffectsValue::GOLD_DUST_RUSH,
            ])
        )->getCharacter();

        $slot = $character->inventory->slots->first();

        $disenchantingService->setUp($character)->disenchantWithSkill($slot);

        $character = $character->refresh();

        $this->assertCount(1, $character->inventory->slots->toArray());

        Event::assertDispatched(UpdateCharacterEnchantingList::class);
    }

    public function testDisenchantItemSuccessfully()
    {
        Event::fake();

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
            })
        );

        $character = $this->character->inventoryManagement()->giveItem($this->itemToDisenchant)->getCharacter();

        $slot = $character->inventory->slots->first();

        $disenchantingService = $this->app->make(DisenchantService::class);

        $disenchantingService->setUp($character)->disenchantWithSkill($slot);

        $character = $character->refresh();

        $this->assertEmpty($character->inventory->slots);
        $this->assertGreaterThan(0, $character->gold_dust);

        Event::assertDispatched(UpdateCharacterEnchantingList::class);
    }

    public function testDisenchantItemSuccessfullyAndGetMaxGoldDustFromARush()
    {
        Event::fake();

        $skillCheckServiceMock = Mockery::mock(SkillCheckService::class);
        $skillCheckServiceMock->shouldReceive('getDCCheck')->once()->andReturn(1);
        $skillCheckServiceMock->shouldReceive('characterRoll')->once()->andReturn(100);

        $characterInventoryService = $this->app->make(CharacterInventoryService::class);

        // Create a mock for DisenchantService and allow mocking of protected methods
        $disenchantingService = Mockery::mock(
            DisenchantService::class,
            [$skillCheckServiceMock, $characterInventoryService]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('fetchDCRoll')
            ->once()
            ->andReturn(1000)
            ->getMock();

        $character = $this->character->inventoryManagement()->giveItem($this->itemToDisenchant)->getCharacter();

        $character->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST - 1,
        ]);

        $character = $character->refresh();

        $slot = $character->inventory->slots->first();

        $disenchantingService->setUp($character)->disenchantWithSkill($slot);

        $character = $character->refresh();

        $this->assertEmpty($character->inventory->slots);
        $this->assertEquals(MaxCurrenciesValue::MAX_GOLD_DUST, $character->gold_dust);

        Event::assertDispatched(UpdateCharacterEnchantingList::class);
    }

    public function testDisenchantItemSuccessfullyAndDoNotGetAGoldRushButDoMaxGoldDust()
    {
        Event::fake();

        $skillCheckServiceMock = Mockery::mock(SkillCheckService::class);
        $skillCheckServiceMock->shouldReceive('getDCCheck')->once()->andReturn(1);
        $skillCheckServiceMock->shouldReceive('characterRoll')->once()->andReturn(100);

        $characterInventoryService = $this->app->make(CharacterInventoryService::class);

        // Create a mock for DisenchantService and allow mocking of protected methods
        $disenchantingService = Mockery::mock(
            DisenchantService::class,
            [$skillCheckServiceMock, $characterInventoryService]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('fetchDCRoll')
            ->once()
            ->andReturn(1)
            ->getMock();

        $character = $this->character->inventoryManagement()->giveItem($this->itemToDisenchant)->getCharacter();

        $character->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST - 1,
        ]);

        $character = $character->refresh();

        $slot = $character->inventory->slots->first();

        $disenchantingService->setUp($character)->disenchantWithSkill($slot);

        $character = $character->refresh();

        $this->assertEmpty($character->inventory->slots);
        $this->assertEquals(MaxCurrenciesValue::MAX_GOLD_DUST, $character->gold_dust);

        Event::assertDispatched(UpdateCharacterEnchantingList::class);
    }

    public function testDisenchantFailToItem()
    {
        Event::fake();

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(100);
                $mock->shouldReceive('characterRoll')->once()->andReturn(1);
            })
        );

        $character = $this->character->inventoryManagement()->giveItem($this->itemToDisenchant)->getCharacter();

        $slot = $character->inventory->slots->first();

        $disenchantingService = $this->app->make(DisenchantService::class);

        $disenchantingService->setUp($character)->disenchantWithSkill($slot);

        $character = $character->refresh();

        $this->assertEmpty($character->inventory->slots);
        $this->assertEquals(1, $character->gold_dust);

        Event::assertDispatched(UpdateCharacterEnchantingList::class);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === resolve(ServerMessageBuilder::class)->build(CraftingMessageTypes::FAILED_TO_DISENCHANT);
        });
    }

    public function testGivePlayerGoldDustRush()
    {
        Event::fake();

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
            })
        );

        $disenchantingService = \Mockery::mock(DisenchantService::class)->makePartial();

        $disenchantingService->__construct(resolve(SkillCheckService::class), resolve(CharacterInventoryService::class));

        $disenchantingService->shouldAllowMockingProtectedMethods()
            ->shouldReceive('fetchDCRoll')
            ->andReturn(1000);

        $character = $this->character->inventoryManagement()->giveItem($this->itemToDisenchant)->giveItem($this->createItem([
            'type' => 'quest',
            'effect' => ItemEffectsValue::GOLD_DUST_RUSH,
        ]))->getCharacter();

        $slot = $character->inventory->slots->first();

        $disenchantingService->setUp($character)->disenchantWithSkill($slot);

        $character = $character->refresh();

        $this->assertCount(1, $character->inventory->slots->toArray());
        $this->assertGreaterThan(0, $character->gold_dust);

        Event::assertDispatched(UpdateCharacterEnchantingList::class);

        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function testDoNotGivePlayerGoldDustRushWhenGoldDustCapped()
    {
        Event::fake();

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
            })
        );

        $disenchantingService = resolve(DisenchantService::class);

        $character = $this->character->inventoryManagement()->giveItem($this->itemToDisenchant)->giveItem($this->createItem([
            'type' => 'quest',
            'effect' => ItemEffectsValue::GOLD_DUST_RUSH,
        ]))->getCharacter();

        $character->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
        ]);

        $character = $character->refresh();

        $slot = $character->inventory->slots->first();

        $disenchantingService->setUp($character)->disenchantWithSkill($slot);

        $character = $character->refresh();

        $this->assertCount(1, $character->inventory->slots->toArray());
        $this->assertEquals(MaxCurrenciesValue::MAX_GOLD_DUST, $character->gold_dust);

        Event::assertDispatched(UpdateCharacterEnchantingList::class);
        Event::assertDispatched(UpdateSkillEvent::class);
    }

    public function testCallDisenchantItemAndSucceed()
    {
        Event::fake();

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
            })
        );

        $character = $this->character->getCharacter();

        $disenchantingService = $this->app->make(DisenchantService::class);

        $disenchantingService->setUp($character)->disenchantItemWithSkill();

        $character = $character->refresh();

        $this->assertGreaterThan(0, $character->gold_dust);
    }

    public function testCallDisenchantItemAndSucceedButGetNoGoldDustWhenMaxed()
    {
        Event::fake();

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
            })
        );

        $character = $this->character->getCharacter();

        $character->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
        ]);

        $disenchantingService = $this->app->make(DisenchantService::class);

        $disenchantingService->setUp($character)->disenchantItemWithSkill();

        $character = $character->refresh();

        $this->assertEquals(MaxCurrenciesValue::MAX_GOLD_DUST, $character->gold_dust);
    }

    public function testCallDisenchantItemAndFail()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(100);
                $mock->shouldReceive('characterRoll')->once()->andReturn(1);
            })
        );

        $disenchantingService = $this->app->make(DisenchantService::class);

        $disenchantingService->setUp($character)->disenchantItemWithSkill();

        $character = $character->refresh();

        $this->assertEmpty($character->inventory->slots);
        $this->assertEquals(1, $character->gold_dust);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === resolve(ServerMessageBuilder::class)->build(CraftingMessageTypes::FAILED_TO_DISENCHANT);
        });
    }

    public function testCannotDisentchantItemThatDoesNotExist()
    {
        $character = $this->character->getCharacter();

        $disenchantingService = $this->app->make(DisenchantService::class);

        $item = $this->createItem();

        $result = $disenchantingService->disenchantItem($character, $item);

        $this->assertEquals($item->affix_name . ' Cannot be disenchanted. Not found in inventory.', $result['message']);
        $this->assertEquals(422, $result['status']);
    }

    public function testCannotDisentchantItemThatIsNotEnchanted()
    {
        $item = $this->createItem();

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $disenchantingService = $this->app->make(DisenchantService::class);

        $result = $disenchantingService->disenchantItem($character, $item);

        $this->assertEquals($item->affix_name . ' Cannot be disenchanted. Has no enchantments attached.', $result['message']);
        $this->assertEquals(422, $result['status']);
    }

    public function testCannotDisentchantItemIsAQuestItem()
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

        $disenchantingService = $this->app->make(DisenchantService::class);

        $result = $disenchantingService->disenchantItem($character, $item);

        $this->assertEquals('Quest items cannot be disenchanted.', $result['message']);
        $this->assertEquals(422, $result['status']);
    }

    public function testDisenchantItemAndDoNotReturnResponse()
    {
        $character = $this->character->inventoryManagement()->giveItem($this->itemToDisenchant)->getCharacter();

        $disenchantingService = $this->app->make(DisenchantService::class);

        $result = $disenchantingService->disenchantItem($character, $this->itemToDisenchant, true);
        $this->assertEquals(200, $result['status']);
    }

    public function testDisenchantItemAndReturnResponse()
    {
        $character = $this->character->inventoryManagement()->giveItem($this->itemToDisenchant)->getCharacter();

        $disenchantingService = $this->app->make(DisenchantService::class);

        $result = $disenchantingService->disenchantItem($character, $this->itemToDisenchant);

        $this->assertEquals('Disenchanted item ' . $this->itemToDisenchant->affix_name . ' Check server message tab for Gold Dust output.', $result['message']);
        $this->assertEmpty($result['inventory']['inventory']);
        $this->assertEquals(200, $result['status']);
    }
}
