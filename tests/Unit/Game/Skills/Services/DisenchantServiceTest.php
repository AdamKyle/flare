<?php

namespace Tests\Unit\Game\Skills\Services;

use App\Flare\Values\ArmourTypes;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\ItemUsabilityType;
use App\Flare\Values\MaxCurrenciesValue;
use App\Flare\Values\SpellTypes;
use App\Flare\Values\WeaponTypes;
use App\Game\Messages\Builders\ServerMessageBuilder;
use App\Game\Skills\Events\UpdateCharacterEnchantingList;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Services\DisenchantService;
use App\Game\Skills\Services\SkillCheckService;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\Item;
use App\Flare\Values\CharacterClassValue;
use App\Flare\Models\GameSkill;
use App\Game\Skills\Values\SkillTypeValue;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Skills\Services\AlchemyService;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class DisenchantServiceTest extends TestCase {

    use RefreshDatabase, CreateItem, CreateClass, CreateGameSkill, CreateItemAffix;

    private ?CharacterFactory $character;

    private ?DisenchantService $disenchantService;

    private ?Item $itemToDisenchant;

    private ?GameSkill $enchantingSkill;

    private ?GameSkill $disenchantingKill;

    public function setUp(): void {
        parent::setUp();

        $this->enchantingSkill = $this->createGameSkill([
            'name' => 'Enchanting',
            'type' => SkillTypeValue::ENCHANTING,
        ]);

        $this->disenchantingKill = $this->createGameSkill([
            'name' => 'Disenchanting',
            'type' => SkillTypeValue::DISENCHANTING,
        ]);

        $this->character = (new CharacterFactory())->createBaseCharacter()->assignSkill(
            $this->disenchantingKill
        )->assignSkill($this->enchantingSkill)->givePlayerLocation();

        $this->disenchantService = resolve(DisenchantService::class);

        $this->itemToDisenchant = $this->createItem([
            'cost'                 => 1000,
            'skill_level_required' => 1,
            'skill_level_trivial'  => 100,
            'crafting_type'        => 'weapon',
            'type'                 => 'weapon',
            'can_craft'            => true,
            'default_position'     => 'hammer',
            'item_prefix_id'       => $this->createItemAffix([
                'type' => 'prefix',
            ]),
            'item_suffix_id'       => $this->createItemAffix([
                'type' => 'prefix',
            ])
        ]);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character         = null;
        $this->disenchantService = null;
        $this->itemToDisenchant  = null;
        $this->disenchantingKill = null;
        $this->enchantingSkill   = null;
    }

    public function testDisenchantTheItemAndRemoveTheItemFromTheInventory() {
        Event::fake();

        $character = $this->character->inventoryManagement()->giveItem($this->itemToDisenchant)->getCharacter();

        $slot = $character->inventory->slots->first();

        $this->disenchantService->setUp($character)->disenchantWithSkill($slot);

        $character = $character->refresh();

        $this->assertEmpty($character->inventory->slots);

        Event::assertDispatched(UpdateCharacterEnchantingList::class);
    }

    public function testDisenchantTheItemAndRemoveTheItemFromTheInventoryWithQuestItemForGoldDustRush() {
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
                'type'   => 'quest',
                'effect' => ItemEffectsValue::GOLD_DUST_RUSH,
            ])
        )->getCharacter();

        $slot = $character->inventory->slots->first();

        $disenchantingService->setUp($character)->disenchantWithSkill($slot);

        $character = $character->refresh();

        $this->assertCount(1, $character->inventory->slots->toArray());

        Event::assertDispatched(UpdateCharacterEnchantingList::class);
    }

    public function testDisenchantItemSuccessfully() {
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

        Event::assertDispatched(function (ServerMessageEvent $event) use($character) {
            return $event->message === resolve(ServerMessageBuilder::class)->buildWithAdditionalInformation('disenchanted', number_format($character->gold_dust));
        });
    }

    public function testDisenchantFailToItem() {
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

        Event::assertDispatched(function (ServerMessageEvent $event) use($character) {
            return $event->message === resolve(ServerMessageBuilder::class)->build('failed_to_disenchant');
        });
    }

    public function testGivePlayerGoldDustRush() {
        Event::fake();

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
            })
        );

        $disenchantingService = \Mockery::mock(DisenchantService::class)->makePartial();

        $disenchantingService->__construct(resolve(SkillCheckService::class));

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

    public function testGivePlayerGoldDustRushWhenGoldDustCapped() {
        Event::fake();

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
            })
        );

        $disenchantingService = \Mockery::mock(DisenchantService::class)->makePartial();

        $disenchantingService->__construct(resolve(SkillCheckService::class));

        $disenchantingService->shouldAllowMockingProtectedMethods()
                             ->shouldReceive('fetchDCRoll')
                             ->andReturn(1000);

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
    }

    public function testCallDisenchantItemAndSucceed() {
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

        Event::assertDispatched(function (ServerMessageEvent $event) use($character) {
            return $event->message === resolve(ServerMessageBuilder::class)->buildWithAdditionalInformation('disenchanted', number_format($character->gold_dust));
        });
    }

    public function testCallDisenchantItemAndFail() {
        Event::fake();

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(100);
                $mock->shouldReceive('characterRoll')->once()->andReturn(1);
            })
        );

        $character = $this->character->getCharacter();

        $disenchantingService = $this->app->make(DisenchantService::class);

        $disenchantingService->setUp($character)->disenchantItemWithSkill();

        $character = $character->refresh();

        $this->assertEmpty($character->inventory->slots);
        $this->assertEquals(1, $character->gold_dust);

        Event::assertDispatched(function (ServerMessageEvent $event) use($character) {
            return $event->message === resolve(ServerMessageBuilder::class)->build('failed_to_disenchant');
        });
    }
}
