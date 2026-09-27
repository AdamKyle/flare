<?php

namespace Tests\Feature\Admin\Kingdoms;

use App\Admin\Kingdoms\Controllers\KingdomExportController;
use App\Admin\Kingdoms\Services\KingdomExcelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class BuildingsControllerTest extends TestCase
{
    use CreateRole, CreateUser, RefreshDatabase;

    public function test_export_invokes_the_kingdom_workbook_download_boundary(): void
    {
        $response = response()->download(base_path('composer.json'), 'kingdoms.xlsx');
        $service = Mockery::mock(KingdomExcelService::class);
        $service->shouldReceive('export')->once()->andReturn($response);

        $result = (new KingdomExportController($service))();

        $this->assertSame($response, $result);
    }

    public function test_unauthenticated_user_is_redirected_from_buildings_index(): void
    {
        $response = $this->call('GET', '/admin/kingdoms/buildings');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_non_admin_user_is_denied_access_to_buildings_index(): void
    {
        $response = $this->actingAs($this->createUser())->call('GET', '/admin/kingdoms/buildings');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_buildings_index_mounts_the_admin_app_container(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/admin/kingdoms/buildings');

        $response->assertOk();
        $response->assertSee('id="kingdom-buildings-admin-app"', false);
    }
}
