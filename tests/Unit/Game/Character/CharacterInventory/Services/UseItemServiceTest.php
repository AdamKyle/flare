<?php

namespace Tests\Unit\Game\Character\CharacterInventory\Services;

use App\Flare\Models\AlchemyBagSlot;
use App\Game\Character\CharacterAttack\Events\UpdateCharacterAttackEvent;
use App\Game\Character\CharacterInventory\Events\CharacterBoonsUpdateBroadcastEvent;
use App\Game\Character\CharacterInventory\Services\UseItemService;
use App\Game\Core\Events\UpdateBaseCharacterInformation;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Skills\Values\SkillTypeValue;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterBoon;
use Tests\Traits\CreateItem;

class UseItemServiceTest extends TestCase
{
    use CreateCharacterBoon, CreateItem, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?UseItemService $useItemService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter();

        $this->useItemService = resolve(UseItemService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;

        $this->useItemService = null;
    }

    public function test_use_item_on_character()
    {
        Event::fake();
        Queue::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 30,
            'type' => 'alchemy',
            'affects_skill_type' => SkillTypeValue::TRAINING->value,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 1,
        ]);

        $result = $this->useItemService->useSingleItemFromInventory($character->refresh(), $item);

        $character = $character->refresh();

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('Used selected item.', $result['message']);

        Event::assertDispatched(UpdateCharacterAttackEvent::class);
        Event::assertDispatched(UpdateTopBarEvent::class);
        Event::assertDispatched(CharacterBoonsUpdateBroadcastEvent::class);

        $this->assertNotEmpty($character->boons);
        $this->assertEquals(0, AlchemyBagSlot::where('alchemy_bag_id', $character->alchemyBag->id)->where('item_id', $item->id)->count());
    }

    public function test_do_not_go_over_max_amount()
    {
        Event::fake();
        Queue::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 30,
            'type' => 'alchemy',
            'affects_skill_type' => SkillTypeValue::TRAINING->value,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 1,
        ]);

        $this->createCharacterBoon([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'started' => now(),
            'complete' => now()->addHours(2),
            'amount_used' => 10,
            'last_for_minutes' => 120,
        ]);

        $character = $character->refresh();

        $result = $this->useItemService->useSingleItemFromInventory($character, $item);

        $character = $character->refresh();

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('You can only have a maximum of ten boons applied. Check active boons to see which ones you have. You can always cancel one by clicking on the row.', $result['message']);

        Event::assertNotDispatched(UpdateCharacterAttackEvent::class);
        Event::assertNotDispatched(UpdateTopBarEvent::class);
        Event::assertNotDispatched(CharacterBoonsUpdateBroadcastEvent::class);

        $this->assertNotEmpty($character->boons);
        $this->assertEquals(1, AlchemyBagSlot::where('alchemy_bag_id', $character->alchemyBag->id)->where('item_id', $item->id)->count());
    }

    public function test_do_not_use_item_when_would_go_above_max_eight_hours()
    {
        Event::fake();
        Queue::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 30,
            'type' => 'alchemy',
            'affects_skill_type' => SkillTypeValue::TRAINING->value,
            'can_stack' => true,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 1,
        ]);

        $this->createCharacterBoon([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'started' => now(),
            'complete' => now()->addHours(8),
            'amount_used' => 1,
            'last_for_minutes' => 8 * 60,
        ]);

        $character = $character->refresh();

        $result = $this->useItemService->useSingleItemFromInventory($character, $item);

        $character = $character->refresh();

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('Cannot use requested item. Items may stack to a multiple of 10 or a max of 8 hours. Non stacking items cannot be used more then once, while another one is running.', $result['message']);

        Event::assertNotDispatched(UpdateCharacterAttackEvent::class);
        Event::assertNotDispatched(UpdateTopBarEvent::class);

        $this->assertNotEmpty($character->boons);
        $this->assertEquals(1, AlchemyBagSlot::where('alchemy_bag_id', $character->alchemyBag->id)->where('item_id', $item->id)->count());
    }

    public function test_stackable_boon_refills_to_eight_hours_when_time_has_passed()
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));

        Event::fake();
        Queue::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 120,
            'type' => 'alchemy',
            'affects_skill_type' => SkillTypeValue::TRAINING->value,
            'can_stack' => true,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 1,
        ]);

        $this->createCharacterBoon([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'started' => now()->subHours(2),
            'complete' => now()->addHours(6),
            'amount_used' => 1,
            'last_for_minutes' => 8 * 60,
        ]);

        $character = $character->refresh();

        $result = $this->useItemService->useSingleItemFromInventory($character, $item);

        $character = $character->refresh();
        $boon = $character->boons->first();

        Carbon::setTestNow();

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('Used selected item.', $result['message']);
        $this->assertEquals(480, $boon->last_for_minutes);
        $this->assertEquals('2026-01-01 20:00:00', $boon->complete->toDateTimeString());
        $this->assertEquals(0, AlchemyBagSlot::where('alchemy_bag_id', $character->alchemyBag->id)->where('item_id', $item->id)->count());
    }

    public function test_do_not_use_item_when_item_does_not_stack()
    {
        Event::fake();
        Queue::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 30,
            'type' => 'alchemy',
            'affects_skill_type' => SkillTypeValue::TRAINING->value,
            'can_stack' => false,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 1,
        ]);

        $this->createCharacterBoon([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'started' => now(),
            'complete' => now()->addHours(2),
            'amount_used' => 1,
            'last_for_minutes' => 8 * 60,
        ]);

        $character = $character->refresh();

        $result = $this->useItemService->useSingleItemFromInventory($character, $item);

        $character = $character->refresh();

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('Cannot use requested item. Items may stack to a multiple of 10 or a max of 8 hours. Non stacking items cannot be used more then once, while another one is running.', $result['message']);

        Event::assertNotDispatched(UpdateCharacterAttackEvent::class);
        Event::assertNotDispatched(UpdateTopBarEvent::class);

        $this->assertNotEmpty($character->boons);
        $this->assertEquals(1, AlchemyBagSlot::where('alchemy_bag_id', $character->alchemyBag->id)->where('item_id', $item->id)->count());
    }

    public function test_player_does_not_have_item()
    {
        Event::fake();
        Queue::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 30,
            'type' => 'alchemy',
            'affects_skill_type' => SkillTypeValue::TRAINING->value,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $result = $this->useItemService->useSingleItemFromInventory($character, $item);

        $character = $character->refresh();

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('Could not find the selected items you wanted to use in your inventory. Are you sure you have them?', $result['message']);

        Event::assertNotDispatched(UpdateCharacterAttackEvent::class);
        Event::assertNotDispatched(UpdateTopBarEvent::class);

        $this->assertEmpty($character->boons);
    }

    public function test_remove_boon_from_character()
    {
        Queue::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 30,
            'type' => 'alchemy',
            'affects_skill_type' => SkillTypeValue::TRAINING->value,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item)
            ->getCharacter();

        $this->useItemService->useItem($character->inventory->slots->where('item.type', 'alchemy')->first(), $character);

        $character = $character->refresh();

        $this->useItemService->removeBoon($character, $character->boons->first());

        $this->assertEmpty($character->boons);
        $this->assertNull($character->inventory->slots->where('item.type', 'alchemy')->first());
    }

    public function test_update_character_based_on_item_used()
    {
        Queue::fake();
        Event::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 30,
            'type' => 'alchemy',
            'affects_skill_type' => SkillTypeValue::TRAINING,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item)
            ->getCharacter();

        $this->useItemService->useItem($character->inventory->slots->where('item.type', 'alchemy')->first(), $character);

        $this->useItemService->updateCharacter($character->refresh(), $item);

        Event::assertDispatched(UpdateBaseCharacterInformation::class);
        Event::assertDispatched(UpdateTopBarEvent::class);
        Event::assertDispatched(ServerMessageEvent::class);
        Event::assertDispatched(CharacterBoonsUpdateBroadcastEvent::class);
    }

    public function test_update_character_based_on_item_used_when_item_does_not_affect_skills()
    {
        Queue::fake();
        Event::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 30,
            'type' => 'alchemy',
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item)
            ->getCharacter();

        $this->useItemService->useItem($character->inventory->slots->where('item.type', 'alchemy')->first(), $character);

        $this->useItemService->updateCharacter($character->refresh(), $item);

        Event::assertDispatched(UpdateBaseCharacterInformation::class);
        Event::assertDispatched(UpdateTopBarEvent::class);
        Event::assertDispatched(ServerMessageEvent::class);
        Event::assertDispatched(CharacterBoonsUpdateBroadcastEvent::class);
    }

    public function test_character_boons_stack_when_the_same_item_is_found_and_used()
    {
        Queue::fake();
        Event::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 30,
            'type' => 'alchemy',
            'can_stack' => true,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $slot = AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 2,
        ]);

        $result = $this->useItemService->useManyItemsFromInventory($character->refresh(), [$slot->id, $slot->id]);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('Used selected items.', $result['message']);

        Event::assertDispatched(UpdateCharacterAttackEvent::class);
        Event::assertDispatched(UpdateTopBarEvent::class);

        $character = $character->refresh();

        $this->assertEquals(2, $character->boons->first()->amount_used);
        $this->assertEquals(60, $character->boons->first()->last_for_minutes);
    }

    public function test_do_not_go_above_the_max_time()
    {
        Queue::fake();
        Event::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 60,
            'type' => 'alchemy',
            'can_stack' => true,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $slot = AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 10,
        ]);

        $result = $this->useItemService->useManyItemsFromInventory($character->refresh(), array_fill(0, 10, $slot->id));

        $character = $character->refresh();

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('Used selected items. Some items were not able to be used because of the amount of boons you have. You can check your Alchemy Bag to see which ones are left.', $result['message']);

        Event::assertDispatched(UpdateCharacterAttackEvent::class);
        Event::assertDispatched(UpdateTopBarEvent::class);

        $boons = $character->boons;

        $this->assertEquals(1, $boons->count());

        $this->assertEquals(8, $boons[0]->amount_used);
        $this->assertEquals(480, $boons[0]->last_for_minutes);
    }

    public function test_do_not_go_above_the_max_amount()
    {
        Queue::fake();
        Event::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 1,
            'type' => 'alchemy',
            'can_stack' => true,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $slot = AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 20,
        ]);

        $result = $this->useItemService->useManyItemsFromInventory($character->refresh(), array_fill(0, 20, $slot->id));

        $character = $character->refresh();

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('Used selected items. Some items were not able to be used because of the amount of boons you have. You can check your Alchemy Bag to see which ones are left.', $result['message']);

        Event::assertDispatched(UpdateCharacterAttackEvent::class);
        Event::assertDispatched(UpdateTopBarEvent::class);

        $boons = $character->boons;

        $this->assertEquals(1, $boons->count());

        $this->assertEquals(10, $boons[0]->amount_used);
        $this->assertEquals(10, $boons[0]->last_for_minutes);
    }

    public function test_takes_existing_boons_into_consideration()
    {
        Queue::fake();
        Event::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 60,
            'type' => 'alchemy',
            'can_stack' => true,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $slot = AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 8,
        ]);

        $this->createCharacterBoon([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'started' => now(),
            'complete' => now()->addHours(2),
            'amount_used' => 2,
            'last_for_minutes' => 120,
        ]);

        $character = $character->refresh();

        $result = $this->useItemService->useManyItemsFromInventory($character, array_fill(0, 8, $slot->id));

        $character = $character->refresh();

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('Used selected items. Some items were not able to be used because of the amount of boons you have. You can check your Alchemy Bag to see which ones are left.', $result['message']);

        Event::assertDispatched(UpdateCharacterAttackEvent::class);
        Event::assertDispatched(UpdateTopBarEvent::class);

        $boons = $character->boons;

        $this->assertEquals(1, $boons->count());

        $this->assertEquals(8, $boons[0]->amount_used);
        $this->assertEquals(480, $boons[0]->last_for_minutes);
        $this->assertEquals(2, AlchemyBagSlot::where('alchemy_bag_id', $character->alchemyBag->id)->where('item_id', $item->id)->value('amount'));
    }

    public function test_cannot_assign_boons_when_you_are_max()
    {
        Queue::fake();
        Event::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 60,
            'type' => 'alchemy',
            'can_stack' => true,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $slot = AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 10,
        ]);

        $this->createCharacterBoon([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'started' => now(),
            'complete' => now()->addHours(2),
            'amount_used' => 10,
            'last_for_minutes' => $item->lasts_for,
        ]);

        $character = $character->refresh();

        $result = $this->useItemService->useManyItemsFromInventory($character, array_fill(0, 10, $slot->id));

        $character = $character->refresh();

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('You can only have a maximum of ten boons applied. Check active boons to see which ones you have. You can always cancel one by clicking on the row.', $result['message']);

        Event::assertNotDispatched(UpdateCharacterAttackEvent::class);
        Event::assertNotDispatched(UpdateTopBarEvent::class);

        $this->assertEquals(10, $character->boons->sum('amount_used'));
    }

    public function test_cannot_apply_boons_that_do_not_exist()
    {
        Queue::fake();
        Event::fake();

        $character = $this->character->getCharacter();

        $result = $this->useItemService->useManyItemsFromInventory($character, [876876, 32343, 1231, 12312]);

        $character = $character->refresh();

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('Could not find the selected items you wanted to use in your inventory. Are you sure you have them?', $result['message']);

        Event::assertNotDispatched(UpdateCharacterAttackEvent::class);
        Event::assertNotDispatched(UpdateTopBarEvent::class);

        $this->assertEquals(0, $character->boons->sum('amount_used'));
    }

    public function test_can_apply_multiple_stacks()
    {
        Queue::fake();
        Event::fake();

        $itemOne = $this->createItem([
            'usable' => true,
            'lasts_for' => 240,
            'type' => 'alchemy',
            'can_stack' => true,
        ]);

        $itemTwo = $this->createItem([
            'usable' => true,
            'lasts_for' => 10,
            'type' => 'alchemy',
            'can_stack' => true,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $slotOne = AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $itemOne->id,
            'amount' => 2,
        ]);

        $slotTwo = AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $itemTwo->id,
            'amount' => 8,
        ]);

        $ids = array_merge(array_fill(0, 2, $slotOne->id), array_fill(0, 8, $slotTwo->id));

        $result = $this->useItemService->useManyItemsFromInventory($character->refresh(), $ids);

        $character = $character->refresh();

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('Used selected items.', $result['message']);

        Event::assertDispatched(UpdateCharacterAttackEvent::class);
        Event::assertDispatched(UpdateTopBarEvent::class);

        $boons = $character->boons;

        $this->assertEquals(2, $boons->count());

        $this->assertEquals(2, $boons[0]->amount_used);
        $this->assertEquals(480, $boons[0]->last_for_minutes);

        $this->assertEquals(8, $boons[1]->amount_used);
        $this->assertEquals(80, $boons[1]->last_for_minutes);

        $this->assertEquals(0, AlchemyBagSlot::where('alchemy_bag_id', $character->alchemyBag->id)->count());
    }

    public function test_using_single_alchemy_item_decrements_alchemy_bag_slot_amount_by_one(): void
    {
        Event::fake();
        Queue::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 30,
            'type' => 'alchemy',
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 2,
        ]);

        $this->useItemService->useSingleItemFromInventory($character->refresh(), $item);

        $this->assertEquals(1, AlchemyBagSlot::where('alchemy_bag_id', $character->alchemyBag->id)->where('item_id', $item->id)->value('amount'));
    }

    public function test_using_single_alchemy_item_deletes_alchemy_bag_slot_row_when_amount_reaches_zero(): void
    {
        Event::fake();
        Queue::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 30,
            'type' => 'alchemy',
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 1,
        ]);

        $this->useItemService->useSingleItemFromInventory($character->refresh(), $item);

        $this->assertEquals(0, AlchemyBagSlot::where('alchemy_bag_id', $character->alchemyBag->id)->where('item_id', $item->id)->count());
    }

    public function test_using_many_alchemy_items_decrements_alchemy_bag_slot_amount_by_required_amount(): void
    {
        Queue::fake();
        Event::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 30,
            'type' => 'alchemy',
            'can_stack' => true,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $slot = AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 5,
        ]);

        $this->useItemService->useManyItemsFromInventory($character->refresh(), [$slot->id, $slot->id, $slot->id]);

        $this->assertEquals(2, AlchemyBagSlot::where('alchemy_bag_id', $character->alchemyBag->id)->where('item_id', $item->id)->value('amount'));
    }

    public function test_using_many_alchemy_items_deletes_alchemy_bag_slot_row_when_amount_reaches_zero(): void
    {
        Queue::fake();
        Event::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 30,
            'type' => 'alchemy',
            'can_stack' => true,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $slot = AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 2,
        ]);

        $this->useItemService->useManyItemsFromInventory($character->refresh(), [$slot->id, $slot->id]);

        $this->assertEquals(0, AlchemyBagSlot::where('alchemy_bag_id', $character->alchemyBag->id)->where('item_id', $item->id)->count());
    }

    public function test_filling_up_boon_consumes_from_alchemy_bag_slot_amount(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));

        Queue::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 60,
            'type' => 'alchemy',
            'can_stack' => true,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 3,
        ]);

        $boon = $this->createCharacterBoon([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'started' => now(),
            'complete' => now()->addMinutes(450),
            'amount_used' => 10,
            'last_for_minutes' => 450,
        ]);

        $result = $this->useItemService->fillUpBoon($character->refresh(), $boon);

        Carbon::setTestNow();

        $this->assertEquals(200, $result['status']);
        $this->assertEquals(2, AlchemyBagSlot::where('alchemy_bag_id', $character->alchemyBag->id)->where('item_id', $item->id)->value('amount'));
        $this->assertEquals('2026-01-01 20:00:00', $boon->refresh()->complete->toDateTimeString());
        $this->assertEquals(480, $boon->last_for_minutes);
        $this->assertEquals(10, $boon->amount_used);
    }

    public function test_filling_up_boon_deletes_alchemy_bag_slot_row_when_amount_reaches_zero(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));

        Queue::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 60,
            'type' => 'alchemy',
            'can_stack' => true,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 1,
        ]);

        $boon = $this->createCharacterBoon([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'started' => now(),
            'complete' => now()->addMinutes(450),
            'amount_used' => 10,
            'last_for_minutes' => 450,
        ]);

        $result = $this->useItemService->fillUpBoon($character->refresh(), $boon);

        Carbon::setTestNow();

        $this->assertEquals(200, $result['status']);
        $this->assertEquals(0, AlchemyBagSlot::where('alchemy_bag_id', $character->alchemyBag->id)->where('item_id', $item->id)->count());
        $this->assertEquals('2026-01-01 20:00:00', $boon->refresh()->complete->toDateTimeString());
        $this->assertEquals(480, $boon->last_for_minutes);
    }

    public function test_filling_up_boon_partially_refills_when_alchemy_bag_stack_has_less_than_required_amount(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));

        Queue::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 60,
            'type' => 'alchemy',
            'can_stack' => true,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 1,
        ]);

        $boon = $this->createCharacterBoon([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'started' => now(),
            'complete' => now()->addMinutes(60),
            'amount_used' => 3,
            'last_for_minutes' => 60,
        ]);

        $result = $this->useItemService->fillUpBoon($character->refresh(), $boon);

        Carbon::setTestNow();

        $this->assertEquals(200, $result['status']);
        $this->assertEquals(0, AlchemyBagSlot::where('alchemy_bag_id', $character->alchemyBag->id)->where('item_id', $item->id)->count());
        $this->assertEquals('2026-01-01 14:00:00', $boon->refresh()->complete->toDateTimeString());
        $this->assertEquals(120, $boon->last_for_minutes);
        $this->assertEquals(3, $boon->amount_used);
    }

    public function test_filling_up_boon_restores_elapsed_time_after_ten_uses(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));

        Queue::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 30,
            'type' => 'alchemy',
            'can_stack' => true,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 2,
        ]);

        $boon = $this->createCharacterBoon([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'started' => now(),
            'complete' => now()->addMinutes(60),
            'amount_used' => 10,
            'last_for_minutes' => 60,
        ]);

        $result = $this->useItemService->fillUpBoon($character->refresh(), $boon);

        Carbon::setTestNow();

        $this->assertEquals(200, $result['status']);
        $this->assertEquals(0, AlchemyBagSlot::where('alchemy_bag_id', $character->alchemyBag->id)->where('item_id', $item->id)->count());
        $this->assertEquals('2026-01-01 14:00:00', $boon->refresh()->complete->toDateTimeString());
        $this->assertEquals(120, $boon->last_for_minutes);
        $this->assertEquals(10, $boon->amount_used);
    }

    public function test_filling_up_boon_uses_only_what_fits_under_eight_hours(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));

        Queue::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 60,
            'type' => 'alchemy',
            'can_stack' => true,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 3,
        ]);

        $boon = $this->createCharacterBoon([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'started' => now(),
            'complete' => now()->addMinutes(450),
            'amount_used' => 10,
            'last_for_minutes' => 450,
        ]);

        $result = $this->useItemService->fillUpBoon($character->refresh(), $boon);

        Carbon::setTestNow();

        $this->assertEquals(200, $result['status']);
        $this->assertEquals(2, AlchemyBagSlot::where('alchemy_bag_id', $character->alchemyBag->id)->where('item_id', $item->id)->value('amount'));
        $this->assertEquals('2026-01-01 20:00:00', $boon->refresh()->complete->toDateTimeString());
        $this->assertEquals(480, $boon->last_for_minutes);
    }

    public function test_filling_up_active_non_stackable_boon_succeeds(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));

        Queue::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 60,
            'type' => 'alchemy',
            'can_stack' => false,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 2,
        ]);

        $boon = $this->createCharacterBoon([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'started' => now(),
            'complete' => now()->addMinutes(30),
            'amount_used' => 1,
            'last_for_minutes' => 30,
        ]);

        $result = $this->useItemService->fillUpBoon($character->refresh(), $boon);

        Carbon::setTestNow();

        $this->assertEquals(200, $result['status']);
    }

    public function test_filling_up_active_non_stackable_boon_does_not_change_amount_used(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));

        Queue::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 120,
            'type' => 'alchemy',
            'can_stack' => false,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 1,
        ]);

        $boon = $this->createCharacterBoon([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'started' => now(),
            'complete' => now()->addMinutes(30),
            'amount_used' => 1,
            'last_for_minutes' => 30,
        ]);

        $this->useItemService->fillUpBoon($character->refresh(), $boon);

        Carbon::setTestNow();

        $this->assertEquals(1, $boon->refresh()->amount_used);
    }

    public function test_filling_up_active_non_stackable_boon_consumes_needed_alchemy_item(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));

        Queue::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 120,
            'type' => 'alchemy',
            'can_stack' => false,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 2,
        ]);

        $boon = $this->createCharacterBoon([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'started' => now(),
            'complete' => now()->addMinutes(30),
            'amount_used' => 1,
            'last_for_minutes' => 30,
        ]);

        $this->useItemService->fillUpBoon($character->refresh(), $boon);

        Carbon::setTestNow();

        $this->assertEquals(1, AlchemyBagSlot::where('alchemy_bag_id', $character->alchemyBag->id)->where('item_id', $item->id)->value('amount'));
    }

    public function test_filling_up_active_non_stackable_boon_does_not_exceed_original_used_time_window(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));

        Queue::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 120,
            'type' => 'alchemy',
            'can_stack' => false,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 5,
        ]);

        $boon = $this->createCharacterBoon([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'started' => now(),
            'complete' => now()->addMinutes(30),
            'amount_used' => 1,
            'last_for_minutes' => 30,
        ]);

        $this->useItemService->fillUpBoon($character->refresh(), $boon);

        Carbon::setTestNow();

        $this->assertEquals('2026-01-01 14:00:00', $boon->refresh()->complete->toDateTimeString());
    }

    public function test_alchemy_use_no_longer_requires_inventory_slot_row(): void
    {
        Event::fake();
        Queue::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 30,
            'type' => 'alchemy',
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 1,
        ]);

        $this->assertEquals(0, $character->inventory->slots()->whereHas('item', fn ($q) => $q->where('type', 'alchemy'))->count());

        $result = $this->useItemService->useSingleItemFromInventory($character->refresh(), $item);

        $this->assertEquals(200, $result['status']);
        $this->assertNotEmpty($character->refresh()->boons);
    }

    public function test_normal_non_alchemy_inventory_item_use_still_uses_inventory_slots(): void
    {
        Event::fake();
        Queue::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 30,
            'type' => 'weapon',
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item)
            ->getCharacter();

        $this->assertEquals(1, $character->inventory->slots()->where('item_id', $item->id)->count());

        $result = $this->useItemService->useSingleItemFromInventory($character->refresh(), $item);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals(0, $character->refresh()->inventory->slots()->where('item_id', $item->id)->count());
        $this->assertNotEmpty($character->refresh()->boons);
    }

    public function test_using_many_alchemy_items_cannot_consume_more_than_available(): void
    {
        Event::fake();
        Queue::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 30,
            'type' => 'alchemy',
            'can_stack' => true,
        ]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $slot = AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 2,
        ]);

        $result = $this->useItemService->useManyItemsFromInventory($character, [$slot->id, $slot->id, $slot->id]);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals(2, $slot->refresh()->amount);
        $this->assertEmpty($character->refresh()->boons);
    }

    public function test_use_all_consumes_maximum_ten_items_when_duration_allows(): void
    {
        Event::fake();
        Queue::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 30,
            'type' => 'alchemy',
            'can_stack' => true,
        ]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $slot = AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 20,
        ]);

        $result = $this->useItemService->useAllAlchemyItems($character, $slot);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals(10, $slot->refresh()->amount);
        $this->assertEquals(10, $character->refresh()->boons->first()->amount_used);
    }

    public function test_use_all_consumes_only_up_to_eight_hours(): void
    {
        Event::fake();
        Queue::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 60,
            'type' => 'alchemy',
            'can_stack' => true,
        ]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $slot = AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 20,
        ]);

        $result = $this->useItemService->useAllAlchemyItems($character, $slot);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals(12, $slot->refresh()->amount);
        $this->assertEquals(480, $character->refresh()->boons->first()->last_for_minutes);
    }

    public function test_use_all_consumes_only_available_stack_and_deletes_it(): void
    {
        Event::fake();
        Queue::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 60,
            'type' => 'alchemy',
            'can_stack' => true,
        ]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $slot = AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 4,
        ]);

        $result = $this->useItemService->useAllAlchemyItems($character, $slot);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals(0, AlchemyBagSlot::where('id', $slot->id)->count());
        $this->assertEquals(4, $character->refresh()->boons->first()->amount_used);
    }

    public function test_use_all_rejects_another_characters_alchemy_bag_slot(): void
    {
        Event::fake();
        Queue::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 30,
            'type' => 'alchemy',
            'can_stack' => true,
        ]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $otherCharacter = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $slot = AlchemyBagSlot::create([
            'alchemy_bag_id' => $otherCharacter->alchemyBag->id,
            'character_id' => $otherCharacter->id,
            'item_id' => $item->id,
            'amount' => 5,
        ]);

        $result = $this->useItemService->useAllAlchemyItems($character, $slot);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals(5, $slot->refresh()->amount);
        $this->assertEmpty($character->refresh()->boons);
    }

    public function test_use_all_consumes_nothing_when_boon_is_already_at_duration_cap(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));
        Event::fake();
        Queue::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 60,
            'type' => 'alchemy',
            'can_stack' => true,
        ]);
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $slot = AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 5,
        ]);
        $this->createCharacterBoon([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'started' => now(),
            'complete' => now()->addHours(8),
            'amount_used' => 8,
            'last_for_minutes' => 480,
        ]);

        $result = $this->useItemService->useAllAlchemyItems($character->refresh(), $slot);

        Carbon::setTestNow();

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('Cannot use requested item. Items may stack to a multiple of 10 or a max of 8 hours. Non stacking items cannot be used more then once, while another one is running.', $result['message']);
        $this->assertEquals(5, $slot->refresh()->amount);
    }
}
