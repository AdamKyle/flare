<?php

namespace Tests\Feature\Admin\Items;

use App\Flare\Models\Item;
use App\Game\Core\Items\Values\AlchemyItemType;
use App\Game\Core\Items\Values\ItemCatalogType;
use App\Game\Core\Items\Values\ItemCraftingType;
use App\Game\Core\Items\Values\ItemDefaultPosition;
use App\Game\Core\Items\Values\ItemEffectType;
use App\Game\Core\Items\Values\ItemSpecialtyType;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Items\ItemPayloadFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateInventory;
use Tests\Traits\CreateInventorySlot;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class ItemsApiControllerTest extends TestCase
{
    use CreateCharacter, CreateInventory, CreateInventorySlot, CreateItem, CreateMonster, CreateRole, CreateUser, RefreshDatabase;

    public function test_index_rejects_unauthenticated_request(): void
    {
        $response = $this->call('GET', '/api/admin/items?profile=all', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_index_rejects_non_admin_request(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->call('GET', '/api/admin/items?profile=all', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_index_rejects_invalid_profile(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call(
            'GET',
            '/api/admin/items?profile=not-a-real-profile',
            [], [], [], ['HTTP_ACCEPT' => 'application/json'],
        );

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_index_rejects_sort_key_not_allowed_for_profile(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call(
            'GET',
            '/api/admin/items?profile=rings&sort_key=base_damage',
            [], [], [], ['HTTP_ACCEPT' => 'application/json'],
        );

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_weapons_profile_only_includes_valid_weapon_types(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->createItem(['name' => 'Iron Sword', 'type' => 'sword']);
        $this->createItem(['name' => 'Silver Ring', 'type' => 'ring']);
        $this->createItem(['name' => 'Fire Bolt', 'type' => 'spell-damage']);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/items?profile=weapons');
        $data = json_decode($response->getContent(), true);
        $names = array_column($data['data'], 'name');

        $this->assertContains('Iron Sword', $names);
        $this->assertNotContains('Silver Ring', $names);
        $this->assertNotContains('Fire Bolt', $names);
    }

    public function test_quest_items_profile_only_includes_quest_type(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->createItem(['name' => 'Relic', 'type' => 'quest']);
        $this->createItem(['name' => 'Steel Sword', 'type' => 'weapon']);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/items?profile=quest-items');
        $data = json_decode($response->getContent(), true);
        $names = array_column($data['data'], 'name');

        $this->assertContains('Relic', $names);
        $this->assertNotContains('Steel Sword', $names);
    }

    public function test_specialty_profile_only_includes_non_null_specialty_type(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->createItem(['name' => 'Hell Forged Blade', 'type' => 'weapon', 'specialty_type' => 'Hell Forged']);
        $this->createItem(['name' => 'Plain Sword', 'type' => 'weapon', 'specialty_type' => null]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/items?profile=specialty');
        $data = json_decode($response->getContent(), true);
        $names = array_column($data['data'], 'name');

        $this->assertContains('Hell Forged Blade', $names);
        $this->assertNotContains('Plain Sword', $names);
    }

    public function test_index_excludes_affixed_generated_items(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->createItem(['name' => 'Catalog Item', 'type' => 'weapon']);
        $this->createItem(['name' => 'Affixed Item', 'type' => 'weapon', 'item_prefix_id' => 1]);
        $this->createItem(['name' => 'Suffixed Item', 'type' => 'weapon', 'item_suffix_id' => 1]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/items?profile=all');
        $data = json_decode($response->getContent(), true);
        $names = array_column($data['data'], 'name');

        $this->assertContains('Catalog Item', $names);
        $this->assertNotContains('Affixed Item', $names);
        $this->assertNotContains('Suffixed Item', $names);
    }

    public function test_index_excludes_generated_child_items_with_parent_id(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $parent = $this->createItem(['name' => 'Catalog Parent', 'type' => 'weapon']);
        $this->createItem(['name' => 'Generated Child', 'type' => 'weapon', 'parent_id' => $parent->id]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/items?profile=all');
        $data = json_decode($response->getContent(), true);
        $names = array_column($data['data'], 'name');

        $this->assertContains('Catalog Parent', $names);
        $this->assertNotContains('Generated Child', $names);
    }

    public function test_show_resolves_quest_presentation_kind(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $item = $this->createItem(['name' => 'Relic', 'type' => 'quest', 'usable' => false]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/items/'.$item->id);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('quest', $data['presentation_kind']);
        $this->assertSame($item->id, $data['presentation']['item_id']);
    }

    public function test_show_resolves_usable_presentation_kind(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $item = $this->createItem(['name' => 'Potion', 'type' => 'alchemy', 'usable' => true]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/items/'.$item->id);
        $data = json_decode($response->getContent(), true);

        $this->assertSame('usable', $data['presentation_kind']);
        $this->assertSame($item->id, $data['presentation']['item_id']);
    }

    public function test_show_resolves_equippable_presentation_kind_by_default(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $item = $this->createItem(['name' => 'Iron Sword', 'type' => 'sword', 'usable' => false]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/items/'.$item->id);
        $data = json_decode($response->getContent(), true);

        $this->assertSame('equippable', $data['presentation_kind']);
        $this->assertSame($item->id, $data['presentation']['id']);
    }

    public function test_store_persists_valid_item(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('POST', '/api/admin/items', (new ItemPayloadFactory)->valid([
            'name' => 'Created Item',
        ]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('Created Item', $data['name']);
        $this->assertDatabaseHas('items', ['name' => 'Created Item']);
    }

    public function test_store_requires_name_and_type(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $payload = (new ItemPayloadFactory)->valid();
        unset($payload['name'], $payload['type']);

        $response = $this->actingAs($admin)->call(
            'POST',
            '/api/admin/items',
            $payload, [], [], ['HTTP_ACCEPT' => 'application/json'],
        );

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_options_returns_closed_domain_values_without_presentation_labels(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/api/admin/items/options');
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(
            array_map(fn (ItemCatalogType $type): string => $type->value, ItemCatalogType::cases()),
            $data['types']
        );
        $this->assertSame(
            array_map(fn (ItemDefaultPosition $type): string => $type->value, ItemDefaultPosition::cases()),
            $data['default_positions']
        );
        $this->assertSame(
            array_map(fn (ItemCraftingType $type): string => $type->value, ItemCraftingType::cases()),
            $data['crafting_types']
        );
        $this->assertSame(
            array_map(fn (AlchemyItemType $type): string => $type->value, AlchemyItemType::cases()),
            $data['alchemy_types']
        );
        $this->assertSame(
            array_map(fn (ItemSpecialtyType $type): string => $type->value, ItemSpecialtyType::cases()),
            $data['specialty_types']
        );
        $this->assertSame(
            array_map(fn (ItemEffectType $type): string => $type->value, ItemEffectType::cases()),
            $data['effects']
        );
        $this->assertSame(
            array_map(fn (SkillTypeValue $type): int => $type->value, SkillTypeValue::cases()),
            $data['skill_types']
        );
    }

    public function test_store_rejects_invalid_crafting_type(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $payload = (new ItemPayloadFactory)->valid([
            'can_craft' => true,
            'crafting_type' => 'invalid-crafting-type',
        ]);

        $response = $this->actingAs($admin)->call(
            'POST',
            '/api/admin/items',
            $payload, [], [], ['HTTP_ACCEPT' => 'application/json'],
        );
        $data = json_decode($response->getContent(), true);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertArrayHasKey('crafting_type', $data['errors']);
    }

    public function test_store_rejects_invalid_skill_type(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $payload = (new ItemPayloadFactory)->valid([
            'affects_skill_type' => 999,
        ]);

        $response = $this->actingAs($admin)->call(
            'POST',
            '/api/admin/items',
            $payload, [], [], ['HTTP_ACCEPT' => 'application/json'],
        );
        $data = json_decode($response->getContent(), true);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertArrayHasKey('affects_skill_type', $data['errors']);
    }

    public function test_store_normalizes_incompatible_usable_only_fields(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('POST', '/api/admin/items', (new ItemPayloadFactory)->valid([
            'name' => 'Not Usable Item',
            'usable' => false,
            'lasts_for' => 500,
            'damages_kingdoms' => true,
            'kingdom_damage' => 10,
        ]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertNull($data['lasts_for']);
        $this->assertFalse($data['damages_kingdoms']);
        $this->assertNull(Item::find($data['id'])->kingdom_damage);
    }

    public function test_store_normalizes_holy_level_when_cannot_use_on_other_items(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('POST', '/api/admin/items', (new ItemPayloadFactory)->valid([
            'name' => 'No Holy Use Item',
            'can_use_on_other_items' => false,
            'holy_level' => 3,
        ]));
        $data = json_decode($response->getContent(), true);

        $this->assertNull($data['holy_level']);
    }

    public function test_store_normalizes_crafting_only_fields_when_not_craftable(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('POST', '/api/admin/items', (new ItemPayloadFactory)->valid([
            'name' => 'Not Craftable Item',
            'can_craft' => false,
            'crafting_type' => 'weapon',
            'skill_level_required' => 10,
            'skill_level_trivial' => 20,
        ]));
        $data = json_decode($response->getContent(), true);

        $this->assertNull($data['crafting_type']);
        $this->assertFalse($data['craft_only']);
        $this->assertNull($data['skill_level_required']);
        $this->assertNull($data['skill_level_trivial']);
    }

    public function test_update_modifies_the_item(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $item = $this->createItem(['name' => 'Original Name']);

        $response = $this->actingAs($admin)->call('PUT', '/api/admin/items/'.$item->id, (new ItemPayloadFactory)->valid([
            'name' => 'Updated Name',
        ]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Updated Name', $data['name']);
    }

    public function test_edit_returns_current_form_values(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $item = $this->createItem(['name' => 'Edit Target']);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/items/'.$item->id.'/edit');
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Edit Target', $data['name']);
        $this->assertArrayNotHasKey('item_prefix_id', $data);
        $this->assertArrayNotHasKey('parent_id', $data);
    }

    public function test_destroy_deletes_dependency_free_item(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $item = $this->createItem(['name' => 'Safe To Delete']);

        $response = $this->actingAs($admin)->call('DELETE', '/api/admin/items/'.$item->id);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertDatabaseMissing('items', ['id' => $item->id]);
    }

    public function test_destroy_blocks_deletion_of_item_held_in_inventory(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $item = $this->createItem(['name' => 'Held Item']);
        $character = $this->createCharacter(['user_id' => $this->createUser()->id]);
        $this->createInventorySlot(['item_id' => $item->id, 'inventory_id' => $this->createInventory(['character_id' => $character->id])->id]);

        $response = $this->actingAs($admin)->call('DELETE', '/api/admin/items/'.$item->id);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(409, $response->getStatusCode());
        $this->assertNotEmpty($data['blockers']);
        $this->assertDatabaseHas('items', ['id' => $item->id]);
    }

    public function test_destroy_blocks_deletion_of_item_referenced_by_monster_quest_drop(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $item = $this->createItem(['name' => 'Monster Quest Drop', 'type' => 'quest']);
        $this->createMonster(['quest_item_id' => $item->id]);

        $response = $this->actingAs($admin)->call('DELETE', '/api/admin/items/'.$item->id);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(409, $response->getStatusCode());
        $this->assertNotEmpty($data['blockers']);
        $this->assertDatabaseHas('items', ['id' => $item->id]);
    }
}
