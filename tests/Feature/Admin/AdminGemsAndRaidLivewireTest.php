<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGameLocationGemParamter;
use Tests\Traits\CreateGameMapGemParamter;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateRaid;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class AdminGemsAndRaidLivewireTest extends TestCase
{
    use CreateGameLocationGemParamter, CreateGameMapGemParamter, CreateItem, CreateLocation, CreateMonster, CreateRaid, CreateRole, CreateUser, RefreshDatabase;

    public function test_admin_map_gems_page_renders_admin_livewire_table(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->createGameMapGemParamter(['name' => 'Ember Shard']);

        $response = $this->actingAs($admin)->call('GET', '/admin/map-gems');

        $response->assertOk();
        $response->assertSee('Ember Shard');
        $this->assertStringContainsString('wire:', $response->getContent());
    }

    public function test_admin_location_gems_page_renders_admin_livewire_table(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->createGameLocationGemParamter(['name' => 'Ember Shard']);

        $response = $this->actingAs($admin)->call('GET', '/admin/location-gems');

        $response->assertOk();
        $response->assertSee('Ember Shard');
        $this->assertStringContainsString('wire:', $response->getContent());
    }

    public function test_admin_raid_show_page_renders_reward_item_table(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $monster = $this->createMonster(['is_raid_boss' => true]);
        $location = $this->createLocation();
        $artifactItem = $this->createItem(['type' => 'artifact']);
        $raid = $this->createRaid([
            'raid_boss_id' => $monster->id,
            'raid_boss_location_id' => $location->id,
            'raid_monster_ids' => [$monster->id],
            'artifact_item_id' => $artifactItem->id,
        ]);

        $response = $this->actingAs($admin)->call('GET', '/admin/raids/'.$raid->id);

        $response->assertOk();
        $this->assertStringContainsString('wire:', $response->getContent());
    }
}
