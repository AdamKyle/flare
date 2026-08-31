<?php

namespace Tests\Feature\Admin\Items;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class ItemQuestItemMonsterDropsTest extends TestCase
{
    use CreateItem, CreateMonster, CreateRole, CreateUser, RefreshDatabase;

    public function test_quest_item_detail_returns_every_monster_that_drops_it_in_deterministic_order(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $questItem = $this->createItem(['type' => 'quest', 'usable' => false]);

        $this->createMonster(['name' => 'Zephyr Wraith', 'quest_item_id' => $questItem->id]);
        $this->createMonster(['name' => 'Ash Golem', 'quest_item_id' => $questItem->id]);
        $this->createMonster(['name' => 'Ember Fox']);

        $response = $this->actingAs($admin)->call(
            'GET',
            "/api/admin/items/{$questItem->id}",
            [], [], [], ['HTTP_ACCEPT' => 'application/json'],
        );

        $response->assertJsonPath('presentation.required_monsters.0.name', 'Ash Golem');
        $response->assertJsonPath('presentation.required_monsters.1.name', 'Zephyr Wraith');
        $response->assertJsonCount(2, 'presentation.required_monsters');
    }
}
