<?php

namespace Tests\Feature\Admin\Races;

use App\Admin\Races\Controllers\RaceExportController;
use App\Admin\Races\Services\RaceExcelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class RacesControllerTest extends TestCase
{
    use CreateRole, CreateUser, RefreshDatabase;

    public function test_export_invokes_the_races_download_boundary(): void
    {
        $response = response()->download(base_path('composer.json'), 'game_races.xlsx');
        $service = Mockery::mock(RaceExcelService::class);
        $service->shouldReceive('export')->once()->andReturn($response);

        $result = (new RaceExportController($service))();

        $this->assertSame($response, $result);
    }

    public function test_unauthenticated_user_is_redirected_from_races_index(): void
    {
        $response = $this->call('GET', '/admin/races');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_non_admin_user_is_denied_access_to_races_index(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->call('GET', '/admin/races');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_admin_can_view_races_index(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/admin/races');

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_races_index_mounts_the_admin_app_container(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/admin/races');

        $response->assertSee('id="races-admin-app"', false);
    }
}
