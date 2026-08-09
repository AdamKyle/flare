<?php

namespace Tests\Unit\Game\Character\Concerns;

use App\Game\Character\Concerns\FetchEquipped;
use App\Game\Character\Exceptions\MissingInventoryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateInventorySets;
use Tests\Traits\CreateInventorySlot;
use Tests\Traits\CreateItem;

class FetchEquippedTest extends TestCase
{
    use CreateInventorySets, CreateInventorySlot, CreateItem, RefreshDatabase;

    public function test_missing_inventory_flags_user_and_immediately_throws_explicit_exception(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->inventory()->delete();
        $fetcher = new class
        {
            use FetchEquipped;
        };

        try {
            $fetcher->fetchEquipped($character->refresh());
            $this->fail('The missing-inventory exception was not thrown.');
        } catch (MissingInventoryException $exception) {
            $this->assertSame('The character inventory is missing.', $exception->getMessage());
        }

        $this->assertTrue($character->user->refresh()->will_be_deleted);
    }

    public function test_missing_inventory_does_not_re_flag_a_user_already_marked_for_deletion(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $character->inventory()->delete();
        $character->user()->update(['will_be_deleted' => true]);

        $fetcher = new class
        {
            use FetchEquipped;
        };

        try {
            $fetcher->fetchEquipped($character->refresh());
            $this->fail('The missing-inventory exception was not thrown.');
        } catch (MissingInventoryException $exception) {
            $this->assertSame('The character inventory is missing.', $exception->getMessage());
        }

        $this->assertTrue($character->user->refresh()->will_be_deleted);
    }

    public function test_returns_equipped_inventory_slots_via_direct_query_when_relations_are_not_loaded(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $item = $this->createItem();
        $slot = $this->createInventorySlot([
            'inventory_id' => $character->inventory->id,
            'item_id' => $item->id,
            'equipped' => true,
        ]);

        $result = (new class
        {
            use FetchEquipped;
        })->fetchEquipped($character->refresh());

        $this->assertNotNull($result);
        $this->assertSame($slot->id, $result->first()->id);
    }

    public function test_returns_equipped_inventory_slots_from_loaded_relation_when_item_relations_are_loaded(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $item = $this->createItem();
        $this->createInventorySlot([
            'inventory_id' => $character->inventory->id,
            'item_id' => $item->id,
            'equipped' => true,
        ]);

        $character->load(['inventory.slots.item.itemSuffix', 'inventory.slots.item.itemPrefix', 'inventory.slots.item.appliedHolyStacks']);

        $result = (new class
        {
            use FetchEquipped;
        })->fetchEquipped($character);

        $this->assertNotNull($result);
        $this->assertCount(1, $result);
    }

    public function test_returns_equipped_inventory_slots_via_query_when_loaded_slots_are_missing_item_relations(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $item = $this->createItem();
        $this->createInventorySlot([
            'inventory_id' => $character->inventory->id,
            'item_id' => $item->id,
            'equipped' => true,
        ]);

        $character->load(['inventory.slots']);

        $result = (new class
        {
            use FetchEquipped;
        })->fetchEquipped($character);

        $this->assertNotNull($result);
        $this->assertCount(1, $result);
    }

    public function test_falls_back_to_equipped_inventory_set_slots_via_direct_query_when_no_equipped_inventory_slots(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $item = $this->createItem();
        $inventorySet = $this->createInventorySet([
            'character_id' => $character->id,
            'is_equipped' => true,
        ]);
        $this->createInventorySetSlot([
            'inventory_set_id' => $inventorySet->id,
            'item_id' => $item->id,
        ]);

        $result = (new class
        {
            use FetchEquipped;
        })->fetchEquipped($character->refresh());

        $this->assertNotNull($result);
        $this->assertCount(1, $result);
    }

    public function test_falls_back_to_equipped_inventory_set_from_loaded_relation_when_no_equipped_inventory_slots(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $item = $this->createItem();
        $inventorySet = $this->createInventorySet([
            'character_id' => $character->id,
            'is_equipped' => true,
        ]);
        $this->createInventorySetSlot([
            'inventory_set_id' => $inventorySet->id,
            'item_id' => $item->id,
        ]);

        $character->load(['inventorySets']);

        $result = (new class
        {
            use FetchEquipped;
        })->fetchEquipped($character);

        $this->assertNotNull($result);
        $this->assertCount(1, $result);
    }

    public function test_returns_equipped_set_slots_from_loaded_relation_when_item_relations_are_loaded(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $item = $this->createItem();
        $inventorySet = $this->createInventorySet([
            'character_id' => $character->id,
            'is_equipped' => true,
        ]);
        $this->createInventorySetSlot([
            'inventory_set_id' => $inventorySet->id,
            'item_id' => $item->id,
        ]);

        $character->load(['inventorySets.slots.item.itemSuffix', 'inventorySets.slots.item.itemPrefix', 'inventorySets.slots.item.appliedHolyStacks']);

        $result = (new class
        {
            use FetchEquipped;
        })->fetchEquipped($character);

        $this->assertNotNull($result);
        $this->assertCount(1, $result);
    }

    public function test_returns_empty_equipped_set_slots_from_loaded_relation_when_set_has_no_slots(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $this->createInventorySet([
            'character_id' => $character->id,
            'is_equipped' => true,
        ]);

        $character->load(['inventorySets.slots']);

        $result = (new class
        {
            use FetchEquipped;
        })->fetchEquipped($character);

        $this->assertNotNull($result);
        $this->assertCount(0, $result);
    }

    public function test_returns_null_when_no_equipped_inventory_slots_or_inventory_set_exist(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $result = (new class
        {
            use FetchEquipped;
        })->fetchEquipped($character->refresh());

        $this->assertNull($result);
    }
}
