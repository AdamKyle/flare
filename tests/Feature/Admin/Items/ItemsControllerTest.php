<?php

namespace Tests\Feature\Admin\Items;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class ItemsControllerTest extends TestCase
{
    use CreateRole, CreateUser, RefreshDatabase;

    public function test_unauthenticated_user_is_redirected_from_items_index(): void
    {
        $response = $this->call('GET', '/admin/items');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_non_admin_user_is_denied_access_to_items_index(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->call('GET', '/admin/items');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_admin_can_view_items_index(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/admin/items');

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_items_index_mounts_the_admin_app_container(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/admin/items');

        $response->assertSee('id="items-admin-app"', false);
    }
}
