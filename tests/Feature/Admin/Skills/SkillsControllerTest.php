<?php

namespace Tests\Feature\Admin\Skills;

use App\Admin\Skills\Controllers\SkillExportController;
use App\Admin\Skills\Services\SkillExcelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class SkillsControllerTest extends TestCase
{
    use CreateRole, CreateUser, RefreshDatabase;

    public function test_export_invokes_the_skills_download_boundary(): void
    {
        $response = response()->download(base_path('composer.json'), 'skills.xlsx');
        $service = Mockery::mock(SkillExcelService::class);
        $service->shouldReceive('export')->once()->andReturn($response);

        $result = (new SkillExportController($service))();

        $this->assertSame($response, $result);
    }

    public function test_unauthenticated_user_is_redirected_from_skills_index(): void
    {
        $response = $this->call('GET', '/admin/skills');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_non_admin_user_is_denied_access_to_skills_index(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->call('GET', '/admin/skills');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_skills_index_mounts_the_admin_app_container(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/admin/skills');

        $response->assertOk();
        $response->assertSee('id="skills-admin-app"', false);
    }
}
