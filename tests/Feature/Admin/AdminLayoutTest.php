<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class AdminLayoutTest extends TestCase
{
    use CreateRole, CreateUser, RefreshDatabase;

    public function test_admin_page_does_not_render_livewire_script_configuration(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/admin');
        $content = $response->getContent();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringNotContainsString('livewireScriptConfig', $content);
        $this->assertStringNotContainsString('Livewire Styles', $content);
    }

    public function test_admin_page_does_not_render_livewire_asset_references(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/admin');
        $content = $response->getContent();

        $this->assertStringNotContainsString('@livewireStyles', $content);
        $this->assertStringNotContainsString('@livewireScriptConfig', $content);
        $this->assertStringNotContainsString('resources/js/vendor/livewire.js', $content);
        $this->assertStringNotContainsString('resources/js/vendor/livewire-data-tables.js', $content);
    }

    public function test_admin_page_renders_through_the_admin_layout(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/admin');
        $content = $response->getContent();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('id="admin-sidebar"', $content);
        $this->assertStringContainsString('id="admin-sidebar-toggle"', $content);
    }

    public function test_admin_page_contains_no_alpine_directives(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/admin');
        $content = $response->getContent();

        $this->assertStringNotContainsString('x-data', $content);
        $this->assertStringNotContainsString('x-init', $content);
        $this->assertStringNotContainsString('x-cloak', $content);
        $this->assertStringNotContainsString('x-show', $content);
        $this->assertStringNotContainsString('@click', $content);
        $this->assertStringNotContainsString(':class', $content);
    }

    public function test_admin_sidebar_toggle_and_sidebar_are_connected_by_aria_controls(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/admin');
        $content = $response->getContent();

        $this->assertStringContainsString('id="admin-sidebar-toggle"', $content);
        $this->assertStringContainsString('id="admin-sidebar"', $content);
        $this->assertStringContainsString('aria-controls="admin-sidebar"', $content);
    }

    public function test_admin_sidebar_exposes_a_close_button_and_backdrop(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/admin');
        $content = $response->getContent();

        $this->assertStringContainsString('id="admin-sidebar-close"', $content);
        $this->assertStringContainsString('aria-label="Close Admin navigation"', $content);
        $this->assertStringContainsString('id="admin-sidebar-backdrop"', $content);
    }

    public function test_admin_sidebar_contains_a_game_maps_link(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/admin');

        $response->assertSee(route('admin.game-maps.index'), false);
        $response->assertSee('Game Maps');
    }

    public function test_admin_sidebar_does_not_contain_template_placeholder_links(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/admin');
        $content = $response->getContent();

        $this->assertStringNotContainsString('ecommerce.html', $content);
        $this->assertStringNotContainsString('pricing-tables.html', $content);
        $this->assertStringNotContainsString('task-list.html', $content);
    }
}
