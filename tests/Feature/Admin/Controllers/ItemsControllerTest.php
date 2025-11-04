<?php

namespace Tests\Feature\Admin\Controllers;

use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\SetSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class ItemsControllerTest extends TestCase
{
    use RefreshDatabase, CreateRole, CreateUser, CreateItem;

    private ?CharacterFactory $characterFactory = null;

    public function setUp(): void
    {
        parent::setUp();

        $role = $this->createAdminRole();
        $admin = $this->createAdmin($role);
        $this->actingAs($admin);

        $this->characterFactory = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->characterFactory = null;
    }

    public function test_index_displays_items_view(): void
    {
        $response = $this->call('GET', route('items.list'));

        $this->assertEquals(200, $response->getStatusCode());

        $view = $response->original;

        $this->assertEquals('admin.items.items', $view->getName());
    }

    public function test_create_displays_manage_with_form_inputs(): void
    {
        $response = $this->call('GET', route('items.create'));

        $this->assertEquals(200, $response->getStatusCode());

        $view = $response->original;

        $this->assertEquals('admin.items.manage', $view->getName());

        $data = $view->getData();

        $this->assertArrayHasKey('item', $data);
        $this->assertArrayHasKey('types', $data);
        $this->assertArrayHasKey('defaultPositions', $data);
        $this->assertArrayHasKey('craftingTypes', $data);
        $this->assertArrayHasKey('skillTypes', $data);
        $this->assertArrayHasKey('effects', $data);
        $this->assertArrayHasKey('specialtyTypes', $data);
        $this->assertArrayHasKey('itemSkills', $data);
        $this->assertArrayHasKey('locations', $data);
        $this->assertArrayHasKey('skills', $data);
        $this->assertArrayHasKey('classes', $data);
    }

    public function test_edit_displays_manage_with_item(): void
    {
        $item = $this->createItem([
            'name' => 'Edit Me',
            'type' => 'weapon',
        ]);

        $response = $this->call('GET', route('items.edit', ['item' => $item->id]));

        $this->assertEquals(200, $response->getStatusCode());

        $view = $response->original;

        $this->assertEquals('admin.items.manage', $view->getName());

        $data = $view->getData();

        $this->assertArrayHasKey('item', $data);
        $this->assertEquals($item->id, $data['item']->id);
    }

    public function test_show_displays_item_information_view(): void
    {
        $item = $this->createItem([
            'name' => 'Show Me',
            'type' => 'weapon',
        ]);

        $response = $this->call('GET', route('items.item', ['item' => $item->id]));

        $this->assertEquals(200, $response->getStatusCode());

        $view = $response->original;

        $this->assertEquals('game.items.item', $view->getName());

        $data = $view->getData();

        $this->assertArrayHasKey('item', $data);
        $this->assertEquals($item->id, $data['item']->id);
    }

    public function test_store_creates_item_and_redirects_to_show(): void
    {
        $payload = $this->validItemPayload([
            'name' => 'Created Via Store',
            'type' => 'weapon',
        ]);

        $response = $this->call('POST', route('item.store'), $payload);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue(Item::where('name', 'Created Via Store')->exists());
        $this->assertTrue(session()->has('success'));
    }

    public function test_store_updates_item_and_redirects_to_show(): void
    {
        $item = $this->createItem([
            'name' => 'Old Item Name',
            'type' => 'weapon',
        ]);

        $payload = $this->validItemPayload([
            'id' => $item->id,
            'name' => 'New Item Name',
            'type' => 'weapon',
        ]);

        $response = $this->call('POST', route('item.store'), $payload);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('New Item Name', Item::find($item->id)->name);
        $this->assertTrue(session()->has('success'));
    }

    public function test_export_items_displays_export_view(): void
    {
        $response = $this->call('GET', route('items.export'));

        $this->assertEquals(200, $response->getStatusCode());

        $view = $response->original;

        $this->assertEquals('admin.items.export', $view->getName());
    }

    public function test_import_items_displays_import_view(): void
    {
        $response = $this->call('GET', route('items.import'));

        $this->assertEquals(200, $response->getStatusCode());

        $view = $response->original;

        $this->assertEquals('admin.items.import', $view->getName());
    }

    public function test_delete_deletes_item_and_related_records_and_redirects_back(): void
    {
        $item = $this->createItem([
            'name' => 'Delete Me',
            'type' => 'weapon',
        ]);

        $child = $this->createItem([
            'name' => 'Child',
            'type' => 'weapon',
            'parent_id' => $item->id,
        ]);

        $this->characterFactory
            ->inventoryManagement()
            ->giveItem($item)
            ->getCharacterFactory()
            ->inventorySetManagement()
            ->createInventorySets(1, true)
            ->putItemInSet($item, 0)
            ->putItemInSet($child, 0)
            ->getCharacterFactory()
            ->inventoryManagement()
            ->giveItem($child)
            ->getCharacter();

        $this->assertTrue(SetSlot::where('item_id', $item->id)->exists());
        $this->assertTrue(SetSlot::where('item_id', $child->id)->exists());

        $response = $this->call('POST', route('items.delete', ['item' => $item->id]), [], [], [], [
            'HTTP_REFERER' => route('items.list'),
        ]);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertNull(Item::find($item->id));
        $this->assertNull(Item::find($child->id));
        $this->assertEquals(0, InventorySlot::where('item_id', $item->id)->count());
        $this->assertEquals(0, SetSlot::where('item_id', $item->id)->count());
        $this->assertEquals(0, InventorySlot::where('item_id', $child->id)->count());
        $this->assertEquals(0, SetSlot::where('item_id', $child->id)->count());
        $this->assertTrue(session()->has('success'));
    }

    public function test_delete_all_with_invalid_first_item_returns_error_and_keeps_items(): void
    {
        $item = $this->createItem([
            'name' => 'Keep Me',
            'type' => 'weapon',
        ]);

        $response = $this->call('POST', route('items.delete.all'), [
            'items' => [9999999, $item->id],
        ], [], [], [
            'HTTP_REFERER' => route('items.list'),
        ]);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue(Item::where('id', $item->id)->exists());
        $this->assertTrue(session()->has('error'));
    }

    public function test_delete_all_deletes_multiple_items_and_redirects_back(): void
    {
        $one = $this->createItem([
            'name' => 'Delete One',
            'type' => 'weapon',
        ]);

        $two = $this->createItem([
            'name' => 'Delete Two',
            'type' => 'weapon',
        ]);

        $response = $this->call('POST', route('items.delete.all'), [
            'items' => [$one->id, $two->id],
        ], [], [], [
            'HTTP_REFERER' => route('items.list'),
        ]);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertNull(Item::find($one->id));
        $this->assertNull(Item::find($two->id));
        $this->assertTrue(session()->has('success'));
    }

    private function validItemPayload(array $overrides = []): array
    {
        $base = [
            'id' => null,
            'name' => 'New Item',
            'type' => 'weapon',
            'alchemy_type' => null,
            'default_position' => null,
            'base_damage' => 0,
            'base_ac' => 0,
            'base_healing' => 0,
            'cost' => 0,
            'gold_dust_cost' => 0,
            'shards_cost' => 0,
            'copper_coin_cost' => 0,
            'base_damage_mod' => 0,
            'description' => 'Item',
            'base_healing_mod' => 0,
            'base_ac_mod' => 0,
            'str_mod' => 0,
            'dur_mod' => 0,
            'dex_mod' => 0,
            'chr_mod' => 0,
            'int_mod' => 0,
            'agi_mod' => 0,
            'focus_mod' => 0,
            'effect' => null,
            'can_craft' => 'false',
            'skill_name' => null,
            'skill_training_bonus' => 0,
            'skill_bonus' => 0,
            'fight_time_out_mod_bonus' => 0,
            'move_time_out_mod_bonus' => 0,
            'skill_level_required' => null,
            'skill_level_trivial' => null,
            'crafting_type' => null,
            'market_sellable' => 'false',
            'can_drop' => 'false',
            'craft_only' => 'false',
            'usable' => 'false',
            'damages_kingdoms' => 'false',
            'kingdom_damage' => null,
            'lasts_for' => null,
            'stat_increase' => 'false',
            'increase_stat_by' => 0,
            'affects_skill_type' => null,
            'can_resurrect' => 'false',
            'resurrection_chance' => null,
            'spell_evasion' => 0,
            'artifact_annulment' => 0,
            'increase_skill_bonus_by' => null,
            'increase_skill_training_bonus_by' => null,
            'healing_reduction' => 0,
            'affix_damage_reduction' => 0,
            'devouring_light' => 0,
            'devouring_darkness' => 0,
            'parent_id' => null,
            'xp_bonus' => 0,
            'ignores_caps' => 'false',
            'can_use_on_other_items' => 'false',
            'holy_level' => null,
            'holy_stacks' => 0,
            'ambush_chance' => 0,
            'ambush_resistance' => 0,
            'counter_chance' => 0,
            'counter_resistance' => 0,
            'is_mythic' => 'false',
            'is_cosmic' => 'false',
            'specialty_type' => null,
            'gold_bars_cost' => 0,
            'can_stack' => 'false',
            'gains_additional_level' => 'false',
            'unlocks_class_id' => null,
            'socket_count' => 0,
            'has_gems_socketed' => 'false',
            'item_skill_id' => null,
        ];

        return array_merge($base, $overrides);
    }
}
