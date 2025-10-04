<?php

namespace Tests\Unit\Game\Skills\Services;

use App\Flare\Models\GameSkill;
use App\Flare\Models\GlobalEventParticipation;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Flare\Values\CharacterClassValue;
use App\Flare\Values\ItemSpecialtyType;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\GlobalEventSteps;
use App\Game\Messages\Builders\ServerMessageBuilder;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Types\CraftingMessageTypes;
use App\Game\Skills\Services\EnchantingService;
use App\Game\Skills\Services\EnchantItemService;
use App\Game\Skills\Services\SkillCheckService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateGlobalCraftingInventory;
use Tests\Traits\CreateGlobalCraftingInventorySlot;
use Tests\Traits\CreateGlobalEventGoal;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class EnchantingServiceTest extends TestCase
{
    use CreateClass,
        CreateEvent,
        CreateGameMap,
        CreateGameSkill,
        CreateGlobalCraftingInventory,
        CreateGlobalCraftingInventorySlot,
        CreateGlobalEventGoal,
        CreateItem,
        CreateItemAffix,
        RefreshDatabase;

    private ?CharacterFactory $character;

    private ?EnchantingService $enchantingService;

    private ?Item $itemToEnchant;

    private ?ItemAffix $suffix;

    private ?ItemAffix $prefix;

    private ?GameSkill $enchantingSkill;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enchantingSkill = $this->createGameSkill([
            'name' => 'Enchanting',
            'type' => SkillTypeValue::ENCHANTING->value,
        ]);

        $this->character = (new CharacterFactory)->createBaseCharacter()->assignSkill(
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
            'type' => 'suffix',
            'int_required' => 1,
            'skill_level_required' => 1,
            'skill_level_trivial' => 2,
            'cost' => 1000,
        ]);

        $this->prefix = $this->createItemAffix([
            'type' => 'prefix',
            'int_required' => 1,
            'skill_level_required' => 1,
            'skill_level_trivial' => 2,
            'cost' => 1000,
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->enchantingSkill = null;
        $this->enchantingService = null;
        $this->suffix = null;
        $this->itemToEnchant = null;
    }

    public function test_fetch_affixes_and_items_that_can_be_enchanted()
    {
        $character = $this->character->inventoryManagement()->giveItem($this->itemToEnchant)->getCharacter();

        $result = $this->enchantingService->fetchAffixes($character, true);

        $this->assertNotEmpty($result['affixes']);
        $this->assertNotEmpty($result['character_inventory']);
    }

    public function test_fetch_affixes_and_items_that_can_be_enchanted_for_global_event()
    {
        $character = $this->character->inventoryManagement()->giveItem($this->itemToEnchant)->getCharacter();

        $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'current_event_goal_step' => GlobalEventSteps::ENCHANT,
        ]);

        $globalEventGoal = $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'max_enchants' => 100,
            'reward_every' => 10,
            'next_reward_at' => 10,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique' => false,
            'should_be_mythic' => true,
        ]);

        $character = $this->character->getCharacter();

        $inventory = $this->createGlobalCraftingInventory([
            'global_event_id' => $globalEventGoal->id,
            'character_id' => $character->id,
        ]);

        $this->createGlobalCraftingInventorySlot([
            'global_event_crafting_inventory_id' => $inventory->id,
            'item_id' => $this->createItem(),
        ]);

        $gameMap = $this->createGameMap([
            'only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
        ]);

        $character->map()->update([
            'game_map_id' => $gameMap->id,
        ]);

        $character = $character->refresh();

        $result = $this->enchantingService->fetchAffixes($character, true);

        $this->assertNotEmpty($result['items_for_event']);
    }

    public function test_fetch_affixes_as_merhcant()
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass([
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

    public function test_fetch_affixes_and_items_that_can_be_enchanted_with_already_enchanted_item_at_the_bottom()
    {
        $unenchanted = $this->itemToEnchant;
        $enchanted = $this->createItem([
            'item_prefix_id' => $this->prefix->id,
            'item_suffix_id' => $this->suffix->id,
        ]);

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($unenchanted)
            ->giveItem($enchanted)
            ->getCharacter();

        $result = $this->enchantingService->fetchAffixes($character, true);
        $inventory = $result['character_inventory'];

        $this->assertNotEmpty($result['affixes']);
        $this->assertNotEmpty($inventory);
        $this->assertEquals(
            $enchanted->id,
            $inventory->last()->item_id
        );
    }

    public function test_get_cost_of_item_affixes_as_zero_when_affixes_do_not_exist()
    {
        $character = $this->character->getCharacter();

        $result = $this->enchantingService->getCostOfEnchantment($character, [10000, 100001], 560);

        $this->assertEquals(0, $result);
    }

    public function test_get_cost_of_item_affixes_as_zero_when_items_do_not_exist()
    {
        $character = $this->character->getCharacter();

        $result = $this->enchantingService->getCostOfEnchantment($character, [
            $this->prefix->id,
            $this->suffix->id,
        ], 560);

        $this->assertEquals(0, $result);
    }

    public function test_get_cost_of_affixes_to_attach()
    {
        $character = $this->character->getCharacter();

        $result = $this->enchantingService->getCostOfEnchantment($character, [
            $this->prefix->id,
            $this->suffix->id,
        ], $this->itemToEnchant->id);

        $this->assertEquals(2000, $result);
    }

    public function test_get_cost_of_affixes_to_attach_as_a_merchant()
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass([
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

    public function test_get_cost_when_item_has_affixes_attached()
    {
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

    public function test_enchant_item_and_the_cost_is_deducted()
    {
        $character = $this->character->inventoryManagement()->giveItem($this->itemToEnchant)->getCharacter();

        $character->update(['gold' => 1000]);

        $character = $character->refresh();

        $this->enchantingService->enchant($character, [
            'affix_ids' => [$this->prefix->id, $this->suffix->id],
            'enchant_for_event' => false,
        ], $character->inventory->slots->first(), 1000);

        $character = $character->refresh();

        $this->assertEquals(0, $character->gold);
    }

    public function test_enchant_item_with_non_existant_affixes_but_still_reduce_the_characters_gold_as_punishment()
    {
        $character = $this->character->inventoryManagement()->giveItem($this->itemToEnchant)->getCharacter();

        $character->update(['gold' => 1000]);

        $character = $character->refresh();

        $this->enchantingService->enchant($character, [
            'affix_ids' => [10000, 1500],
            'enchant_for_event' => false,
        ], $character->inventory->slots->first(), 1000);

        $character = $character->refresh();

        $this->assertEquals(0, $character->gold);
    }

    public function test_cannot_enchant_item_when_skill_level_required_is_to_high()
    {

        Event::fake();

        $this->prefix->update([
            'skill_level_required' => 1800,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($this->itemToEnchant)->getCharacter();

        $character->update(['gold' => 1000]);

        $character = $character->refresh();

        $this->enchantingService->enchant($character, [
            'affix_ids' => [$this->prefix->id],
            'enchant_for_event' => false,
        ], $character->inventory->slots->first(), 1000);

        $character = $character->refresh();

        $this->assertEquals(0, $character->gold);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === resolve(ServerMessageBuilder::class)->buildWithAdditionalInformation(CraftingMessageTypes::TO_HARD_TO_CRAFT);
        });
    }

    public function test_enchant_not_enough_int()
    {

        Event::fake();

        $this->prefix->update([
            'skill_level_trivial' => 1,
            'int_required' => 10000,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($this->itemToEnchant)->getCharacter();

        $character->update(['gold' => 1000]);

        $character = $character->refresh();

        $this->enchantingService->enchant($character, [
            'affix_ids' => [$this->prefix->id],
            'enchant_for_event' => false,
        ], $character->inventory->slots->first(), 1000);

        $character = $character->refresh();

        $this->assertEquals(0, $character->gold);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === resolve(ServerMessageBuilder::class)->buildWithAdditionalInformation(CraftingMessageTypes::INT_TO_LOW_ENCHANTING);
        });
    }

    public function test_enchant_when_to_easy()
    {

        Event::fake();

        $this->prefix->update([
            'skill_level_trivial' => -10,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($this->itemToEnchant)->getCharacter();

        $character->update(['gold' => 1000]);

        $character = $character->refresh();

        $this->enchantingService->enchant($character, [
            'affix_ids' => [$this->prefix->id],
            'enchant_for_event' => false,
        ], $character->inventory->slots->first(), 1000);

        $character = $character->refresh();

        $this->assertEquals(0, $character->gold);

        Event::assertDispatched(function (ServerMessageEvent $event) {
            return $event->message === resolve(ServerMessageBuilder::class)->buildWithAdditionalInformation(CraftingMessageTypes::TO_EASY_TO_CRAFT);
        });
    }

    public function test_enchanting_succeeds()
    {

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
            'affix_ids' => [$this->prefix->id],
            'enchant_for_event' => false,
        ], $slot, 1000);

        $character = $character->refresh();

        $this->assertEquals(0, $character->gold);

        Event::assertDispatched(function (ServerMessageEvent $event) use ($slot) {
            return $event->message === 'Applied enchantment: '.$this->prefix->name.' to: '.$slot->item->refresh()->affix_name;
        });
    }

    public function test_enchanting_succeeds_while_enchanting_global_event_is_running()
    {

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
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'current_event_goal_step' => GlobalEventSteps::ENCHANT,
        ]);

        $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'max_enchants' => 100,
            'reward_every' => 10,
            'next_reward_at' => 10,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique' => false,
            'should_be_mythic' => true,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($this->itemToEnchant)->getCharacter();

        $character->update(['gold' => 1000]);

        $character = $character->refresh();

        $slot = $character->inventory->slots->first();

        $enchantingService->enchant($character, [
            'affix_ids' => [$this->prefix->id],
            'enchant_for_event' => true,
        ], $slot, 1000);

        $character = $character->refresh();

        $this->assertEquals(0, $character->gold);

        $this->assertNotNull($character->globalEventEnchants);

        $globalEventParticipation = GlobalEventParticipation::where('character_id', $character->id)->first();

        $this->assertNotNull($globalEventParticipation);

        $this->assertEmpty($character->inventory->slots);
    }

    public function test_enchanting_fails()
    {

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

        $itemName = $slot->item->affix_name;

        $enchantingService->enchant($character, [
            'affix_ids' => [$this->prefix->id],
            'enchant_for_event' => false,
        ], $slot, 1000);

        $character = $character->refresh();

        $this->assertEquals(0, $character->gold);

        Event::assertDispatched(function (ServerMessageEvent $event) use ($itemName) {
            return $event->message === 'You failed to apply '.$this->prefix->name.' to: '.$itemName.'. The item shatters before you. You lost the investment.';
        });
    }

    public function test_get_time_addition_for_enchanting_should_be_triple()
    {
        $item = $this->createItem([
            'item_prefix_id' => $this->prefix->id,
            'item_suffix_id' => $this->suffix->id,
        ]);

        $time = $this->enchantingService->timeForEnchanting($item);

        $this->assertEquals('triple', $time);
    }

    public function test_get_time_addition_for_enchanting_should_be_double()
    {
        $item = $this->createItem([
            'item_prefix_id' => $this->prefix->id,
        ]);

        $time = $this->enchantingService->timeForEnchanting($item);

        $this->assertEquals('double', $time);
    }

    public function test_get_time_addition_for_enchanting_should_be_null()
    {
        $time = $this->enchantingService->timeForEnchanting($this->itemToEnchant);

        $this->assertNull($time);
    }

    public function test_get_inventory_slot_from_slot_id()
    {
        $character = $this->character->inventoryManagement()->giveItem($this->itemToEnchant)->getCharacter();

        $slotId = $character->inventory->slots->first()->id;

        $slot = $this->enchantingService->getSlotFromInventory($character, $slotId);

        $this->assertNotNull($slot);
        $this->assertEquals($slotId, $slot->id);
    }

    public function test_fetch_character_enchanting_xp()
    {
        $character = $this->character->getCharacter();

        $weaponCraftingXpData = $this->enchantingService->getEnchantingXP($character);

        $enchantingSkill = $character->skills()->where('game_skill_id', $this->enchantingSkill->id)->first();

        $this->assertEquals($weaponCraftingXpData['skill_name'], $enchantingSkill->baseSkill->name);
    }

    public function test_get_item_for_global_event()
    {

        $globalEventGoal = $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'max_enchants' => 100,
            'reward_every' => 10,
            'next_reward_at' => 10,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique' => false,
            'should_be_mythic' => true,
        ]);

        $character = $this->character->getCharacter();

        $inventory = $this->createGlobalCraftingInventory([
            'global_event_id' => $globalEventGoal->id,
            'character_id' => $character->id,
        ]);

        $slot = $this->createGlobalCraftingInventorySlot([
            'global_event_crafting_inventory_id' => $inventory->id,
            'item_id' => $this->createItem(),
        ]);

        $foundSlot = $this->enchantingService->getSlotFromInventory($character, $slot->id);

        $this->assertEquals($foundSlot->id, $slot->id);
    }
}
