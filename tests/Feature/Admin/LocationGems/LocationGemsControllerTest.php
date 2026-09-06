<?php

namespace Tests\Feature\Admin\LocationGems;

use App\Admin\LocationGems\Controllers\LocationGemExportController;
use App\Admin\LocationGems\Services\LocationGemExcelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class LocationGemsControllerTest extends TestCase
{
    use CreateRole, CreateUser, RefreshDatabase;

    public function test_export_invokes_the_location_gems_download_boundary(): void
    {
        $response = response()->download(base_path('composer.json'), 'location-gems.xlsx');
        $service = Mockery::mock(LocationGemExcelService::class);
        $service->shouldReceive('export')->once()->andReturn($response);

        $result = (new LocationGemExportController($service))();

        $this->assertSame($response, $result);
    }

    public function test_unauthenticated_user_is_redirected_from_location_gems_index(): void
    {
        $response = $this->call('GET', '/admin/location-gems');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_non_admin_user_is_denied_access_to_location_gems_index(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->call('GET', '/admin/location-gems');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_admin_can_view_location_gems_index(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/admin/location-gems');

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_location_gems_index_mounts_the_admin_app_container(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/admin/location-gems');

        $response->assertSee('id="location-gems-admin-app"', false);
    }
}
