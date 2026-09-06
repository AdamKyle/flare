<?php

namespace Tests\Feature\Admin\Classes;

use App\Admin\Classes\Controllers\ClassExportController;
use App\Admin\Classes\Services\ClassExcelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class ClassesControllerTest extends TestCase
{
    use CreateRole, CreateUser, RefreshDatabase;

    public function test_export_invokes_the_classes_download_boundary(): void
    {
        $response = response()->download(base_path('composer.json'), 'game_classes.xlsx');
        $service = Mockery::mock(ClassExcelService::class);
        $service->shouldReceive('export')->once()->andReturn($response);

        $result = (new ClassExportController($service))();

        $this->assertSame($response, $result);
    }

    public function test_unauthenticated_user_is_redirected_from_classes_index(): void
    {
        $response = $this->call('GET', '/admin/classes');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_non_admin_user_is_denied_access_to_classes_index(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->call('GET', '/admin/classes');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_admin_can_view_classes_index(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/admin/classes');

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_classes_index_mounts_the_admin_app_container(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/admin/classes');

        $response->assertSee('id="classes-admin-app"', false);
    }
}
