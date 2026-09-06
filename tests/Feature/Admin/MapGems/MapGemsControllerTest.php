<?php

namespace Tests\Feature\Admin\MapGems;

use App\Admin\MapGems\Controllers\MapGemExportController;
use App\Admin\MapGems\Services\MapGemExcelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class MapGemsControllerTest extends TestCase
{
    use CreateRole, CreateUser, RefreshDatabase;

    public function test_export_invokes_the_map_gems_download_boundary(): void
    {
        $response = response()->download(base_path('composer.json'), 'map-gems.xlsx');
        $service = Mockery::mock(MapGemExcelService::class);
        $service->shouldReceive('export')->once()->andReturn($response);

        $result = (new MapGemExportController($service))();

        $this->assertSame($response, $result);
    }

    public function test_unauthenticated_user_is_redirected_from_map_gems_index(): void
    {
        $response = $this->call('GET', '/admin/map-gems');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_non_admin_user_is_denied_access_to_map_gems_index(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->call('GET', '/admin/map-gems');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_admin_can_view_map_gems_index(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/admin/map-gems');

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_map_gems_index_mounts_the_admin_app_container(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/admin/map-gems');

        $response->assertSee('id="map-gems-admin-app"', false);
    }
}
