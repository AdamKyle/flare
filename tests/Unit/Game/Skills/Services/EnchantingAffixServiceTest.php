<?php

namespace Tests\Unit\Game\Skills\Services;

use App\Flare\Models\GameSkill;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Game\Character\Values\CharacterClass;
use App\Game\Core\Items\Values\ItemSpecialtyType;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\GlobalEventSteps;
use App\Game\Events\Values\ScheduledEventStatus;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Skills\Services\EnchantingAffixService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
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
use Tests\Traits\CreateScheduledEvent;

class EnchantingAffixServiceTest extends TestCase
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
        CreateScheduledEvent,
        RefreshDatabase;

    private ?CharacterFactory $character;

    private ?EnchantingAffixService $enchantingAffixService;

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

        $this->enchantingAffixService = resolve(EnchantingAffixService::class);

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
        $this->enchantingAffixService = null;
        $this->suffix = null;
        $this->itemToEnchant = null;
    }

    public function test_fetch_affixes_and_items_that_can_be_enchanted_for_global_event()
    {
        $character = $this->character->inventoryManagement()->giveItem($this->itemToEnchant)->getCharacter();

        $schedule = $this->createScheduledEvent(['event_type' => EventType::DELUSIONAL_MEMORIES_EVENT, 'status' => ScheduledEventStatus::RUNNING, 'currently_running' => true]);
        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'scheduled_event_id' => $schedule->id,
            'current_event_goal_step' => GlobalEventSteps::ENCHANT,
            'ends_at' => now()->addHour(),
        ]);

        $globalEventGoal = $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_id' => $event->id,
            'max_enchants' => 100,
            'reward_every' => 10,
            'next_reward_at' => 10,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER->value,
            'should_be_unique' => false,
            'should_be_mythic' => true,
        ]);

        $character = $this->character->getCharacter();

        $inventory = $this->createGlobalCraftingInventory([
            'global_event_goal_id' => $globalEventGoal->id,
            'character_id' => $character->id,
        ]);

        $this->createGlobalCraftingInventorySlot([
            'global_event_crafting_inventory_id' => $inventory->id,
            'item_id' => $this->createItem()->id,
        ]);

        $gameMap = $this->createGameMap([
            'only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
        ]);

        $character->map()->update([
            'game_map_id' => $gameMap->id,
        ]);

        $character = $character->refresh();

        $result = $this->enchantingAffixService->fetchAffixes($character, true);

        $this->assertNotEmpty($result['items_for_event']);
    }

    public function test_event_items_for_enchanting_exclude_ineligible_item_types()
    {
        $character = $this->character->getCharacter();

        $schedule = $this->createScheduledEvent(['event_type' => EventType::DELUSIONAL_MEMORIES_EVENT, 'status' => ScheduledEventStatus::RUNNING, 'currently_running' => true]);
        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'scheduled_event_id' => $schedule->id,
            'current_event_goal_step' => GlobalEventSteps::ENCHANT,
            'ends_at' => now()->addHour(),
        ]);

        $globalEventGoal = $this->createGlobalEventGoal([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_id' => $event->id,
            'max_enchants' => 100,
            'reward_every' => 10,
            'next_reward_at' => 10,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER->value,
            'should_be_unique' => false,
            'should_be_mythic' => true,
        ]);

        $gameMap = $this->createGameMap([
            'only_during_event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
        ]);

        $character->map()->update(['game_map_id' => $gameMap->id]);

        $character = $character->refresh();

        $inventory = $this->createGlobalCraftingInventory([
            'global_event_goal_id' => $globalEventGoal->id,
            'character_id' => $character->id,
        ]);

        $eligibleItem = $this->createItem(['type' => 'body']);
        $questItem = $this->createItem(['type' => 'quest']);

        $this->createGlobalCraftingInventorySlot([
            'global_event_crafting_inventory_id' => $inventory->id,
            'item_id' => $eligibleItem->id,
        ]);

        $this->createGlobalCraftingInventorySlot([
            'global_event_crafting_inventory_id' => $inventory->id,
            'item_id' => $questItem->id,
        ]);

        $result = $this->enchantingAffixService->fetchAffixes($character, true);

        $this->assertCount(1, $result['items_for_event']);
        $this->assertEquals($eligibleItem->name, $result['items_for_event'][0]['item_name']);
    }

    public function test_fetch_affixes_as_merhcant()
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter([], $this->createClass([
            'name' => CharacterClass::MERCHANT->value,
        ]))
            ->assignSkill($this->enchantingSkill)
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($this->itemToEnchant)
            ->getCharacter();

        $result = $this->enchantingAffixService->fetchAffixes($character, true);

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
            ->giveItem($enchanted)
            ->giveItem($unenchanted)
            ->getCharacter();

        $result = $this->enchantingAffixService->fetchAffixes($character, true);
        $inventory = $result['character_inventory'];

        $this->assertNotEmpty($result['affixes']);
        $this->assertNotEmpty($inventory);
        $this->assertEquals(
            $unenchanted->id,
            $inventory->first()->item_id
        );
        $this->assertEquals(
            $enchanted->id,
            $inventory->last()->item_id
        );
    }

    public function test_fetch_affixes_ignore_trinkets_excludes_trinkets_and_artifacts_but_keeps_eligible_item()
    {
        $trinket = $this->createItem(['type' => 'trinket']);
        $artifact = $this->createItem(['type' => 'artifact']);

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($trinket)
            ->giveItem($artifact)
            ->giveItem($this->itemToEnchant)
            ->getCharacter();

        $result = $this->enchantingAffixService->fetchAffixes($character, true);
        $inventory = $result['character_inventory'];

        $this->assertCount(1, $inventory);
        $this->assertEquals($this->itemToEnchant->id, $inventory->first()->item_id);
    }
}
