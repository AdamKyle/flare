<?php

namespace Tests\Feature\Admin\Items;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class ItemExportControllerTest extends TestCase
{
    use CreateRole, CreateUser, RefreshDatabase;

    public function test_export_without_a_profile_fails_validation_instead_of_defaulting_to_all(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/admin/items/export');

        $response->assertInvalid(['profile']);
    }

    public function test_export_with_an_invalid_profile_fails_validation(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/admin/items/export', ['profile' => 'all']);

        $response->assertInvalid(['profile']);
    }

    public function test_export_with_a_valid_profile_downloads_the_items_workbook(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/admin/items/export', ['profile' => 'weapons']);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('items.xlsx', $response->headers->get('content-disposition'));
    }
}
