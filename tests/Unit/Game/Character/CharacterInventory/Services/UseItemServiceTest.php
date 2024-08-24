<?php

namespace Tests\Unit\Game\Character\CharacterInventory\Services;

use App\Game\Character\CharacterAttack\Events\UpdateCharacterAttackEvent;
use App\Game\Character\CharacterInventory\Events\CharacterBoonsUpdateBroadcastEvent;
use App\Game\Character\CharacterInventory\Services\UseItemService;
use App\Game\Core\Events\UpdateBaseCharacterInformation;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Skills\Values\SkillTypeValue;
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

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter();

        $this->useItemService = resolve(UseItemService::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;

        $this->useItemService = null;
    }

    public function testUseItemOnCharacter()
    {
        Event::fake();
        Queue::fake();

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

        $result = $this->useItemService->useSingleItemFromInventory($character, $character->inventory->slots->where('item.type', 'alchemy')->first()->item);

        $character = $character->refresh();

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('Used selected item.', $result['message']);

        Event::assertDispatched(UpdateCharacterAttackEvent::class);
        Event::assertDispatched(UpdateTopBarEvent::class);

        $this->assertNotEmpty($character->boons);
        $this->assertNull($character->inventory->slots->where('item.type', 'alchemy')->first());
    }

    public function testDoNotGoOverMaxAmount()
    {
        Event::fake();
        Queue::fake();

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

        $this->createCharacterBoon([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'started' => now(),
            'complete' => now()->addHours(2),
            'amount_used' => 10,
            'last_for_minutes' => 120,
        ]);

        $character = $character->refresh();

        $result = $this->useItemService->useSingleItemFromInventory($character, $character->inventory->slots->where('item.type', 'alchemy')->first()->item);

        $character = $character->refresh();

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('You can only have a maximum of ten boons applied. Check active boons to see which ones you have. You can always cancel one by clicking on the row.', $result['message']);

        Event::assertNotDispatched(UpdateCharacterAttackEvent::class);
        Event::assertNotDispatched(UpdateTopBarEvent::class);

        $this->assertNotEmpty($character->boons);
        $this->assertNotNull($character->inventory->slots->where('item.type', 'alchemy')->first());
    }

    public function testDoNotUseItemWhenWouldGoAboveMaxEightHours()
    {
        Event::fake();
        Queue::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 30,
            'type' => 'alchemy',
            'affects_skill_type' => SkillTypeValue::TRAINING,
            'can_stack' => true,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item)
            ->getCharacter();

        $this->createCharacterBoon([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'started' => now(),
            'complete' => now()->addHours(2),
            'amount_used' => 1,
            'last_for_minutes' => 8 * 60,
        ]);

        $character = $character->refresh();

        $result = $this->useItemService->useSingleItemFromInventory($character, $character->inventory->slots->where('item.type', 'alchemy')->first()->item);

        $character = $character->refresh();

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('Cannot use requested item. Items may stack to a multiple of 10 or a max of 8 hours. Non stacking items cannot be used more then once, while another one is running.', $result['message']);

        Event::assertNotDispatched(UpdateCharacterAttackEvent::class);
        Event::assertNotDispatched(UpdateTopBarEvent::class);

        $this->assertNotEmpty($character->boons);
        $this->assertNotNull($character->inventory->slots->where('item.type', 'alchemy')->first());
    }

    public function testDoNotUseItemWhenItemDoesNotStack()
    {
        Event::fake();
        Queue::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 30,
            'type' => 'alchemy',
            'affects_skill_type' => SkillTypeValue::TRAINING,
            'can_stack' => false,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item)
            ->getCharacter();

        $this->createCharacterBoon([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'started' => now(),
            'complete' => now()->addHours(2),
            'amount_used' => 1,
            'last_for_minutes' => 8 * 60,
        ]);

        $character = $character->refresh();

        $result = $this->useItemService->useSingleItemFromInventory($character, $character->inventory->slots->where('item.type', 'alchemy')->first()->item);

        $character = $character->refresh();

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('Cannot use requested item. Items may stack to a multiple of 10 or a max of 8 hours. Non stacking items cannot be used more then once, while another one is running.', $result['message']);

        Event::assertNotDispatched(UpdateCharacterAttackEvent::class);
        Event::assertNotDispatched(UpdateTopBarEvent::class);

        $this->assertNotEmpty($character->boons);
        $this->assertNotNull($character->inventory->slots->where('item.type', 'alchemy')->first());
    }

    public function testPlayerDoesNotHaveItem()
    {
        Event::fake();
        Queue::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 30,
            'type' => 'alchemy',
            'affects_skill_type' => SkillTypeValue::TRAINING,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->inventoryManagement()
            ->getCharacter();

        $result = $this->useItemService->useSingleItemFromInventory($character, $item);

        $character = $character->refresh();

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('Could not find the selected items you wanted to use in your inventory. Are you sure you have them?', $result['message']);

        Event::assertNotDispatched(UpdateCharacterAttackEvent::class);
        Event::assertNotDispatched(UpdateTopBarEvent::class);

        $this->assertEmpty($character->boons);
    }

    public function testRemoveBoonFromCharacter()
    {
        Queue::fake();

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

        $character = $character->refresh();

        $this->useItemService->removeBoon($character, $character->boons->first());

        $this->assertEmpty($character->boons);
        $this->assertNull($character->inventory->slots->where('item.type', 'alchemy')->first());
    }

    public function testUpdateCharacterBasedOnItemUsed()
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

    public function testUpdateCharacterBasedOnItemUsedWhenItemDoesNotAffectSkills()
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

    public function testCharacterBoonsStackWhenTheSameItemIsFoundAndUsed()
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
            ->inventoryManagement()
            ->giveItem($item)
            ->giveItem($item)
            ->getCharacter();

        $items = $character->inventory->slots->where('item.type', 'alchemy');

        $result = $this->useItemService->useManyItemsFromInventory($character, $items->pluck('id')->toArray());

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('Used selected items.', $result['message']);

        Event::assertDispatched(UpdateCharacterAttackEvent::class);
        Event::assertDispatched(UpdateTopBarEvent::class);

        $this->assertEquals(2, $character->boons->first()->amount_used);
        $this->assertEquals(60, $character->boons->first()->last_for_minutes);
    }

    public function testDoNotGoAboveTheMaxTime()
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
            ->inventoryManagement();

        for ($i = 1; $i <= 10; $i++) {
            $character->giveItem($item);
        }

        $character = $character->getCharacter();

        $slots = $character->inventory->slots->where('item.type', 'alchemy');

        $result = $this->useItemService->useManyItemsFromInventory($character, $slots->pluck('id')->toArray());

        $character = $character->refresh();

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('Used selected items. Some items were not able to be used because of the amount of boons you have. You can check your usable items section to see which ones are left.', $result['message']);

        Event::assertDispatched(UpdateCharacterAttackEvent::class);
        Event::assertDispatched(UpdateTopBarEvent::class);

        $boons = $character->boons;

        $this->assertEquals(1, $boons->count());

        $this->assertEquals(8, $boons[0]->amount_used);
        $this->assertEquals(480, $boons[0]->last_for_minutes);
    }

    public function testDoNotGoAboveTheMaxAmount()
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
            ->inventoryManagement();

        for ($i = 1; $i <= 20; $i++) {
            $character->giveItem($item);
        }

        $character = $character->getCharacter();

        $slots = $character->inventory->slots->where('item.type', 'alchemy');

        $result = $this->useItemService->useManyItemsFromInventory($character, $slots->pluck('id')->toArray());

        $character = $character->refresh();

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('Used selected items. Some items were not able to be used because of the amount of boons you have. You can check your usable items section to see which ones are left.', $result['message']);

        Event::assertDispatched(UpdateCharacterAttackEvent::class);
        Event::assertDispatched(UpdateTopBarEvent::class);

        $boons = $character->boons;

        $this->assertEquals(1, $boons->count());

        $this->assertEquals(10, $boons[0]->amount_used);
        $this->assertEquals(10, $boons[0]->last_for_minutes);
    }

    public function testTakesExistingBoonsIntoConsideration()
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
            ->inventoryManagement();

        for ($i = 1; $i <= 8; $i++) {
            $character->giveItem($item);
        }

        $character = $character->getCharacter();

        $this->createCharacterBoon([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'started' => now(),
            'complete' => now()->addHours(2),
            'amount_used' => 2,
            'last_for_minutes' => 120,
        ]);

        $slots = $character->inventory->slots->where('item.type', 'alchemy');

        $character = $character->refresh();

        $result = $this->useItemService->useManyItemsFromInventory($character, $slots->pluck('id')->toArray());

        $character = $character->refresh();

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('Used selected items. Some items were not able to be used because of the amount of boons you have. You can check your usable items section to see which ones are left.', $result['message']);

        Event::assertDispatched(UpdateCharacterAttackEvent::class);
        Event::assertDispatched(UpdateTopBarEvent::class);

        $boons = $character->boons;

        $this->assertEquals(1, $boons->count());

        $this->assertEquals(8, $boons[0]->amount_used);
        $this->assertEquals(480, $boons[0]->last_for_minutes);
        $this->assertEquals(2, $character->inventory->slots->where('item.type', 'alchemy')->count());
    }

    public function testCannotAssignBoonsWhenYouAreMax()
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
            ->inventoryManagement();

        for ($i = 1; $i <= 10; $i++) {
            $character->giveItem($item);
        }

        $character = $character->getCharacter();

        $this->createCharacterBoon([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'started' => now(),
            'complete' => now()->addHours(2),
            'amount_used' => 10,
            'last_for_minutes' => $item->lasts_for,
        ]);

        $slots = $character->inventory->slots->where('item.type', 'alchemy');

        $character = $character->refresh();

        $result = $this->useItemService->useManyItemsFromInventory($character, $slots->pluck('id')->toArray());

        $character = $character->refresh();

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('You can only have a maximum of ten boons applied. Check active boons to see which ones you have. You can always cancel one by clicking on the row.', $result['message']);

        Event::assertNotDispatched(UpdateCharacterAttackEvent::class);
        Event::assertNotDispatched(UpdateTopBarEvent::class);

        $this->assertEquals(10, $character->boons->sum('amount_used'));
    }

    public function testCannotApplyBoonsThatDoNotExist()
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

    public function testCanApplyMultipleStacks()
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
            ->inventoryManagement();

        for ($i = 1; $i <= 2; $i++) {
            $character->giveItem($itemOne);
        }

        for ($i = 1; $i <= 8; $i++) {
            $character->giveItem($itemTwo);
        }

        $character = $character->getCharacter();

        $slots = $character->inventory->slots->where('item.type', 'alchemy');

        $result = $this->useItemService->useManyItemsFromInventory($character, $slots->pluck('id')->toArray());

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

        $this->assertEquals(0, $character->inventory->slots->where('item.type', 'alchemy')->count());
    }
}
