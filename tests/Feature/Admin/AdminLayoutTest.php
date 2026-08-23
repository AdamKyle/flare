<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
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

    public function test_admin_layout_source_does_not_load_livewire_or_livewire_tables_entries(): void
    {
        $layoutSource = File::get(resource_path('views/layouts/admin.blade.php'));

        $this->assertStringNotContainsString('@livewireStyles', $layoutSource);
        $this->assertStringNotContainsString('@livewireScriptConfig', $layoutSource);
        $this->assertStringNotContainsString('resources/js/vendor/livewire.js', $layoutSource);
        $this->assertStringNotContainsString('resources/js/vendor/livewire-data-tables.js', $layoutSource);
    }

    public function test_admin_view_extends_admin_layout(): void
    {
        $adminViewSource = File::get(resource_path('views/admin/home.blade.php'));

        $this->assertStringContainsString("@extends('layouts.admin')", $adminViewSource);
    }
}
