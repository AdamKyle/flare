<?php

namespace Tests\Feature\Admin\Npcs;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class NpcsLegacyRouteTest extends TestCase
{
    use CreateNpc, CreateRole, CreateUser, RefreshDatabase;

    public function test_legacy_npc_edit_route_redirects_into_the_modern_app(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $npc = $this->createNpc();

        $response = $this->actingAs($admin)->call('GET', '/admin/npcs/edit/'.$npc->id);

        $this->assertSame(302, $response->getStatusCode());
        $response->assertRedirect(route('admin.npcs.index'));
    }

    public function test_legacy_npc_show_route_renders_for_admin(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $npc = $this->createNpc();

        $response = $this->actingAs($admin)->call('GET', '/admin/npcs/'.$npc->id);

        $this->assertSame(200, $response->getStatusCode());
        $response->assertSee($npc->real_name);
    }
}
