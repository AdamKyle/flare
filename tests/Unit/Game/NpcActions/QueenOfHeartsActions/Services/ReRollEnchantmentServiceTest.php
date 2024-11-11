<?php

namespace Tests\Unit\Game\NpcActions\QueenOfHeartsActions\Services;

use App\Flare\Values\RandomAffixDetails;
use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\NpcActions\QueenOfHeartsActions\Services\ReRollEnchantmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class ReRollEnchantmentServiceTest extends TestCase
{
    use CreateGameMap, CreateGem, CreateItem, CreateItemAffix, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?ReRollEnchantmentService $reRollEnchantmentService;

    public function setUp(): void
    {

        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $this->reRollEnchantmentService = resolve(ReRollEnchantmentService::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->reRollEnchantmentService = null;
    }

    public function testCanAffordForAllEnchantments()
    {
        $character = $this->character->getCharacter();

        $this->assertFalse($this->reRollEnchantmentService->canAfford($character, 'everything', 'all-enchantments'));
    }

    public function testCanAffordForRegularReRoll()
    {
        $character = $this->character->getCharacter();

        $this->assertFalse($this->reRollEnchantmentService->canAfford($character, '', ''));
    }

    public function testDoReRoll()
    {
        $character = $this->character->getCharacter();
        $item = $this->createItem([
            'type' => 'weapon',
            'item_prefix_id' => $this->createItemAffix([
                'randomly_generated' => true,
                'cost' => RandomAffixDetails::LEGENDARY,
            ])->id,
            'is_mythic' => false,
        ]);

        $originalAffix = $item->itemPrefix->getAttributes();

        $newItem = $this->reRollEnchantmentService->doReRoll($character, $item, 'all-enchantments', 'everything');

        $newItemAffix = $newItem->itemPrefix->getAttributes();

        $this->assertNotEquals(json_encode($originalAffix), json_encode($newItemAffix));
    }

    public function testDoReRollWithOldCost()
    {
        $character = $this->character->getCharacter();
        $item = $this->createItem([
            'type' => 'weapon',
            'item_prefix_id' => $this->createItemAffix([
                'randomly_generated' => true,
                'cost' => 100000000000, // This is the old cost of what used to be Legendary Uniques.
            ])->id,
            'is_mythic' => false,
        ]);

        $originalAffix = $item->itemPrefix->getAttributes();

        $newItem = $this->reRollEnchantmentService->doReRoll($character, $item, 'all-enchantments', 'everything');

        $newItemAffix = $newItem->itemPrefix->getAttributes();

        $this->assertNotEquals(json_encode($originalAffix), json_encode($newItemAffix));
    }

    public function testDoReRollForEverything()
    {
        $character = $this->character->getCharacter();
        $item = $this->createItem([
            'type' => 'weapon',
            'item_prefix_id' => $this->createItemAffix([
                'randomly_generated' => true,
                'cost' => RandomAffixDetails::LEGENDARY,
            ])->id,
            'item_suffix_id' => $this->createItemAffix([
                'randomly_generated' => true,
                'cost' => RandomAffixDetails::LEGENDARY,
            ])->id,
            'is_mythic' => false,
        ]);

        $originalAffix = $item->itemPrefix->getAttributes();

        $newItem = $this->reRollEnchantmentService->doReRoll($character, $item, 'all-enchantments', 'everything');

        $newItemAffix = $newItem->itemPrefix->getAttributes();

        $this->assertNotEquals(json_encode($originalAffix), json_encode($newItemAffix));
    }

    public function testDoReRollForPrefix()
    {
        $character = $this->character->getCharacter();
        $item = $this->createItem([
            'type' => 'weapon',
            'item_prefix_id' => $this->createItemAffix([
                'randomly_generated' => true,
                'cost' => RandomAffixDetails::LEGENDARY,
            ])->id,
            'item_suffix_id' => $this->createItemAffix([
                'randomly_generated' => true,
                'cost' => RandomAffixDetails::LEGENDARY,
            ])->id,
            'is_mythic' => false,
        ]);

        $originalAffix = $item->itemPrefix->getAttributes();

        $newItem = $this->reRollEnchantmentService->doReRoll($character, $item, 'prefix', 'everything');

        $newItemAffix = $newItem->itemPrefix->getAttributes();

        $this->assertNotEquals(json_encode($originalAffix), json_encode($newItemAffix));
    }

    public function testDoReRollForPrefixEffectingBaseStats()
    {
        $character = $this->character->getCharacter();
        $item = $this->createItem([
            'type' => 'weapon',
            'item_prefix_id' => $this->createItemAffix([
                'randomly_generated' => true,
                'cost' => RandomAffixDetails::LEGENDARY,
            ])->id,
            'item_suffix_id' => $this->createItemAffix([
                'randomly_generated' => true,
                'cost' => RandomAffixDetails::LEGENDARY,
            ])->id,
            'is_mythic' => false,
        ]);

        $originalAffix = $item->itemPrefix->getAttributes();

        $newItem = $this->reRollEnchantmentService->doReRoll($character, $item, 'prefix', 'base');

        $newItemAffix = $newItem->itemPrefix->getAttributes();

        $this->assertNotEquals(json_encode($originalAffix), json_encode($newItemAffix));
    }

    public function testCanAffordTheMovementCost()
    {
        $item = $this->createItem([
            'type' => 'weapon',
            'item_prefix_id' => $this->createItemAffix([
                'randomly_generated' => true,
            ])->id,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $slot = $character->inventory->slots->filter(function ($slot) {
            return $slot->item->is_unique;
        })->first();

        $this->assertFalse($this->reRollEnchantmentService->canAffordMovementCost($character, $slot->item->id, 'all-enchantments'));
    }

    public function testGetMovementCostForNonExistentItem()
    {
        $result = $this->reRollEnchantmentService->getMovementCosts(1, 'all-enchantments');

        $this->assertEmpty($result);
    }

    public function testGetMovementFromCostsForAllEnchantments()
    {
        $item = $this->createItem([
            'type' => 'weapon',
            'item_prefix_id' => $this->createItemAffix([
                'randomly_generated' => true,
                'cost' => RandomAffixDetails::LEGENDARY,
            ])->id,
            'cost' => RandomAffixDetails::LEGENDARY,
        ]);

        $result = $this->reRollEnchantmentService->getMovementCosts($item->id, 'all-enchantments');

        $this->assertGreaterThan(0, $result['gold_dust_cost']);
        $this->assertGreaterThan(0, $result['shards_cost']);
    }

    public function testGetMovementFromCostsForPrefix()
    {
        $item = $this->createItem([
            'type' => 'weapon',
            'item_prefix_id' => $this->createItemAffix([
                'randomly_generated' => true,
                'cost' => RandomAffixDetails::LEGENDARY,
            ])->id,
            'cost' => RandomAffixDetails::LEGENDARY,
        ]);

        $result = $this->reRollEnchantmentService->getMovementCosts($item->id, 'prefix');

        $this->assertGreaterThan(0, $result['gold_dust_cost']);
        $this->assertGreaterThan(0, $result['shards_cost']);
    }

    public function testMoveAllAffixes()
    {
        Event::fake();

        $item = $this->createItem([
            'type' => 'weapon',
            'item_prefix_id' => $this->createItemAffix([
                'type' => 'prefix',
                'randomly_generated' => true,
            ])->id,
            'item_suffix_id' => $this->createItemAffix([
                'type' => 'suffix',
                'randomly_generated' => true,
            ])->id,
        ]);

        $secondItem = $this->createItem([
            'type' => 'weapon',
            'item_prefix_id' => $this->createItemAffix([
                'type' => 'prefix',
                'randomly_generated' => false,
            ])->id,
            'item_suffix_id' => $this->createItemAffix([
                'type' => 'suffix',
                'randomly_generated' => false,
            ])->id,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->giveItem($secondItem)->getCharacter();

        $slotUnique = $character->inventory->slots->filter(function ($slot) {
            return $slot->item->is_unique;
        })->first();

        $slotNotUnique = $character->inventory->slots->filter(function ($slot) {
            return ! $slot->item->is_unique;
        })->first();

        $this->reRollEnchantmentService->moveAffixes($character, $slotUnique, $slotNotUnique, 'all-enchantments');

        Event::assertDispatched(UpdateCharacterCurrenciesEvent::class);
        Event::assertDispatchedTimes(ServerMessageEvent::class, 2);
        Event::assertDispatched(GlobalMessageEvent::class);
    }

    public function testMoveAllAffixesWhenOneItemHasGems()
    {
        Event::fake();

        $item = $this->createItem([
            'type' => 'weapon',
            'item_prefix_id' => $this->createItemAffix([
                'type' => 'prefix',
                'randomly_generated' => true,
            ])->id,
            'item_suffix_id' => $this->createItemAffix([
                'type' => 'suffix',
                'randomly_generated' => true,
            ])->id,
        ]);

        $item->sockets()->create([
            'item_id' => $item->id,
            'gem_id' => $this->createGem()->id,
        ]);

        $item->update([
            'socket_count' => 2,
        ]);

        $item = $item->refresh();

        $secondItem = $this->createItem([
            'type' => 'weapon',
            'item_prefix_id' => $this->createItemAffix([
                'type' => 'prefix',
                'randomly_generated' => false,
            ])->id,
            'item_suffix_id' => $this->createItemAffix([
                'type' => 'suffix',
                'randomly_generated' => false,
            ])->id,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->giveItem($secondItem)->getCharacter();

        $slotUnique = $character->inventory->slots->filter(function ($slot) {
            return $slot->item->is_unique;
        })->first();

        $slotNotUnique = $character->inventory->slots->filter(function ($slot) {
            return ! $slot->item->is_unique;
        })->first();

        $this->reRollEnchantmentService->moveAffixes($character, $slotUnique, $slotNotUnique, 'all-enchantments');

        Event::assertDispatched(UpdateCharacterCurrenciesEvent::class);
        Event::assertDispatchedTimes(ServerMessageEvent::class, 2);
        Event::assertDispatched(GlobalMessageEvent::class);
    }

    public function testMoveAllAffixesWhenItemOnlyHasOneAffix()
    {
        Event::fake();

        $item = $this->createItem([
            'type' => 'weapon',
            'item_prefix_id' => $this->createItemAffix([
                'type' => 'prefix',
                'randomly_generated' => true,
            ])->id,
        ]);

        $secondItem = $this->createItem([
            'type' => 'weapon',
            'item_prefix_id' => $this->createItemAffix([
                'type' => 'prefix',
                'randomly_generated' => false,
            ])->id,
            'item_suffix_id' => $this->createItemAffix([
                'type' => 'suffix',
                'randomly_generated' => false,
            ])->id,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->giveItem($secondItem)->getCharacter();

        $slotUnique = $character->inventory->slots->filter(function ($slot) {
            return $slot->item->is_unique;
        })->first();

        $slotNotUnique = $character->inventory->slots->filter(function ($slot) {
            return ! $slot->item->is_unique;
        })->first();

        $this->reRollEnchantmentService->moveAffixes($character, $slotUnique, $slotNotUnique, 'all-enchantments');

        Event::assertDispatched(UpdateCharacterCurrenciesEvent::class);
        Event::assertDispatchedTimes(ServerMessageEvent::class, 2);
        Event::assertDispatched(GlobalMessageEvent::class);
    }

    public function testMoveSpecificAffix()
    {
        Event::fake();

        $item = $this->createItem([
            'type' => 'weapon',
            'item_prefix_id' => $this->createItemAffix([
                'type' => 'prefix',
                'randomly_generated' => true,
            ])->id,
            'item_suffix_id' => $this->createItemAffix([
                'type' => 'suffix',
                'randomly_generated' => true,
            ])->id,
        ]);

        $secondItem = $this->createItem([
            'type' => 'weapon',
            'item_prefix_id' => $this->createItemAffix([
                'type' => 'prefix',
                'randomly_generated' => false,
            ])->id,
            'item_suffix_id' => $this->createItemAffix([
                'type' => 'suffix',
                'randomly_generated' => false,
            ])->id,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->giveItem($secondItem)->getCharacter();

        $slotUnique = $character->inventory->slots->filter(function ($slot) {
            return $slot->item->is_unique;
        })->first();

        $slotNotUnique = $character->inventory->slots->filter(function ($slot) {
            return ! $slot->item->is_unique;
        })->first();

        $this->reRollEnchantmentService->moveAffixes($character, $slotUnique, $slotNotUnique, 'prefix');

        Event::assertDispatched(UpdateCharacterCurrenciesEvent::class);
        Event::assertDispatchedTimes(ServerMessageEvent::class, 2);
        Event::assertDispatched(GlobalMessageEvent::class);
    }

    public function testMoveSpecificAffixAndDeleteTheSlotWithTheUnique()
    {
        Event::fake();

        $item = $this->createItem([
            'type' => 'weapon',
            'item_prefix_id' => $this->createItemAffix([
                'type' => 'prefix',
                'randomly_generated' => true,
                'cost' => RandomAffixDetails::LEGENDARY,
            ])->id,
        ]);

        $secondItem = $this->createItem([
            'type' => 'weapon',
            'item_prefix_id' => $this->createItemAffix([
                'type' => 'prefix',
                'randomly_generated' => false,
            ])->id,
            'item_suffix_id' => $this->createItemAffix([
                'type' => 'suffix',
                'randomly_generated' => false,
            ])->id,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->giveItem($secondItem)->getCharacter();

        $slotUnique = $character->inventory->slots->filter(function ($slot) {
            return $slot->item->is_unique;
        })->first();

        $slotNotUnique = $character->inventory->slots->filter(function ($slot) {
            return ! $slot->item->is_unique;
        })->first();

        $this->reRollEnchantmentService->moveAffixes($character, $slotUnique, $slotNotUnique, 'prefix');

        Event::assertDispatched(UpdateCharacterCurrenciesEvent::class);
        Event::assertDispatchedTimes(ServerMessageEvent::class, 2);
        Event::assertDispatched(GlobalMessageEvent::class);

        $character = $character->refresh();

        $this->assertCount(1, $character->inventory->slots);
    }
}
