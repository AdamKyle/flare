<?php

namespace Tests\Feature\Admin\ClassMasteries;

use App\Admin\ClassMasteries\Controllers\ClassMasteryExportController;
use App\Admin\ClassMasteries\Services\ClassMasteryExcelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class ClassMasteriesControllerTest extends TestCase
{
    use CreateRole, CreateUser, RefreshDatabase;

    public function test_export_invokes_the_class_masteries_download_boundary(): void
    {
        $response = response()->download(base_path('composer.json'), 'class-masteries.xlsx');
        $service = Mockery::mock(ClassMasteryExcelService::class);
        $service->shouldReceive('export')->once()->andReturn($response);

        $result = (new ClassMasteryExportController($service))();

        $this->assertSame($response, $result);
    }

    public function test_unauthenticated_user_is_redirected_from_class_masteries_index(): void
    {
        $response = $this->call('GET', '/admin/class-masteries');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_non_admin_user_is_denied_access_to_class_masteries_index(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->call('GET', '/admin/class-masteries');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_admin_can_view_class_masteries_index(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/admin/class-masteries');

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_class_masteries_index_mounts_the_admin_app_container(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/admin/class-masteries');

        $response->assertSee('id="class-masteries-admin-app"', false);
    }
}
