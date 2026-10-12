<?php

namespace Tests\Unit\Game\Character\CharacterInventory\Services;

use App\Flare\Models\Character;
use App\Game\Character\CharacterInventory\Builders\EquipManyBuilder;
use App\Game\Character\CharacterInventory\Services\MultiInventoryActionService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\Character\CharacterInventoryServiceFactory;
use Tests\TestCase;
use Tests\Traits\CreateInventorySets;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class MultiInventoryActionInventoryServiceTest extends TestCase
{
    use CreateInventorySets, CreateItem, CreateItemAffix, RefreshDatabase;

    private ?Character $character;

    private ?MultiInventoryActionService $multiInventoryActionService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $this->multiInventoryActionService = (new CharacterInventoryServiceFactory)->buildMultiInventoryActionService();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Mockery::close();

        $this->character = null;
        $this->multiInventoryActionService = null;
    }

    public function test_bulk_inventory_selling_updates_gold_and_deletes_selected_slots(): void
    {
        $firstItem = $this->createItem(['cost' => 100]);
        $secondItem = $this->createItem(['cost' => 200]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->inventoryManagement()
            ->giveItem($firstItem)
            ->giveItem($secondItem)
            ->getCharacter();
        $slotIds = $character->inventory->slots->pluck('id')->all();

        $this->multiInventoryActionService->sellManyItems($character, $slotIds);

        $this->assertSame(0, $character->refresh()->inventory->slots()->count());
        $this->assertSame(295, $character->refresh()->gold);
    }

    public function test_bulk_inventory_selling_with_exclude_keeps_the_excluded_slot(): void
    {
        $keepItem = $this->createItem(['cost' => 100]);
        $sellItem = $this->createItem(['cost' => 200]);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->inventoryManagement()
            ->giveItem($keepItem)
            ->giveItem($sellItem)
            ->getCharacter();
        $keepSlotId = $character->inventory->slots->firstWhere('item_id', $keepItem->id)->id;

        $this->multiInventoryActionService->sellManyItems($character, ['exclude' => [$keepSlotId]]);

        $this->assertSame(1, $character->refresh()->inventory->slots()->count());
        $this->assertSame($keepSlotId, $character->inventory->slots->first()->id);
    }

    public function test_destroy_many_items_removes_only_the_selected_slots(): void
    {
        $keepItem = $this->createItem();
        $destroyItem = $this->createItem();
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->inventoryManagement()
            ->giveItem($keepItem)
            ->giveItem($destroyItem)
            ->getCharacter();
        $destroySlotId = $character->inventory->slots()->where('item_id', $destroyItem->id)->first()->id;
        $keepSlotId = $character->inventory->slots()->where('item_id', $keepItem->id)->first()->id;

        $this->multiInventoryActionService->destroyManyItems($character, ['ids' => [$destroySlotId]]);

        $this->assertSame(0, $character->inventory->slots()->where('id', $destroySlotId)->count());
        $this->assertSame(1, $character->inventory->slots()->where('id', $keepSlotId)->count());
    }

    public function test_destroy_many_items_with_exclude_keeps_only_the_excluded_slots(): void
    {
        $keepItem = $this->createItem();
        $destroyItem = $this->createItem();
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->inventoryManagement()
            ->giveItem($keepItem)
            ->giveItem($destroyItem)
            ->getCharacter();
        $destroySlotId = $character->inventory->slots()->where('item_id', $destroyItem->id)->first()->id;
        $keepSlotId = $character->inventory->slots()->where('item_id', $keepItem->id)->first()->id;

        $this->multiInventoryActionService->destroyManyItems($character, ['exclude' => [$keepSlotId]]);

        $this->assertSame(0, $character->inventory->slots()->where('id', $destroySlotId)->count());
        $this->assertSame(1, $character->inventory->slots()->where('id', $keepSlotId)->count());
    }

    public function test_move_many_items_to_selected_set_moves_all_selected_items_and_reports_the_destination_set(): void
    {
        $firstItem = $this->createItem();
        $secondItem = $this->createItem();
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->inventoryManagement()
            ->giveItem($firstItem)
            ->giveItem($secondItem)
            ->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $slotIds = $character->inventory->slots->pluck('id')->all();

        $result = $this->multiInventoryActionService->moveManyItemsToSelectedSet($character, $set->id, $slotIds);

        $this->assertSame(200, $result['status']);
        $this->assertSame('Moved all selected items to: Set 0.', $result['message']);
        $this->assertSame(2, $set->refresh()->slots()->count());
        $this->assertSame(0, $character->inventory->slots()->count());
    }

    public function test_move_many_items_to_selected_set_stops_and_returns_error_when_a_move_fails(): void
    {
        $item = $this->createItem();
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->inventoryManagement()
            ->giveItem($item)
            ->getCharacter();
        $set = $this->createInventorySet(['character_id' => $character->id]);
        $validSlotId = $character->inventory->slots()->where('item_id', $item->id)->first()->id;

        $result = $this->multiInventoryActionService->moveManyItemsToSelectedSet($character, $set->id, [999999, $validSlotId]);

        $this->assertSame(422, $result['status']);
        $this->assertSame('Either the slot or the inventory set does not exist.', $result['message']);
        $this->assertSame(1, $character->inventory->slots()->where('id', $validSlotId)->count());
        $this->assertSame(0, $set->refresh()->slots()->count());
    }

    public function test_equip_many_items_equips_matching_items(): void
    {
        $item = $this->createItem(['type' => 'body']);
        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item)
            ->getCharacter();
        $slotId = $character->inventory->slots()->where('item_id', $item->id)->first()->id;

        $result = $this->multiInventoryActionService->equipManyItems($character, [$slotId]);

        $this->assertSame(200, $result['status']);
        $this->assertSame('Equipped valid items to your character.', $result['message']);
        $this->assertTrue($character->inventory->slots()->where('id', $slotId)->first()->equipped);
    }

    public function test_equip_many_items_returns_error_result_when_equip_many_builder_throws(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $equipManyBuilder = Mockery::mock(EquipManyBuilder::class);
        $equipManyBuilder->shouldReceive('buildEquipmentArray')->once()->andThrow(new Exception('Something went wrong building the equipment array.'));

        $this->multiInventoryActionService = (new CharacterInventoryServiceFactory)
            ->buildMultiInventoryActionService($equipManyBuilder);

        $result = $this->multiInventoryActionService->equipManyItems($character, [123]);

        $this->assertSame(422, $result['status']);
        $this->assertSame('Something went wrong building the equipment array.', $result['message']);
    }

    public function test_disenchant_many_items_returns_no_eligible_items_message_when_nothing_matches(): void
    {
        $character = $this->character;

        $result = $this->multiInventoryActionService->disenchantManyItems($character, []);

        $this->assertSame(200, $result['status']);
        $this->assertSame('No eligible items to disenchant.', $result['message']);
    }
}
