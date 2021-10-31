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
use Tests\Traits\CreateGameSkill;
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
        CreateCharacterBoon,
        CreateGameSkill;

    private $character;

    private $item;

    public function setUp(): void {
        parent::setUp();

        $this->item = $this->createItem([
            'name'        => 'Rusty Dagger',
            'type'        => 'weapon',
            'base_damage' => '6',
        ]);

        $this->createItemAffix();

        $this->character = (new CharacterFactory)->createBaseCharacter()
                                                 ->givePlayerLocation()
                                                 ->assignSkill($this->createGameSkill([
                                                     'type' => SkillTypeValue::ENCHANTING,
                                                 ]))
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

        $this->actingAs($character->getCharacter(false)->user)->json('post', '/api/character/'.$character->getCharacter(false)->id.'/inventory/unequip', [
            'inventory_set_equipped' => true,
        ]);

        $character = $this->character->getCharacter(false);

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

        $character = $this->character->getCharacter(false);

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
        $user      = $character->getCharacter(false)->user;

        $this->actingAs($user)->json('post', '/api/character/'.$user->character->id.'/inventory/unequip-all', [
            'is_set_equipped' => true,
        ]);

        $character = $character->getCharacter(false);

        $equipped = $character->inventory->slots->filter(function($slot) {
            return $slot->equipped;
        })->all();

        $this->assertTrue(empty($equipped));

        $character = $this->character->getCharacter(false);

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

        $character = $this->character->getCharacter(false);

        $character->inventory->slots->each(function($slot) {
            $this->assertTrue($slot->equipped);
        });
    }

    public function testCanDestroyItem() {

        $user = $this->character->inventoryManagement()->unequipAll()->getCharacterFactory()->getUser();

        $this->actingAs($user)->json('post', '/api/character/'.$user->character->id.'/inventory/destroy', [
            'slot_id' => InventorySlot::first()->id
        ]);

        $this->assertTrue($this->character->getCharacter(false)->inventory->slots->isEmpty());
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
        $character = $this->character->inventorySetManagement()->createInventorySets(10)->getCharacter(false);

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

    public function testCanMoveItemToSetAndCauseItToBeNotEquippableSpellDamage() {
        $spellDamage = $this->createItem([
            'name'                => 'Sample Item',
            'type'                => 'spell-damage',
        ]);

        $character = $this->character->inventorySetManagement()
                                     ->createInventorySets(10)
                                     ->putItemInSet($spellDamage, 0)
                                     ->putItemInSet($spellDamage, 0)
                                     ->getCharacterFactory()
                                     ->inventoryManagement()
                                     ->giveItem($spellDamage)
                                     ->getCharacterFactory()
                                     ->getCharacter(false);

        $spellDamageSlot = $character->inventory->slots->where('item_id', $spellDamage->id)->first()->id;

        $spellDamageResponse = $this->actingAs($character->user)->json('post', '/api/character/'.$character->id.'/inventory/move-to-set', [
            'slot_id' => $spellDamageSlot,
            'move_to_set' => $character->inventorySets()->first()->id,
        ])->response;

        $this->assertEquals(200, $spellDamageResponse->status());

        $character = $character->refresh();

        $this->assertNotNull($character->inventorySets()->where('can_be_equipped', false)->first());
    }

    public function testCanMoveItemToSetAndCauseItToBeNotEquippableSpellDamageAndSpellHealing() {
        $spellDamage = $this->createItem([
            'name'                => 'Sample Item',
            'type'                => 'spell-damage',
        ]);

        $spellHealing = $this->createItem([
            'name'                => 'Sample Item',
            'type'                => 'spell-damage',
        ]);

        $character = $this->character->inventorySetManagement()
            ->createInventorySets(10)
            ->putItemInSet($spellDamage, 0)
            ->putItemInSet($spellDamage, 0)
            ->getCharacterFactory()
            ->inventoryManagement()
            ->giveItem($spellHealing)
            ->getCharacterFactory()
            ->getCharacter(false);

        $spellHealingSlot = $character->inventory->slots->where('item_id', $spellHealing->id)->first()->id;

        $spellHealingResponse = $this->actingAs($character->user)->json('post', '/api/character/'.$character->id.'/inventory/move-to-set', [
            'slot_id' => $spellHealingSlot,
            'move_to_set' => $character->inventorySets()->first()->id,
        ])->response;

        $this->assertEquals(200, $spellHealingResponse->status());

        $character = $character->refresh();

        $this->assertNotNull($character->inventorySets()->where('can_be_equipped', false)->first());
    }

    public function testCanMoveItemToSetAndCauseItToBeNotEquippableSpellHealing() {
        $spellHealing = $this->createItem([
            'name'                => 'Sample Item',
            'type'                => 'spell-healing',
        ]);

        $character = $this->character->inventorySetManagement()
                                     ->createInventorySets(10)
                                     ->putItemInSet($spellHealing, 0)
                                     ->putItemInSet($spellHealing, 0)
                                     ->getCharacterFactory()
                                     ->inventoryManagement()
                                     ->giveItem($spellHealing)
                                     ->getCharacterFactory()
                                     ->getCharacter(false);

        $spellHealingSlot = $character->inventory->slots->where('item_id', $spellHealing->id)->first()->id;

        $spellHealingResponse = $this->actingAs($character->user)->json('post', '/api/character/'.$character->id.'/inventory/move-to-set', [
            'slot_id' => $spellHealingSlot,
            'move_to_set' => $character->inventorySets()->first()->id,
        ])->response;

        $this->assertEquals(200, $spellHealingResponse->status());

        $character = $character->refresh();

        $this->assertNotNull($character->inventorySets()->where('can_be_equipped', false)->first());
    }

    public function testCanMoveItemToSetAndCauseItToBeNotEquippableSpellHealingAndDamage() {
        $spellHealing = $this->createItem([
            'name'                => 'Sample Item',
            'type'                => 'spell-healing',
        ]);

        $spellDamage = $this->createItem([
            'name'                => 'Sample Item',
            'type'                => 'spell-healing',
        ]);

        $character = $this->character->inventorySetManagement()
            ->createInventorySets(10)
            ->putItemInSet($spellHealing, 0)
            ->putItemInSet($spellHealing, 0)
            ->getCharacterFactory()
            ->inventoryManagement()
            ->giveItem($spellDamage)
            ->getCharacterFactory()
            ->getCharacter(false);

        $spellDamageSlot = $character->inventory->slots->where('item_id', $spellDamage->id)->first()->id;

        $spellDamageResponse = $this->actingAs($character->user)->json('post', '/api/character/'.$character->id.'/inventory/move-to-set', [
            'slot_id' => $spellDamageSlot,
            'move_to_set' => $character->inventorySets()->first()->id,
        ])->response;

        $this->assertEquals(200, $spellDamageResponse->status());

        $character = $character->refresh();

        $this->assertNotNull($character->inventorySets()->where('can_be_equipped', false)->first());
    }

    public function testCanMoveItemToSetAndCauseItToBeNotEquippableRing() {
        $ring = $this->createItem([
            'name'                => 'Sample Item',
            'type'                => 'ring',
        ]);

        $character = $this->character->inventorySetManagement()
                                     ->createInventorySets(10)
                                     ->putItemInSet($ring, 0)
                                     ->putItemInSet($ring, 0)
                                     ->getCharacterFactory()
                                     ->inventoryManagement()
                                     ->giveItem($ring)
                                     ->getCharacterFactory()
                                     ->getCharacter(false);

        $ringSlot = $character->inventory->slots->where('item_id', $ring->id)->first()->id;

        $responseResponse = $this->actingAs($character->user)->json('post', '/api/character/'.$character->id.'/inventory/move-to-set', [
            'slot_id' => $ringSlot,
            'move_to_set' => $character->inventorySets()->first()->id,
        ])->response;

        $this->assertEquals(200, $responseResponse->status());

        $character = $character->refresh();

        $this->assertNotNull($character->inventorySets()->where('can_be_equipped', false)->first());
    }

    public function testCanMoveItemToSetAndCauseItToBeNotEquippableArtifact() {
        $artifact = $this->createItem([
            'name'                => 'Sample Item',
            'type'                => 'artifact',
        ]);

        $character = $this->character->inventorySetManagement()
                                     ->createInventorySets(10)
                                     ->putItemInSet($artifact, 0)
                                     ->putItemInSet($artifact, 0)
                                     ->getCharacterFactory()
                                     ->inventoryManagement()
                                     ->giveItem($artifact)
                                     ->getCharacterFactory()
                                     ->getCharacter(false);

        $artifactSlot = $character->inventory->slots->where('item_id', $artifact->id)->first()->id;

        $artifactResponse = $this->actingAs($character->user)->json('post', '/api/character/'.$character->id.'/inventory/move-to-set', [
            'slot_id' => $artifactSlot,
            'move_to_set' => $character->inventorySets()->first()->id,
        ])->response;

        $this->assertEquals(200, $artifactResponse->status());

        $character = $character->refresh();

        $this->assertNotNull($character->inventorySets()->where('can_be_equipped', false)->first());
    }

    public function testCanMoveItemToSetAndCauseItToBeNotEquippableArmour() {
        $leggings = $this->createItem([
            'name'                => 'Sample Item',
            'type'                => 'leggings',
            'default_position'    => 'leggings',
            'crafting_type'       => 'armour'
        ]);

        $character = $this->character->inventorySetManagement()
                                     ->createInventorySets(10)
                                     ->putItemInSet($leggings, 0)
                                     ->getCharacterFactory()
                                     ->inventoryManagement()
                                     ->giveItem($leggings)
                                     ->getCharacterFactory()
                                     ->getCharacter(false);

        $leggingsSlot = $character->inventory->slots->where('item_id', $leggings->id)->first()->id;

        $leggingsResponse = $this->actingAs($character->user)->json('post', '/api/character/'.$character->id.'/inventory/move-to-set', [
            'slot_id' => $leggingsSlot,
            'move_to_set' => $character->inventorySets()->first()->id,
        ])->response;

        $this->assertEquals(200, $leggingsResponse->status());

        $character = $character->refresh();

        $this->assertNotNull($character->inventorySets()->where('can_be_equipped', false)->first());

    }

    public function testCanMoveItemToSetAndCauseItToBeNotEquippableWeapon() {
        $weapon = $this->createItem([
            'name'                => 'Sample Item',
            'type'                => 'weapon',
        ]);

        $character = $this->character->inventorySetManagement()
                                     ->createInventorySets(10)
                                     ->putItemInSet($weapon, 0)
                                     ->putItemInSet($weapon, 0)
                                     ->getCharacterFactory()
                                     ->inventoryManagement()
                                     ->giveItem($weapon)
                                     ->getCharacterFactory()
                                     ->getCharacter(false);

        $weaponSlot            = $character->inventory->slots->where('item_id', $weapon->id)->first()->id;

        $weaponResponse = $this->actingAs($character->user)->json('post', '/api/character/'.$character->id.'/inventory/move-to-set', [
            'slot_id' => $weaponSlot,
            'move_to_set' => $character->inventorySets()->first()->id,
        ])->response;

        $this->assertEquals(200, $weaponResponse->status());

        $character = $character->refresh();

        $this->assertNotNull($character->inventorySets()->where('can_be_equipped', false)->first());
    }

    public function testCanMoveItemToSetAndCauseItToBeNotEquippableShieldAndWeapons() {
        $shield = $this->createItem([
            'name'                => 'Sample Item',
            'type'                => 'shield',
        ]);

        $weapon = $this->createItem([
            'name'                => 'Sample Item',
            'type'                => 'shield',
        ]);

        $character = $this->character->inventorySetManagement()
            ->createInventorySets(10)
            ->putItemInSet($shield, 0)
            ->putItemInSet($weapon, 0)
            ->getCharacterFactory()
            ->inventoryManagement()
            ->giveItem($weapon)
            ->getCharacterFactory()
            ->getCharacter(false);

        $weaponSlot            = $character->inventory->slots->where('item_id', $weapon->id)->first()->id;

        $weaponResponse = $this->actingAs($character->user)->json('post', '/api/character/'.$character->id.'/inventory/move-to-set', [
            'slot_id' => $weaponSlot,
            'move_to_set' => $character->inventorySets()->first()->id,
        ])->response;

        $this->assertEquals(200, $weaponResponse->status());

        $character = $character->refresh();

        $this->assertNotNull($character->inventorySets()->where('can_be_equipped', false)->first());
    }

    public function testCanMoveItemToSetAndCauseItToBeNotEquippableMultipleShieldAndWeapons() {
        $shield = $this->createItem([
            'name'                => 'Sample Item',
            'type'                => 'shield',
        ]);

        $weapon = $this->createItem([
            'name'                => 'Sample Item',
            'type'                => 'shield',
        ]);

        $character = $this->character->inventorySetManagement()
            ->createInventorySets(10)
            ->putItemInSet($shield, 0)
            ->putItemInSet($weapon, 0)
            ->getCharacterFactory()
            ->inventoryManagement()
            ->giveItem($shield)
            ->getCharacterFactory()
            ->getCharacter(false);

        $shieldSlot            = $character->inventory->slots->where('item_id', $shield->id)->first()->id;

        $shieldResponse = $this->actingAs($character->user)->json('post', '/api/character/'.$character->id.'/inventory/move-to-set', [
            'slot_id' => $shieldSlot,
            'move_to_set' => $character->inventorySets()->first()->id,
        ])->response;

        $this->assertEquals(200, $shieldResponse->status());

        $character = $character->refresh();

        $this->assertNotNull($character->inventorySets()->where('can_be_equipped', false)->first());
    }

    public function testCanMoveItemToSetAndCauseItToBeNotEquippableWeaponAndBow() {
        $bow = $this->createItem([
            'name'                => 'Sample Item',
            'type'                => 'bow',
        ]);

        $weapon = $this->createItem([
            'name'                => 'Sample Item',
            'type'                => 'weapon',
        ]);

        $character = $this->character->inventorySetManagement()
            ->createInventorySets(10)
            ->putItemInSet($weapon, 0)
            ->getCharacterFactory()
            ->inventoryManagement()
            ->giveItem($bow)
            ->getCharacterFactory()
            ->getCharacter(false);

        $bowSlot            = $character->inventory->slots->where('item_id', $bow->id)->first()->id;

        $bowResponse = $this->actingAs($character->user)->json('post', '/api/character/'.$character->id.'/inventory/move-to-set', [
            'slot_id' => $bowSlot,
            'move_to_set' => $character->inventorySets()->first()->id,
        ])->response;

        $this->assertEquals(200, $bowResponse->status());

        $character = $character->refresh();

        $this->assertNotNull($character->inventorySets()->where('can_be_equipped', false)->first());
    }

    public function testCanMoveItemToSetAndCauseItToBeNotEquippableMultipleBows() {
        $bow = $this->createItem([
            'name'                => 'Sample Item',
            'type'                => 'bow',
        ]);

        $character = $this->character->inventorySetManagement()
            ->createInventorySets(10)
            ->putItemInSet($bow, 0)
            ->getCharacterFactory()
            ->inventoryManagement()
            ->giveItem($bow)
            ->getCharacterFactory()
            ->getCharacter(false);

        $bowSlot            = $character->inventory->slots->where('item_id', $bow->id)->first()->id;

        $bowResponse = $this->actingAs($character->user)->json('post', '/api/character/'.$character->id.'/inventory/move-to-set', [
            'slot_id' => $bowSlot,
            'move_to_set' => $character->inventorySets()->first()->id,
        ])->response;

        $this->assertEquals(200, $bowResponse->status());

        $character = $character->refresh();

        $this->assertNotNull($character->inventorySets()->where('can_be_equipped', false)->first());
    }

    public function testCanMoveItemToSetAndCauseItToBeNotEquippableBowAndShield() {
        $bow = $this->createItem([
            'name'                => 'Sample Item',
            'type'                => 'bow',
        ]);

        $shield = $this->createItem([
            'name'                => 'Sample Item',
            'type'                => 'bow',
        ]);

        $character = $this->character->inventorySetManagement()
            ->createInventorySets(10)
            ->putItemInSet($shield, 0)
            ->getCharacterFactory()
            ->inventoryManagement()
            ->giveItem($bow)
            ->getCharacterFactory()
            ->getCharacter(false);

        $bowSlot            = $character->inventory->slots->where('item_id', $bow->id)->first()->id;

        $bowResponse = $this->actingAs($character->user)->json('post', '/api/character/'.$character->id.'/inventory/move-to-set', [
            'slot_id' => $bowSlot,
            'move_to_set' => $character->inventorySets()->first()->id,
        ])->response;

        $this->assertEquals(200, $bowResponse->status());

        $character = $character->refresh();

        $this->assertNotNull($character->inventorySets()->where('can_be_equipped', false)->first());
    }

    public function testCannotMoveItemToSet() {
        $character = $this->character->inventorySetManagement()->createInventorySets(10)->getCharacter(false);

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

        $character = $this->character->inventorySetManagement()
                                     ->createInventorySets(10)
                                     ->putItemInSet($item, 0)
                                     ->putItemInSet($this->createItem(['type' => 'ring']), 0)
                                     ->putItemInSet($this->createItem(['type' => 'spell-healing']), 0)
                                     ->putItemInSet($this->createItem(['type' => 'artifact']), 0)
                                     ->putItemInSet($this->createItem(['default_position' => 'leggings', 'type' => 'leggings']), 0)
                                     ->getCharacter(false);

        $this->actingAs($character->user)->json('post', '/api/character/'.$character->id.'/inventory-set/equip/'.$character->inventorySets()->first()->id);

        $character = $character->refresh();

        $this->assertTrue($character->inventorySets()->where('is_equipped', true)->get()->isNotEmpty());
    }

    public function testCanEquipSetWhileAnotherSetIsEquipped() {
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

        $character = $this->character->inventorySetManagement()
            ->createInventorySets(10)
            ->putItemInSet($item, 0)
            ->putItemInSet($this->createItem(['type' => 'shield']), 0)
            ->putItemInSet($this->createItem(['type' => 'ring']), 0)
            ->putItemInSet($this->createItem(['type' => 'spell-healing']), 0)
            ->putItemInSet($this->createItem(['type' => 'artifact']), 0)
            ->putItemInSet($this->createItem(['default_position' => 'leggings', 'type' => 'leggings']), 0)
            ->putItemInSet($this->createItem(['type' => 'ring']), 1, 'ring-one', true)
            ->putItemInSet($this->createItem(['type' => 'spell-healing']), 1, 'spell-one', true)
            ->putItemInSet($this->createItem(['type' => 'artifact']), 1, 'artifact-one', true)
            ->putItemInSet($this->createItem(['default_position' => 'leggings', 'type' => 'leggings']), 1, 'leggings', true)
            ->getCharacter(false);

        $this->actingAs($character->user)->json('post', '/api/character/'.$character->id.'/inventory-set/equip/'.$character->inventorySets()->first()->id);

        $character = $character->refresh();

        $this->assertTrue($character->inventorySets()->where('is_equipped', true)->get()->isNotEmpty());
    }

    public function testCanEquipSetWithBow() {
        $item = $this->createItem([
            'name'                => 'Sample Item',
            'type'                => 'bow',
            'base_damage'         => 200,
            'cost'                => 100,
            'crafting_type'       => 'weapon',
            'description'         => 'sample',
            'can_resurrect'       => false,
            'resurrection_chance' => 0.0
        ]);

        $character = $this->character->inventorySetManagement()
            ->createInventorySets(10)
            ->putItemInSet($item, 0)
            ->putItemInSet($this->createItem(['type' => 'ring']), 0)
            ->putItemInSet($this->createItem(['type' => 'spell-healing']), 0)
            ->putItemInSet($this->createItem(['type' => 'artifact']), 0)
            ->putItemInSet($this->createItem(['default_position' => 'leggings', 'type' => 'leggings']), 0)
            ->getCharacter(false);

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
        ])->getCharacter(false);

        $response = $this->actingAs($character->user)->json('post', '/api/character/'.$character->id.'/inventory-set/equip/'.$character->inventorySets()->first()->id)->response;

        $this->assertEquals(422, $response->status());

        $character = $character->refresh();

        $this->assertFalse($character->inventorySets()->where('is_equipped', true)->get()->isNotEmpty());
    }

    public function testCanSaveEquipmentAsSet() {
        $this->character->inventorySetManagement()->createInventorySets(10);

        $character = $this->character->getCharacter(false);

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

        $character = $this->character->inventorySetManagement()->createInventorySets(10)->putItemInSet($item, 0)->getCharacter(false);

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

        $character = $character->updateCharacter(['inventory_max' => 0])->getCharacter(false);

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

        $character = $this->character->inventorySetManagement()->createInventorySets(10)->putItemInSet($item, 0)->getCharacter(false);

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

        $character = $this->character->inventorySetManagement()->createInventorySets(10)->putItemInSet($item, 0, 'left-hand', true)->getCharacter(false);

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
        ])->inventorySetManagement()->createInventorySets(10)->putItemInSet($item, 0, 'left-hand', true)->getCharacter(false);

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

        $character = $this->character->inventorySetManagement()->createInventorySets(10)->putItemInSet($item, 0)->getCharacter(false);

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

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->inventoryManagement()->giveItem($item)->getCharacter(false);

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

        $character = (new CharacterFactory)->createBaseCharacter()->inventoryManagement()->giveItem($item)->getCharacter(false);

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

        $character = (new CharacterFactory)->createBaseCharacter()->inventoryManagement()->giveItem($item)->getCharacter(false);

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

        $character = (new CharacterFactory)->createBaseCharacter()->inventoryManagement()->getCharacter(false);

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

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->inventoryManagement()->giveItem($item)->giveItem($item)->giveItem($item)->getCharacter(false);
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

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->inventoryManagement()->giveItem($item)->getCharacter(false);

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

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->inventoryManagement()->giveItem($item)->getCharacter(false);

        $character = $character->refresh();

        $response = $this->actingAs($character->user)->json('post', '/api/character/'.$character->id.'/inventory/use-many', [
            'slot_ids' => [$character->inventory->slots->filter(function($slot) { return $slot->item->usable; })->first()->id]
        ])->response;

        $this->assertEquals(200, $response->status());
    }

    public function testUseManyItemsThatDoesntExist() {
        $character = $this->character->getCharacter(false);

        $response = $this->actingAs($character->user)->json('post', '/api/character/'.$character->id.'/inventory/use-many', [
            'slot_ids' => 678
        ])->response;

        $this->assertEquals(422, $response->status());
    }
}
