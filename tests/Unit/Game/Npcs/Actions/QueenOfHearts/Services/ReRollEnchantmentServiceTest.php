<?php

namespace Tests\Unit\Game\Npcs\Actions\QueenOfHearts\Services;

use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;
use App\Game\Core\Items\Values\RandomAffixTier;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Npcs\Actions\QueenOfHearts\Services\ReRollEnchantmentService;
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

    protected function setUp(): void
    {

        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $this->reRollEnchantmentService = resolve(ReRollEnchantmentService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->reRollEnchantmentService = null;
    }

    public function test_can_afford_for_all_enchantments()
    {
        $character = $this->character->getCharacter();

        $this->assertFalse($this->reRollEnchantmentService->canAfford($character, 'everything', 'all-enchantments'));
    }

    public function test_can_afford_for_regular_re_roll()
    {
        $character = $this->character->getCharacter();

        $this->assertFalse($this->reRollEnchantmentService->canAfford($character, '', ''));
    }

    public function test_can_afford_exact_re_roll_cost()
    {
        $character = $this->character->getCharacter();

        $costs = $this->reRollEnchantmentService->getReRollCosts('base', 'prefix');

        $character->update([
            'gold_dust' => $costs['gold_dust'],
            'shards' => $costs['shards'],
        ]);

        $character = $character->refresh();

        $this->assertTrue($this->reRollEnchantmentService->canAfford($character, 'base', 'prefix'));
    }

    public function test_get_re_roll_costs_for_all_enchantments_and_everything()
    {
        $costs = $this->reRollEnchantmentService->getReRollCosts('everything', 'all-enchantments');

        $this->assertEquals(20500, $costs['gold_dust']);
        $this->assertEquals(450, $costs['shards']);
    }

    public function test_get_re_roll_costs_for_single_affix_and_regular_type()
    {
        $costs = $this->reRollEnchantmentService->getReRollCosts('base', 'prefix');

        $this->assertEquals(10100, $costs['gold_dust']);
        $this->assertEquals(200, $costs['shards']);
    }

    public function test_re_roll_deducts_currency_without_first_calling_can_afford()
    {
        $item = $this->createItem([
            'type' => 'weapon',
            'item_prefix_id' => $this->createItemAffix([
                'randomly_generated' => true,
                'cost' => RandomAffixTier::LEGENDARY->value,
            ])->id,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $character->update([
            'gold_dust' => 100000,
            'shards' => 1000,
        ]);

        $character = $character->refresh();

        $slot = $character->inventory->slots->filter(function ($slot) {
            return $slot->item->is_unique;
        })->first();

        $this->reRollEnchantmentService->reRoll($character, $slot, 'prefix', 'base');

        $character = $character->refresh();

        $this->assertEquals(89900, $character->gold_dust);
        $this->assertEquals(800, $character->shards);
    }

    public function test_do_re_roll()
    {
        $character = $this->character->getCharacter();
        $item = $this->createItem([
            'type' => 'weapon',
            'item_prefix_id' => $this->createItemAffix([
                'randomly_generated' => true,
                'cost' => RandomAffixTier::LEGENDARY->value,
            ])->id,
            'is_mythic' => false,
        ]);

        $originalAffix = $item->itemPrefix->getAttributes();

        $newItem = $this->reRollEnchantmentService->doReRoll($character, $item, 'all-enchantments', 'everything');

        $newItemAffix = $newItem->itemPrefix->getAttributes();

        $this->assertNotEquals(json_encode($originalAffix), json_encode($newItemAffix));
    }

    public function test_do_re_roll_with_old_cost()
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

    public function test_do_re_roll_for_everything()
    {
        $character = $this->character->getCharacter();
        $item = $this->createItem([
            'type' => 'weapon',
            'item_prefix_id' => $this->createItemAffix([
                'randomly_generated' => true,
                'cost' => RandomAffixTier::LEGENDARY->value,
            ])->id,
            'item_suffix_id' => $this->createItemAffix([
                'randomly_generated' => true,
                'cost' => RandomAffixTier::LEGENDARY->value,
            ])->id,
            'is_mythic' => false,
        ]);

        $originalAffix = $item->itemPrefix->getAttributes();

        $newItem = $this->reRollEnchantmentService->doReRoll($character, $item, 'all-enchantments', 'everything');

        $newItemAffix = $newItem->itemPrefix->getAttributes();

        $this->assertNotEquals(json_encode($originalAffix), json_encode($newItemAffix));
    }

    public function test_do_re_roll_for_prefix()
    {
        $character = $this->character->getCharacter();
        $item = $this->createItem([
            'type' => 'weapon',
            'item_prefix_id' => $this->createItemAffix([
                'randomly_generated' => true,
                'cost' => RandomAffixTier::LEGENDARY->value,
            ])->id,
            'item_suffix_id' => $this->createItemAffix([
                'randomly_generated' => true,
                'cost' => RandomAffixTier::LEGENDARY->value,
            ])->id,
            'is_mythic' => false,
        ]);

        $originalAffix = $item->itemPrefix->getAttributes();

        $newItem = $this->reRollEnchantmentService->doReRoll($character, $item, 'prefix', 'everything');

        $newItemAffix = $newItem->itemPrefix->getAttributes();

        $this->assertNotEquals(json_encode($originalAffix), json_encode($newItemAffix));
    }

    public function test_do_re_roll_for_prefix_effecting_base_stats()
    {
        $character = $this->character->getCharacter();
        $item = $this->createItem([
            'type' => 'weapon',
            'item_prefix_id' => $this->createItemAffix([
                'randomly_generated' => true,
                'cost' => RandomAffixTier::LEGENDARY->value,
            ])->id,
            'item_suffix_id' => $this->createItemAffix([
                'randomly_generated' => true,
                'cost' => RandomAffixTier::LEGENDARY->value,
            ])->id,
            'is_mythic' => false,
        ]);

        $originalAffix = $item->itemPrefix->getAttributes();

        $newItem = $this->reRollEnchantmentService->doReRoll($character, $item, 'prefix', 'base');

        $newItemAffix = $newItem->itemPrefix->getAttributes();

        $this->assertNotEquals(json_encode($originalAffix), json_encode($newItemAffix));
    }

    public function test_can_afford_the_movement_cost()
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

    public function test_get_movement_cost_for_non_existent_item()
    {
        $result = $this->reRollEnchantmentService->getMovementCosts(1, 'all-enchantments');

        $this->assertEmpty($result);
    }

    public function test_get_movement_from_costs_for_all_enchantments()
    {
        $item = $this->createItem([
            'type' => 'weapon',
            'item_prefix_id' => $this->createItemAffix([
                'randomly_generated' => true,
                'cost' => RandomAffixTier::LEGENDARY->value,
            ])->id,
            'cost' => RandomAffixTier::LEGENDARY->value,
        ]);

        $result = $this->reRollEnchantmentService->getMovementCosts($item->id, 'all-enchantments');

        $this->assertGreaterThan(0, $result['gold_dust']);
        $this->assertGreaterThan(0, $result['shards']);
    }

    public function test_get_movement_from_costs_for_prefix()
    {
        $item = $this->createItem([
            'type' => 'weapon',
            'item_prefix_id' => $this->createItemAffix([
                'randomly_generated' => true,
                'cost' => RandomAffixTier::LEGENDARY->value,
            ])->id,
            'cost' => RandomAffixTier::LEGENDARY->value,
        ]);

        $result = $this->reRollEnchantmentService->getMovementCosts($item->id, 'prefix');

        $this->assertGreaterThan(0, $result['gold_dust']);
        $this->assertGreaterThan(0, $result['shards']);
    }

    public function test_get_movement_costs_returns_zero_cost_when_affix_not_present_on_item()
    {
        $item = $this->createItem([
            'type' => 'weapon',
            'item_prefix_id' => $this->createItemAffix([
                'randomly_generated' => true,
                'cost' => RandomAffixTier::LEGENDARY->value,
            ])->id,
        ]);

        $result = $this->reRollEnchantmentService->getMovementCosts($item->id, 'suffix');

        $this->assertEquals(0, $result['gold_dust']);
        $this->assertEquals(0, $result['shards']);
    }

    public function test_move_all_affixes()
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

    public function test_move_all_affixes_when_one_item_has_gems()
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

    public function test_move_all_affixes_when_item_only_has_one_affix()
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

    public function test_move_specific_affix()
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

    public function test_move_affixes_deducts_the_exact_returned_movement_cost()
    {
        Event::fake();

        $item = $this->createItem([
            'type' => 'weapon',
            'item_prefix_id' => $this->createItemAffix([
                'type' => 'prefix',
                'randomly_generated' => true,
                'cost' => RandomAffixTier::LEGENDARY->value,
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

        $character->update([
            'gold_dust' => 1000000,
            'shards' => 10000,
        ]);

        $character = $character->refresh();

        $slotUnique = $character->inventory->slots->filter(function ($slot) {
            return $slot->item->is_unique;
        })->first();

        $slotNotUnique = $character->inventory->slots->filter(function ($slot) {
            return ! $slot->item->is_unique;
        })->first();

        $expectedCosts = $this->reRollEnchantmentService->getMovementCosts($slotUnique->item_id, 'prefix');

        $this->reRollEnchantmentService->moveAffixes($character, $slotUnique, $slotNotUnique, 'prefix');

        $character = $character->refresh();

        $this->assertEquals(1000000 - $expectedCosts['gold_dust'], $character->gold_dust);
        $this->assertEquals(10000 - $expectedCosts['shards'], $character->shards);
    }

    public function test_move_specific_affix_and_delete_the_slot_with_the_unique()
    {
        Event::fake();

        $item = $this->createItem([
            'type' => 'weapon',
            'item_prefix_id' => $this->createItemAffix([
                'type' => 'prefix',
                'randomly_generated' => true,
                'cost' => RandomAffixTier::LEGENDARY->value,
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

    public function test_get_supported_movement_affix_selections_includes_suffix_when_item_has_a_suffix(): void
    {
        $item = $this->createItem([
            'item_suffix_id' => $this->createItemAffix(['type' => 'suffix'])->id,
        ]);

        $selections = $this->reRollEnchantmentService->getSupportedMovementAffixSelections($item);

        $this->assertContains('suffix', $selections);
    }
}
