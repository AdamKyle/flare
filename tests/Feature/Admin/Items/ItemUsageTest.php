<?php

namespace Tests\Feature\Admin\Items;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateInventory;
use Tests\Traits\CreateInventorySlot;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class ItemUsageTest extends TestCase
{
    use CreateCharacter, CreateInventory, CreateInventorySlot, CreateItem, CreateMonster, CreateRole, CreateUser, RefreshDatabase;

    public function test_usage_reports_deletable_true_and_zero_blocker_categories_for_a_free_item(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $item = $this->createItem(['name' => 'Free Item']);

        $response = $this->actingAs($admin)->call(
            'GET',
            "/api/admin/items/{$item->id}/usage",
            [], [], [], ['HTTP_ACCEPT' => 'application/json'],
        );

        $response->assertJsonPath('deletable', true);
        $response->assertJsonPath('total_blocker_categories', 0);
        $response->assertJsonCount(0, 'blockers');
    }

    public function test_usage_reports_deletable_false_with_blocker_counts_and_related_entities_when_referenced(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $item = $this->createItem(['name' => 'Referenced Item', 'type' => 'quest']);
        $character = $this->createCharacter(['user_id' => $this->createUser()->id]);
        $this->createInventorySlot([
            'item_id' => $item->id,
            'inventory_id' => $this->createInventory(['character_id' => $character->id])->id,
        ]);
        $this->createMonster(['name' => 'Drop Monster', 'quest_item_id' => $item->id]);

        $response = $this->actingAs($admin)->call(
            'GET',
            "/api/admin/items/{$item->id}/usage",
            [], [], [], ['HTTP_ACCEPT' => 'application/json'],
        );

        $data = json_decode($response->getContent(), true);
        $blockerKeys = collect($data['blockers'])->pluck('key')->all();

        $this->assertFalse($data['deletable']);
        $this->assertSame(2, $data['total_blocker_categories']);
        $this->assertContains('inventory_slots', $blockerKeys);
        $this->assertContains('monster_drops', $blockerKeys);

        $monsterCategory = collect($data['blockers'])->firstWhere('key', 'monster_drops');

        $this->assertSame(1, $monsterCategory['count']);
        $this->assertSame('Drop Monster', $monsterCategory['related_entities'][0]['name']);
    }
}
