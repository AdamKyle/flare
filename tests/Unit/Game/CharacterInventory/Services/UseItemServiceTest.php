<?php

namespace Tests\Unit\Game\CharacterInventory\Services;

use App\Game\CharacterInventory\Events\CharacterBoonsUpdateBroadcastEvent;
use App\Game\CharacterInventory\Services\UseItemService;
use App\Game\Core\Events\UpdateBaseCharacterInformation;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class UseItemServiceTest extends TestCase {

    use RefreshDatabase, CreateItem;

    private ?CharacterFactory $character;

    private ?UseItemService $useItemService;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory())->createBaseCharacter();

        $this->useItemService = resolve(UseItemService::class);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;

        $this->useItemService = null;
    }

    public function testUseItemOnCharacter() {
        Queue::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 30,
            'type' => 'alchemy',
            'affects_skill_type' => SkillTypeValue::TRAINING,
        ]);

        $character = (new CharacterFactory())->createBaseCharacter()
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item)
            ->getCharacter();

        $this->useItemService->useItem($character->inventory->slots->where('item.type', 'alchemy')->first(), $character);

        $character = $character->refresh();

        $this->assertNotEmpty($character->boons);
        $this->assertNull($character->inventory->slots->where('item.type', 'alchemy')->first());
    }

    public function testRemoveBoonFromCharacter() {
        Queue::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 30,
            'type' => 'alchemy',
            'affects_skill_type' => SkillTypeValue::TRAINING,
        ]);

        $character = (new CharacterFactory())->createBaseCharacter()
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

    public function testUpdateCharacterBasedOnItemUsed() {
        Queue::fake();
        Event::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 30,
            'type' => 'alchemy',
            'affects_skill_type' => SkillTypeValue::TRAINING,
        ]);

        $character = (new CharacterFactory())->createBaseCharacter()
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

    public function testUpdateCharacterBasedOnItemUsedWhenItemDoesNotAffectSkills() {
        Queue::fake();
        Event::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 30,
            'type' => 'alchemy',
        ]);

        $character = (new CharacterFactory())->createBaseCharacter()
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

    public function testCharacterBoonsStackWhenTheSameItemIsFoundAndUsed() {
        Queue::fake();
        Event::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 30,
            'type' => 'alchemy',
            'can_stack' => true,
        ]);

        $character = (new CharacterFactory())->createBaseCharacter()
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item)
            ->giveItem($item)
            ->getCharacter();

        $items = $character->inventory->slots->where('item.type', 'alchemy');

        for ($i = 0; $i <= ($items->count() - 1); $i++) {
            $this->useItemService->useItem($items[$i], $character);

            $character = $character->refresh();
        }

        $character = $character->refresh();

        $this->useItemService->updateCharacter($character, $item);

        Event::assertDispatched(UpdateBaseCharacterInformation::class);
        Event::assertDispatched(UpdateTopBarEvent::class);
        Event::assertDispatched(ServerMessageEvent::class);
        Event::assertDispatched(CharacterBoonsUpdateBroadcastEvent::class);

        $this->assertEquals(2, $character->boons->first()->amount_used);
        $this->assertEquals(60, $character->boons->first()->last_for_minutes);
    }

    public function testDoNotGoAboveTheMaxAmountAndMaxTime() {
        Queue::fake();
        Event::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 60,
            'type' => 'alchemy',
            'can_stack' => true,
        ]);

        $character = (new CharacterFactory())->createBaseCharacter()
            ->givePlayerLocation()
            ->inventoryManagement();

        for ($i = 1; $i <= 10; $i++) {
            $character->giveItem($item);
        }

        $character = $character->getCharacter();

        $items = $character->inventory->slots->where('item.type', 'alchemy');

        for ($i = 0; $i <= ($items->count() - 1); $i++) {
            $this->useItemService->useItem($items[$i], $character);

            $character = $character->refresh();
        }

        $character = $character->refresh();

        $this->useItemService->updateCharacter($character, $item);

        Event::assertDispatched(UpdateBaseCharacterInformation::class);
        Event::assertDispatched(UpdateTopBarEvent::class);
        Event::assertDispatched(ServerMessageEvent::class);
        Event::assertDispatched(CharacterBoonsUpdateBroadcastEvent::class);

        $boons = $character->boons;

        $this->assertEquals(2, $boons->count());

        $this->assertEquals(8, $boons[0]->amount_used);
        $this->assertEquals(480, $boons[0]->last_for_minutes);

        $this->assertEquals(2, $boons[1]->amount_used);
        $this->assertEquals(120, $boons[1]->last_for_minutes);
    }

    public function testUseMultipleItemsCreatingTeoStacksEach() {
        Queue::fake();
        Event::fake();

        $item = $this->createItem([
            'usable' => true,
            'lasts_for' => 10,
            'type' => 'alchemy',
            'can_stack' => true,
        ]);

        $character = (new CharacterFactory())->createBaseCharacter()
            ->givePlayerLocation()
            ->inventoryManagement();

        for ($i = 1; $i <= 20; $i++) {
            $character->giveItem($item);
        }

        $character = $character->getCharacter();

        $items = $character->inventory->slots->where('item.type', 'alchemy');

        for ($i = 0; $i <= ($items->count() - 1); $i++) {
            $this->useItemService->useItem($items[$i], $character);

            $character = $character->refresh();
        }

        $character = $character->refresh();

        $this->useItemService->updateCharacter($character, $item);

        Event::assertDispatched(UpdateBaseCharacterInformation::class);
        Event::assertDispatched(UpdateTopBarEvent::class);
        Event::assertDispatched(ServerMessageEvent::class);
        Event::assertDispatched(CharacterBoonsUpdateBroadcastEvent::class);

        $boons = $character->boons;

        $this->assertEquals(2, $boons->count());

        $this->assertEquals(10, $boons[0]->amount_used);
        $this->assertEquals(100, $boons[0]->last_for_minutes);

        $this->assertEquals(10, $boons[1]->amount_used);
        $this->assertEquals(100, $boons[1]->last_for_minutes);
    }
}
