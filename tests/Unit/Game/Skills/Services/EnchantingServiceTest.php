<?php

namespace Tests\Unit\Game\Skills\Services;

use App\Flare\Models\GlobalEventEnchant;
use App\Flare\Models\GlobalEventParticipation;
use App\Flare\Values\CharacterClassValue;
use App\Flare\Values\ItemSpecialtyType;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\GlobalEventSteps;
use App\Game\Messages\Builders\ServerMessageBuilder;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Skills\Services\EnchantingService;
use App\Game\Skills\Services\EnchantItemService;
use App\Game\Skills\Services\SkillCheckService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\Item;
use App\Flare\Models\GameSkill;
use App\Flare\Models\ItemAffix;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateGlobalEventGoal;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class EnchantingServiceTest extends TestCase {

    use RefreshDatabase, CreateItem, CreateClass, CreateGameSkill, CreateItemAffix, CreateEvent, CreateGlobalEventGoal;

    private ?CharacterFactory $character;

    private ?EnchantingService $enchantingService;

    private ?Item $itemToEnchant;

    private ?ItemAffix $suffix;

    private ?ItemAffix $prefix;

    private ?GameSkill $enchantingSkill;

    public function setUp(): void {
        parent::setUp();

        $this->enchantingSkill = $this->createGameSkill([
            'name' => 'Enchanting',
            'type' => SkillTypeValue::ENCHANTING,
        ]);

        $this->character = (new CharacterFactory())->createBaseCharacter()->assignSkill(
            $this->enchantingSkill
        )->givePlayerLocation();

        $this->enchantingService = resolve(EnchantingService::class);

        $this->itemToEnchant = $this->createItem([
            'cost' => 1000,
            'skill_level_required' => 1,
            'skill_level_trivial' => 100,
            'crafting_type' => 'weapon',
            'type' => 'weapon',
            'can_craft' => true,
            'default_position' => 'hammer',
        ]);

        $this->suffix = $this->createItemAffix([
            'type'                 => 'suffix',
            'int_required'         => 1,
            'skill_level_required' => 1,
            'skill_level_trivial'  => 2,
            'cost'                 => 1000,
        ]);

        $this->prefix = $this->createItemAffix([
            'type'                 => 'prefix',
            'int_required'         => 1,
            'skill_level_required' => 1,
            'skill_level_trivial'  => 2,
            'cost'                 => 1000,
        ]);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character          = null;
        $this->enchantingSkill    = null;
        $this->enchantingService  = null;
        $this->suffix             = null;
        $this->itemToEnchant      = null;
    }

    public function testFetchAffixesAndItemsThatCanBeEnchanted() {
        $character = $this->character->inventoryManagement()->giveItem($this->itemToEnchant)->getCharacter();

        $result = $this->enchantingService->fetchAffixes($character, true);

        $this->assertNotEmpty($result['affixes']);
        $this->assertNotEmpty($result['character_inventory']);
    }

    public function testFetchAffixesAsMerhcant() {
        Event::fake();

        $character = (new CharacterFactory())->createBaseCharacter([], $this->createClass([
            'name' => CharacterClassValue::MERCHANT,
        ]))
            ->assignSkill($this->enchantingSkill)
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($this->itemToEnchant)
            ->getCharacter();

        $result = $this->enchantingService->fetchAffixes($character, true);

        $this->assertNotEmpty($result['affixes']);
        $this->assertNotEmpty($result['character_inventory']);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === 'As a Merchant you get 15% discount on enchanting items. This discount is applied to the total cost of the enchantments, not the individual enchantments.';
        });
    }

    public function testFetchAffixesAndItemsThatCanBeEnchantedWithAlreadyEnchantedItemAtTheBottom() {
        $character = $this->character->inventoryManagement()->giveItem($this->itemToEnchant)->giveItem($this->createItem([
            'item_prefix_id' => $this->prefix->id,
            'item_suffix_id' => $this->suffix->id,
        ]))->getCharacter();

        $result = $this->enchantingService->fetchAffixes($character, true);

        $this->assertNotEmpty($result['affixes']);
        $this->assertNotEmpty($result['character_inventory']);

        $this->assertArrayHasKey(array_key_last($result['character_inventory']), $result['character_inventory']);
    }

    public function testGetCostOfItemAffixesAsZeroWhenAffixesDoNotExist() {
        $character = $this->character->getCharacter();

        $result = $this->enchantingService->getCostOfEnchantment($character, [10000, 100001], 560);

        $this->assertEquals(0, $result);
    }

    public function testGetCostOfItemAffixesAsZeroWhenItemsDoNotExist() {
        $character = $this->character->getCharacter();

        $result = $this->enchantingService->getCostOfEnchantment($character, [
            $this->prefix->id,
            $this->suffix->id,
        ], 560);

        $this->assertEquals(0, $result);
    }

    public function testGetCostOfAffixesToAttach() {
        $character = $this->character->getCharacter();

        $result = $this->enchantingService->getCostOfEnchantment($character, [
            $this->prefix->id,
            $this->suffix->id,
        ], $this->itemToEnchant->id);

        $this->assertEquals(2000, $result);
    }

    public function testGetCostOfAffixesToAttachAsAMerchant() {
        Event::fake();

        $character = (new CharacterFactory())->createBaseCharacter([], $this->createClass([
            'name' => CharacterClassValue::MERCHANT,
        ]))->getCharacter();

        $result = $this->enchantingService->getCostOfEnchantment($character, [
            $this->prefix->id,
            $this->suffix->id,
        ], $this->itemToEnchant->id);

        $this->assertEquals(floor(2000 - 2000 * 0.15), $result);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === 'As a Merchant you get a 15% reduction on enchanting items (reduction applied to total price).';
        });
    }

    public function testGetCostWhenItemHasAffixesAttached() {
        $character = $this->character->getCharacter();

        $result = $this->enchantingService->getCostOfEnchantment($character, [
            $this->prefix->id,
            $this->suffix->id,
        ], $this->createItem([
            'item_prefix_id' => $this->prefix->id,
            'item_suffix_id' => $this->suffix->id,
        ])->id);

        $this->assertEquals(4000, $result);
    }

    public function testEnchantItemAndTheCostIsDeducted() {
        $character = $this->character->inventoryManagement()->giveItem($this->itemToEnchant)->getCharacter();

        $character->update(['gold' => 1000]);

        $character = $character->refresh();

        $this->enchantingService->enchant($character, [
            'affix_ids' => [$this->prefix->id, $this->suffix->id]
        ], $character->inventory->slots->first(), 1000);

        $character = $character->refresh();

        $this->assertEquals(0, $character->gold);
    }

    public function testEnchantItemWithNonExistantAffixesButStillReduceTheCharactersGoldAsPunishment() {
        $character = $this->character->inventoryManagement()->giveItem($this->itemToEnchant)->getCharacter();

        $character->update(['gold' => 1000]);

        $character = $character->refresh();

        $this->enchantingService->enchant($character, [
            'affix_ids' => [10000, 1500]
        ], $character->inventory->slots->first(), 1000);

        $character = $character->refresh();

        $this->assertEquals(0, $character->gold);
    }

    public function testCannotEnchantItemWhenSkillLevelRequiredIsToHigh() {

        Event::fake();

        $this->prefix->update([
            'skill_level_required' => 1800
        ]);

        $character = $this->character->inventoryManagement()->giveItem($this->itemToEnchant)->getCharacter();

        $character->update(['gold' => 1000]);

        $character = $character->refresh();

        $this->enchantingService->enchant($character, [
            'affix_ids' => [$this->prefix->id]
        ], $character->inventory->slots->first(), 1000);

        $character = $character->refresh();

        $this->assertEquals(0, $character->gold);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === resolve(ServerMessageBuilder::class)->buildWithAdditionalInformation('to_hard_to_craft');
        });
    }

    public function testEnchantNotEnoughInt() {

        Event::fake();

        $this->prefix->update([
            'skill_level_trivial' => 1,
            'int_required'        => 10000
        ]);

        $character = $this->character->inventoryManagement()->giveItem($this->itemToEnchant)->getCharacter();

        $character->update(['gold' => 1000]);

        $character = $character->refresh();

        $this->enchantingService->enchant($character, [
            'affix_ids' => [$this->prefix->id]
        ], $character->inventory->slots->first(), 1000);

        $character = $character->refresh();

        $this->assertEquals(0, $character->gold);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === resolve(ServerMessageBuilder::class)->buildWithAdditionalInformation('int_to_low_enchanting');
        });
    }

    public function testEnchantWhenToEasy() {

        Event::fake();

        $this->prefix->update([
            'skill_level_trivial' => -10
        ]);

        $character = $this->character->inventoryManagement()->giveItem($this->itemToEnchant)->getCharacter();

        $character->update(['gold' => 1000]);

        $character = $character->refresh();

        $this->enchantingService->enchant($character, [
            'affix_ids' => [$this->prefix->id]
        ], $character->inventory->slots->first(), 1000);

        $character = $character->refresh();

        $this->assertEquals(0, $character->gold);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === resolve(ServerMessageBuilder::class)->buildWithAdditionalInformation('to_easy_to_craft');
        });
    }

    public function testEnchantingSucceeds() {

        Event::fake();

        $this->instance(
            EnchantItemService::class,
            Mockery::mock(EnchantItemService::class, function (MockInterface $mock) {
                $mock->makePartial()->shouldReceive('attachAffix')->once()->andReturn(true);
            })
        );

        $character = $this->character->inventoryManagement()->giveItem($this->itemToEnchant)->getCharacter();

        $character->update(['gold' => 1000]);

        $character = $character->refresh();

        $enchantingService = resolve(EnchantingService::class);

        $slot = $character->inventory->slots->first();

        $enchantingService->enchant($character, [
            'affix_ids' => [$this->prefix->id]
        ], $slot, 1000);

        $character = $character->refresh();

        $this->assertEquals(0, $character->gold);

        Event::assertDispatched(function (ServerMessageEvent $event) use ($slot) {
            return $event->message === 'Applied enchantment: ' . $this->prefix->name . ' to: ' . $slot->item->refresh()->affix_name;
        });
    }

    public function testEnchantingSucceedsWhileEnchantingGlobalEventIsRunning() {

        Event::fake();

        $this->instance(
            SkillCheckService::class,
            Mockery::mock(SkillCheckService::class, function (MockInterface $mock) {
                $mock->shouldReceive('getDCCheck')->once()->andReturn(1);
                $mock->shouldReceive('characterRoll')->once()->andReturn(100);
            })
        );

        $enchantingService = $this->app->make(EnchantingService::class);

        $this->createEvent([
            'type'                    => EventType::DELUSIONAL_MEMORIES_EVENT,
            'current_event_goal_step' => GlobalEventSteps::ENCHANT,
        ]);

        $this->createGlobalEventGoal([
            'event_type'                  => EventType::DELUSIONAL_MEMORIES_EVENT,
            'max_enchants'                => 100,
            'reward_every'                => 10,
            'next_reward_at'              => 10,
            'item_specialty_type_reward'  => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique'            => false,
            'should_be_mythic'            => true,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($this->itemToEnchant)->getCharacter();

        $character->update(['gold' => 1000]);

        $character = $character->refresh();

        $slot = $character->inventory->slots->first();

        $enchantingService->enchant($character, [
            'affix_ids' => [$this->prefix->id]
        ], $slot, 1000);

        $character = $character->refresh();

        $this->assertEquals(0, $character->gold);

        $this->assertNotNull($character->globalEventEnchants);

        $globalEventParticipation = GlobalEventParticipation::where('character_id', $character->id)->first();

        $this->assertNotNull($globalEventParticipation);

        $this->assertEmpty($character->inventory->slots);
    }

    public function testEnchantingFails() {

        Event::fake();

        $this->instance(
            EnchantItemService::class,
            Mockery::mock(EnchantItemService::class, function (MockInterface $mock) {
                $mock->makePartial()->shouldReceive('attachAffix')->once()->andReturn(false);
            })
        );

        $character = $this->character->inventoryManagement()->giveItem($this->itemToEnchant)->getCharacter();

        $character->update(['gold' => 1000]);

        $character = $character->refresh();

        $enchantingService = resolve(EnchantingService::class);

        $slot = $character->inventory->slots->first();

        $itemName =  $slot->item->affix_name;

        $enchantingService->enchant($character, [
            'affix_ids' => [$this->prefix->id]
        ], $slot, 1000);

        $character = $character->refresh();

        $this->assertEquals(0, $character->gold);

        Event::assertDispatched(function (ServerMessageEvent $event) use ($itemName) {
            return $event->message === 'You failed to apply ' . $this->prefix->name . ' to: ' . $itemName . '. The item shatters before you. You lost the investment.';
        });
    }

    public function testGetTimeAdditionForEnchantingShouldBeTriple() {
        $item = $this->createItem([
            'item_prefix_id' => $this->prefix->id,
            'item_suffix_id' => $this->suffix->id,
        ]);

        $time = $this->enchantingService->timeForEnchanting($item);

        $this->assertEquals('triple', $time);
    }

    public function testGetTimeAdditionForEnchantingShouldBeDouble() {
        $item = $this->createItem([
            'item_prefix_id' => $this->prefix->id,
        ]);

        $time = $this->enchantingService->timeForEnchanting($item);

        $this->assertEquals('double', $time);
    }

    public function testGetTimeAdditionForEnchantingShouldBeNull() {
        $time = $this->enchantingService->timeForEnchanting($this->itemToEnchant);

        $this->assertNull($time);
    }

    public function testGetInventorySlotFromSlotId() {
        $character = $this->character->inventoryManagement()->giveItem($this->itemToEnchant)->getCharacter();

        $slotId    = $character->inventory->slots->first()->id;

        $slot      = $this->enchantingService->getSlotFromInventory($character, $slotId);

        $this->assertNotNull($slot);
        $this->assertEquals($slotId, $slot->id);
    }

    public function testFetchCharacterEnchantingXP() {
        $character = $this->character->getCharacter();

        $weaponCraftingXpData = $this->enchantingService->getEnchantingXP($character);

        $enchantingSkill = $character->skills()->where('game_skill_id', $this->enchantingSkill->id)->first();

        $this->assertEquals($weaponCraftingXpData['skill_name'], $enchantingSkill->baseSkill->name);
    }
}
