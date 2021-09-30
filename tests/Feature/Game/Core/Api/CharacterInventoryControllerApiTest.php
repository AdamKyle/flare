<?php

namespace Tests\Feature\Game\Core\Api;

use App\Game\Skills\Jobs\DisenchantItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\SetSlot;
use App\Flare\Values\ItemUsabilityType;
use App\Game\Skills\Values\SkillTypeValue;
use Tests\TestCase;
use Tests\Traits\CreateCharacterBoon;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Setup\Character\CharacterFactory;

class CharacterInventoryControllerApiTestTest extends TestCase
{
    use RefreshDatabase,
        CreateItem,
        CreateItemAffix,
        CreateUser,
        CreateRole,
        CreateCharacterBoon;

    private $character;

    private $item;

    public function setUp(): void {
        parent::setUp();

        $this->item = $this->createItem([
            'name'        => 'Rusty Dagger',
            'type'        => 'weapon',
            'base_damage' => '6',
        ]);

        $this->character = (new CharacterFactory)->createBaseCharacter()
                                                 ->equipStartingEquipment();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testCanGetInventory() {
        $user = $this->character->getUser();

        $response = $this->actingAs($user)->JSON('get', '/api/character/'.$user->character->id.'/inventory')->response;

        $content = json_decode($response->content());

        // Test character is instantiated with starting equipment, we should see this.
        $this->assertNotEmpty($content->equipped);
    }

    public function testCanDestroyAllItems() {
        $user = $this->character->inventoryManagement()->giveitem($this->createItem([
            'name'             => 'Armour',
            'base_damage'      => 6,
            'base_ac'          => 6,
            'type'             => 'gloves',
            'default_position' => 'hands',
            'crafting_type'    => 'armour',
        ]))->getCharacterFactory()->getUser();

        $response = $this->actingAs($user)->JSON('post', '/api/character/'.$user->character->id.'/inventory/destroy-all')->response;

        $character = $user->character->refresh();

        $this->assertCount(0, $character->inventory->slots->filter(function($slot) {
            return !$slot->equipped;
        }));
    }

    public function testCanDisenchantAllItems() {
        Queue::fake();

        $user = $this->character->inventoryManagement()->giveitem($this->createItem([
            'name'             => 'Armour',
            'item_prefix_id'   => $this->createItemAffix(['type' => 'prefix']),
            'base_damage'      => 6,
            'base_ac'          => 6,
            'type'             => 'gloves',
            'default_position' => 'hands',
            'crafting_type'    => 'armour',
        ]))->giveitem($this->createItem([
            'name'             => 'Armour',
            'item_prefix_id'   => $this->createItemAffix(['type' => 'prefix']),
            'base_damage'      => 6,
            'base_ac'          => 6,
            'type'             => 'gloves',
            'default_position' => 'hands',
            'crafting_type'    => 'armour',
        ]))->giveitem($this->createItem([
            'name'             => 'Armour',
            'item_prefix_id'   => $this->createItemAffix(['type' => 'prefix']),
            'base_damage'      => 6,
            'base_ac'          => 6,
            'type'             => 'gloves',
            'default_position' => 'hands',
            'crafting_type'    => 'armour',
        ]))->getCharacterFactory()->getUser();

        $this->actingAs($user)->JSON('post', '/api/character/'.$user->character->id.'/inventory/disenchant-all');


        Queue::assertPushed(DisenchantItem::class);

    }

    public function testHasNothingToDisenchant() {
        $user = $this->character->inventoryManagement()->giveitem($this->createItem([
            'name'             => 'Armour',
            'base_damage'      => 6,
            'base_ac'          => 6,
            'type'             => 'gloves',
            'default_position' => 'hands',
            'crafting_type'    => 'armour',
        ]))->getCharacterFactory()->getUser();

        $this->actingAs($user)->JSON('post', '/api/character/'.$user->character->id.'/inventory/disenchant-all');

        $character = $user->character->refresh();

        $this->assertCount(1, $character->inventory->slots->filter(function($slot) {
            return !$slot->equipped;
        }));
    }

    public function testCanUnEquipItem() {

        $user = $this->character->getUser();

        $this->actingAs($user)->JSON('post', '/api/character/'.$user->character->id.'/inventory/unequip', [
            'item_to_remove'         => InventorySlot::where('equipped', true)->first()->id,
            'inventory_set_equipped' => false,
        ]);

        $character = Character::first();

        $character->inventory->slots->each(function($slot) {
            $this->assertFalse($slot->equipped);
        });
    }

    public function testCanUnequipSet() {
        $item = $this->createItem([
            'name'                => 'Sample Item',
            'type'                => 'weapon',
            'base_damage'         => 200,
            'cost'                => 100,
            'crafting_type'       => 'weapon',
            'description'         => 'sample',
            'can_resurrect'       => false,
            'resurrection_chance' => 0.0
        ]);

        $character = $this->character->inventorySetManagement()->createInventorySets(10)->putItemInSet($item, 0, 'left-hand', true);

        $this->actingAs($character->getCharacter()->user)->json('post', '/api/character/'.$character->getCharacter()->id.'/inventory/unequip', [
            'inventory_set_equipped' => true,
        ]);

        $character = $this->character->getCharacter();

        $character->inventory->slots->each(function($slot) {
            $this->assertFalse($slot->equipped);
        });

        $character->inventorySets->filter(function($set) {
            $this->assertTrue($set->slots()->where('equipped', true)->get()->isEmpty());
            $this->assertFalse($set->is_equipped);
        });
    }

    public function testUnequipAll() {

        $user = $this->character->inventoryManagement()
            ->giveitem($this->createItem([
                'name'             => 'Armour',
                'base_damage'      => 6,
                'base_ac'          => 6,
                'type'             => 'gloves',
                'default_position' => 'hands',
                'crafting_type'    => 'armour',
            ]))
            ->equipItem('hands', 'Armour')
            ->getCharacterFactory()
            ->getUser();

        $this->actingAs($user)->json('post', '/api/character/'.$user->character->id.'/inventory/unequip-all');

        $character = $this->character->getCharacter();

        $equipped = $character->inventory->slots->filter(function($slot) {
            return $slot->equipped;
        })->all();

        $this->assertTrue(empty($equipped));
    }

    public function testCanUnequipAllWithSet() {
        $item = $this->createItem([
            'name'                => 'Sample Item',
            'type'                => 'weapon',
            'base_damage'         => 200,
            'cost'                => 100,
            'crafting_type'       => 'weapon',
            'description'         => 'sample',
            'can_resurrect'       => false,
            'resurrection_chance' => 0.0
        ]);

        $character = $this->character->inventorySetManagement()->createInventorySets(10)->putItemInSet($item, 0, 'left-hand', true);
        $user      = $character->getCharacter()->user;

        $this->actingAs($user)->json('post', '/api/character/'.$user->character->id.'/inventory/unequip-all', [
            'is_set_equipped' => true,
        ]);

        $character = $character->getCharacter();

        $equipped = $character->inventory->slots->filter(function($slot) {
            return $slot->equipped;
        })->all();

        $this->assertTrue(empty($equipped));

        $character = $this->character->getCharacter();

        $character->inventory->slots->each(function($slot) {
            $this->assertFalse($slot->equipped);
        });

        $character->inventorySets->filter(function($set) {
            $this->assertTrue($set->slots()->where('equipped', true)->get()->isEmpty());
            $this->assertFalse($set->is_equipped);
        });
    }

    public function testCannotUnEquipItem() {
        $user = $this->character->getUser();

        $response = $this->actingAs($user)->json('post', '/api/character/'.$user->character->id.'/inventory/unequip' , [
            'item_to_remove' => rand(900,9560),
            'inventory_set_equipped' => false,
        ])->response;

        $this->assertEquals(422, $response->status());

        $character = $this->character->getCharacter();

        $character->inventory->slots->each(function($slot) {
            $this->assertTrue($slot->equipped);
        });
    }

    public function testCanDestroyItem() {

        $user = $this->character->inventoryManagement()->unequipAll()->getCharacterFactory()->getUser();

        $this->actingAs($user)->json('post', '/api/character/'.$user->character->id.'/inventory/destroy', [
            'slot_id' => InventorySlot::first()->id
        ]);

        $this->assertTrue($this->character->getCharacter()->inventory->slots->isEmpty());
    }

    public function testCannotDestroyItemYouDontHave() {
        $user = $this->character->getUser();

        $response = $this->actingAs($user)->json('post', '/api/character/'.$user->character->id.'/inventory/destroy', [
            'slot_id' => 5768
        ])->response;

        $this->assertEquals(422, $response->status());
    }

    public function testCannotDestroyItemYouHaveEquipped() {
        $user = $this->character->getUser();

        $response = $this->actingAs($user)->json('post', '/api/character/'.$user->character->id.'/inventory/destroy', [
            'slot_id' => InventorySlot::first()->id,
        ])->response;

        $this->assertEquals(422, $response->status());
    }

    public function testCanMoveItemToSet() {
        $item = $this->createItem([
            'name'                => 'Sample Item',
            'type'                => 'weapon',
            'base_damage'         => 200,
            'cost'                => 100,
            'crafting_type'       => 'weapon',
            'description'         => 'sample',
            'can_resurrect'       => false,
            'resurrection_chance' => 0.0
        ]);

        $slotId = $this->character->inventoryManagement()->giveItem($item)->getSlotId(0);
        $character = $this->character->inventorySetManagement()->createInventorySets(10)->getCharacter();

        $response = $this->actingAs($character->user)->json('post', '/api/character/'.$character->id.'/inventory/move-to-set', [
            'slot_id' => $slotId,
            'move_to_set' => $character->inventorySets()->first()->id,
        ])->response;

        $character = $character->refresh();

        $this->assertTrue($character->inventory->slots()->where('item_id', $item->id)->get()->isEmpty());
        $this->assertFalse($character->inventorySets()->join('set_slots', function($join) {
            $join->on('set_slots.inventory_set_id', 'inventory_sets.id');
        })->where('set_slots.item_id', $item->id)->get()->isEmpty());
    }

    public function testCannotMoveItemToSet() {
        $character = $this->character->inventorySetManagement()->createInventorySets(10)->getCharacter();

        $response = $this->actingAs($character->user)->json('post', '/api/character/'.$character->id.'/inventory/move-to-set', [
            'slot_id' => 879,
            'move_to_set' => $character->inventorySets()->first()->id,
        ])->response;

        $this->assertEquals(422, $response->status());

        $character = $character->refresh();

        $this->assertTrue($character->inventorySets()->join('set_slots', function($join) {
            $join->on('set_slots.inventory_set_id', 'inventory_sets.id');
        })->whereNotNull('set_slots.item_id')->get()->isEmpty());
    }

    public function testCanEquipSet() {
        $item = $this->createItem([
            'name'                => 'Sample Item',
            'type'                => 'weapon',
            'base_damage'         => 200,
            'cost'                => 100,
            'crafting_type'       => 'weapon',
            'description'         => 'sample',
            'can_resurrect'       => false,
            'resurrection_chance' => 0.0
        ]);

        $character = $this->character->inventorySetManagement()->createInventorySets(10)->putItemInSet($item, 0)->getCharacter();

        $this->actingAs($character->user)->json('post', '/api/character/'.$character->id.'/inventory-set/equip/'.$character->inventorySets()->first()->id);

        $character = $character->refresh();

        $this->assertTrue($character->inventorySets()->where('is_equipped', true)->get()->isNotEmpty());
    }

    public function testCannotEquipSet() {
        $item = $this->createItem([
            'name'                => 'Sample Item',
            'type'                => 'weapon',
            'base_damage'         => 200,
            'cost'                => 100,
            'crafting_type'       => 'weapon',
            'description'         => 'sample',
            'can_resurrect'       => false,
            'resurrection_chance' => 0.0
        ]);

        $character = $this->character->inventorySetManagement()->createInventorySets(10)->putItemInSet($item, 0)->updateSet(0, [
            'can_be_equipped' => false,
        ])->getCharacter();

        $response = $this->actingAs($character->user)->json('post', '/api/character/'.$character->id.'/inventory-set/equip/'.$character->inventorySets()->first()->id)->response;

        $this->assertEquals(422, $response->status());

        $character = $character->refresh();

        $this->assertFalse($character->inventorySets()->where('is_equipped', true)->get()->isNotEmpty());
    }

    public function testCanSaveEquipmentAsSet() {
        $this->character->inventorySetManagement()->createInventorySets(10);

        $character = $this->character->getCharacter();

        $response = $this->actingAs($character->user)->json('post', '/api/character/'.$character->id.'/inventory/save-equipped-as-set', [
            'move_to_set' => $this->character->inventorySetManagement()->getInventorySetId(0)
        ])->response;

        $character = $character->refresh();

        $this->assertTrue($character->inventory->slots->filter(function($slot) {
            return $slot->equipped;
        })->isEmpty());

        $this->assertCount(1, $character->inventorySets()->where('is_equipped', true)->get());
    }

    public function testCanRemoveFromSet() {
        $item = $this->createItem([
            'name'                => 'Sample Item',
            'type'                => 'weapon',
            'base_damage'         => 200,
            'cost'                => 100,
            'crafting_type'       => 'weapon',
            'description'         => 'sample',
            'can_resurrect'       => false,
            'resurrection_chance' => 0.0
        ]);

        $character = $this->character->inventorySetManagement()->createInventorySets(10)->putItemInSet($item, 0)->getCharacter();

        $this->actingAs($character->user)->json('post','/api/character/'.$character->id.'/inventory-set/remove', [
            'inventory_set_id' => $this->character->inventorySetManagement()->getInventorySetId(0),
            'slot_id' => SetSlot::whereNotNull('item_id')->first()->id,
        ]);

        $character = $character->refresh();

        $this->assertTrue($character->inventorySets()->join('set_slots', function($join) {
            $join->on('set_slots.inventory_set_id', 'inventory_sets.id');
        })->whereNotNull('set_slots.item_id')->get()->isEmpty());

        $this->assertTrue($character->inventory->slots->filter(function($slot) use($item) {
            return $slot->item_id === $item->id;
        })->isNotEmpty());
    }

    public function testCanNotRemoveFromSetNoInventorySpace() {
        $item = $this->createItem([
            'name'                => 'Sample Item',
            'type'                => 'weapon',
            'base_damage'         => 200,
            'cost'                => 100,
            'crafting_type'       => 'weapon',
            'description'         => 'sample',
            'can_resurrect'       => false,
            'resurrection_chance' => 0.0
        ]);

        $character = $this->character->inventorySetManagement()->createInventorySets(10)->putItemInSet($item, 0)->getCharacterFactory();

        $character = $character->updateCharacter(['inventory_max' => 0])->getCharacter();

        $response = $this->actingAs($character->user)->json('post','/api/character/'.$character->id.'/inventory-set/remove', [
            'inventory_set_id' => $this->character->inventorySetManagement()->getInventorySetId(0),
            'slot_id' => SetSlot::whereNotNull('item_id')->first()->id,
        ])->response;

        $this->assertEquals(422, $response->status());

        $character = $character->refresh();

        $this->assertTrue($character->inventorySets()->join('set_slots', function($join) {
            $join->on('set_slots.inventory_set_id', 'inventory_sets.id');
        })->whereNotNull('set_slots.item_id')->get()->isNotEmpty());

        $this->assertTrue($character->inventory->slots->filter(function($slot) use($item) {
            return $slot->item_id === $item->id;
        })->isEmpty());
    }

    public function testCannotRemoveFromSetSlotDoesNotExist() {
        $item = $this->createItem([
            'name'                => 'Sample Item',
            'type'                => 'weapon',
            'base_damage'         => 200,
            'cost'                => 100,
            'crafting_type'       => 'weapon',
            'description'         => 'sample',
            'can_resurrect'       => false,
            'resurrection_chance' => 0.0
        ]);

        $character = $this->character->inventorySetManagement()->createInventorySets(10)->putItemInSet($item, 0)->getCharacter();

        $response = $this->actingAs($character->user)->json('post','/api/character/'.$character->id.'/inventory-set/remove', [
            'inventory_set_id' => $this->character->inventorySetManagement()->getInventorySetId(0),
            'slot_id' => 9879
        ])->response;

        $this->assertEquals(422, $response->status());
    }

    public function testCannotRemoveFromSetIsEquipped() {
        $item = $this->createItem([
            'name'                => 'Sample Item',
            'type'                => 'weapon',
            'base_damage'         => 200,
            'cost'                => 100,
            'crafting_type'       => 'weapon',
            'description'         => 'sample',
            'can_resurrect'       => false,
            'resurrection_chance' => 0.0
        ]);

        $character = $this->character->inventorySetManagement()->createInventorySets(10)->putItemInSet($item, 0, 'left-hand', true)->getCharacter();

        $response = $this->actingAs($character->user)->json('post','/api/character/'.$character->id.'/inventory-set/remove', [
            'inventory_set_id' => $this->character->inventorySetManagement()->getInventorySetId(0),
            'slot_id' => SetSlot::whereNotNull('item_id')->first()->id,
        ])->response;

        $this->assertEquals(422, $response->status());
    }

    public function testCannotRemoveFromNoInventorySpace() {
        $item = $this->createItem([
            'name'                => 'Sample Item',
            'type'                => 'weapon',
            'base_damage'         => 200,
            'cost'                => 100,
            'crafting_type'       => 'weapon',
            'description'         => 'sample',
            'can_resurrect'       => false,
            'resurrection_chance' => 0.0
        ]);

        $character = $this->character->updateCharacter([
            'inventory_max' => 0
        ])->inventorySetManagement()->createInventorySets(10)->putItemInSet($item, 0, 'left-hand', true)->getCharacter();

        $response = $this->actingAs($character->user)->json('post','/api/character/'.$character->id.'/inventory-set/remove', [
            'inventory_set_id' => $this->character->inventorySetManagement()->getInventorySetId(0),
            'slot_id' => SetSlot::whereNotNull('item_id')->first()->id,
        ])->response;

        $this->assertEquals(422, $response->status());
    }

    public function testCanEmptySet() {
        $item = $this->createItem([
            'name'                => 'Sample Item',
            'type'                => 'weapon',
            'base_damage'         => 200,
            'cost'                => 100,
            'crafting_type'       => 'weapon',
            'description'         => 'sample',
            'can_resurrect'       => false,
            'resurrection_chance' => 0.0
        ]);

        $character = $this->character->inventorySetManagement()->createInventorySets(10)->putItemInSet($item, 0)->getCharacter();

        $this->actingAs($character->user)->json('post', '/api/character/'.$character->id.'/inventory-set/'.$character->inventorySets()->first()->id.'/remove-all', [
            'character' => $character->id,
            'inventorySet' => $this->character->inventorySetManagement()->getInventorySetId(0),
        ]);

        $character = $character->refresh();

        $this->assertTrue($character->inventorySets()->join('set_slots', function($join) {
            $join->on('set_slots.inventory_set_id', 'inventory_sets.id');
        })->whereNotNull('set_slots.item_id')->get()->isEmpty());

        $this->assertTrue($character->inventory->slots->filter(function($slot) use($item) {
            return $slot->item_id === $item->id;
        })->isNotEmpty());
    }

    public function testUseItem() {
        Queue::fake();

        $this->item->update([
            'usable' => true,
            'stat_increase' => true,
            'increase_stat_by' => 0.08,
            'lasts_for' => 10
        ]);

        $item = $this->item->refresh();

        $character = (new CharacterFactory)->createBaseCharacter()->inventoryManagement()->giveItem($item)->getCharacter();

        $this->actingAs($character->user)->json('post', '/api/character/'.$character->id.'/inventory/use-item/'. $item->id);

        $character = $character->refresh();

        $this->assertCount(1, $character->refresh()->boons);
    }

    public function testCannotUseItemMaxBoons() {
        Queue::fake();

        $this->item->update([
            'usable' => true,
            'stat_increase' => true,
            'increase_stat_by' => 0.08,
            'lasts_for' => 10
        ]);

        $item = $this->item->refresh();

        $character = (new CharacterFactory)->createBaseCharacter()->inventoryManagement()->giveItem($item)->getCharacter();

        for ($i = 1; $i <= 10; $i++) {
            $this->createCharacterBoon([
                'character_id' => $character->id,
                'stat_bonus' => 0.08,
                'started' => now(),
                'complete' => now()->addHour(10),
                'type' => ItemUsabilityType::STAT_INCREASE
            ]);
        }

        $character = $character->refresh();

        $response = $this->actingAs($character->user)->json('post', '/api/character/'.$character->id.'/inventory/use-item/'. $item->id)->response;

        $this->assertEquals(422, $response->status());
    }

    public function testUseItemAffectsSkills() {
        Queue::fake();

        $this->item->update([
            'usable' => true,
            'affects_skill_type' => SkillTypeValue::ALCHEMY,
            'increase_skill_bonus_by' => 0.18,
            'lasts_for' => 10
        ]);

        $item = $this->item->refresh();

        $character = (new CharacterFactory)->createBaseCharacter()->inventoryManagement()->giveItem($item)->getCharacter();

        $character = $character->refresh();

        $this->actingAs($character->user)->json('post', '/api/character/'.$character->id.'/inventory/use-item/'. $item->id);

        $character = $character->refresh();

        $this->assertCount(1, $character->boons);
    }

    public function testUseItemThatDoesntExist() {
        Queue::fake();

        $this->item->update([
            'usable' => true,
            'stat_increase' => true,
            'increase_stat_by' => 0.08,
            'lasts_for' => 10
        ]);

        $item = $this->item->refresh();

        $character = (new CharacterFactory)->createBaseCharacter()->inventoryManagement()->getCharacter();

        $response = $this->actingAs($character->user)->json('post', '/api/character/'.$character->id.'/inventory/use-item/'. $item->id)->response;

        $this->assertEquals(422, $response->status());
    }

    public function testUseManyItems() {
        $this->item->update([
            'usable' => true,
            'stat_increase' => true,
            'increase_stat_by' => 0.08,
            'lasts_for' => 10
        ]);

        $item = $this->item->refresh();

        $character = (new CharacterFactory)->createBaseCharacter()->inventoryManagement()->giveItem($item)->giveItem($item)->giveItem($item)->getCharacter();
        $response = $this->actingAs($character->user)->json('post', '/api/character/'.$character->id.'/inventory/use-many', [
            'slot_ids' => [$character->inventory->slots->filter(function($slot) { return $slot->item->usable; })->first()->id]
        ])->response;

        $this->assertEquals(200, $response->status());
    }

    public function testCannotUseManyItemMaxBoons() {
        $this->item->update([
            'usable' => true,
            'stat_increase' => true,
            'increase_stat_by' => 0.08,
            'lasts_for' => 10
        ]);

        $item = $this->item->refresh();

        $character = (new CharacterFactory)->createBaseCharacter()->inventoryManagement()->giveItem($item)->getCharacter();

        for ($i = 1; $i <= 10; $i++) {
            $this->createCharacterBoon([
                'character_id' => $character->id,
                'stat_bonus' => 0.08,
                'started' => now(),
                'complete' => now()->addHour(10),
                'type' => ItemUsabilityType::STAT_INCREASE
            ]);
        }

        $character = $character->refresh();

        $response = $this->actingAs($character->user)->json('post', '/api/character/'.$character->id.'/inventory/use-many', [
            'slot_ids' => [$character->inventory->slots->filter(function($slot) { return $slot->item->usable; })->first()->id]
        ])->response;

        $this->assertEquals(422, $response->status());
    }

    public function testUseManyItemsAffectsSkills() {
        $this->item->update([
            'usable' => true,
            'affects_skill_type' => SkillTypeValue::ALCHEMY,
            'increase_skill_bonus_by' => 0.18,
            'lasts_for' => 10
        ]);

        $item = $this->item->refresh();

        $character = (new CharacterFactory)->createBaseCharacter()->inventoryManagement()->giveItem($item)->getCharacter();

        $character = $character->refresh();

        $response = $this->actingAs($character->user)->json('post', '/api/character/'.$character->id.'/inventory/use-many', [
            'slot_ids' => [$character->inventory->slots->filter(function($slot) { return $slot->item->usable; })->first()->id]
        ])->response;

        $this->assertEquals(200, $response->status());
    }

    public function testUseManyItemsThatDoesntExist() {
        $character = $this->character->getCharacter();

        $response = $this->actingAs($character->user)->json('post', '/api/character/'.$character->id.'/inventory/use-many', [
            'slot_ids' => 678
        ])->response;

        $this->assertEquals(422, $response->status());
    }
}
