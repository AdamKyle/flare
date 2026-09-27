<?php

namespace Tests\Feature\Admin\PassiveSkills;

use App\Admin\PassiveSkills\Controllers\PassiveSkillExportController;
use App\Admin\PassiveSkills\Services\PassiveSkillExcelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class PassiveSkillsControllerTest extends TestCase
{
    use CreateRole, CreateUser, RefreshDatabase;

    public function test_export_invokes_the_passive_skills_download_boundary(): void
    {
        $response = response()->download(base_path('composer.json'), 'passive_skills.xlsx');
        $service = Mockery::mock(PassiveSkillExcelService::class);
        $service->shouldReceive('export')->once()->andReturn($response);

        $result = (new PassiveSkillExportController($service))();

        $this->assertSame($response, $result);
    }

    public function test_unauthenticated_user_is_redirected_from_passive_skills_index(): void
    {
        $response = $this->call('GET', '/admin/passive-skills');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_non_admin_user_is_denied_access_to_passive_skills_index(): void
    {
        $response = $this->actingAs($this->createUser())->call('GET', '/admin/passive-skills');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_passive_skills_index_mounts_the_admin_app_container(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/admin/passive-skills');

        $response->assertOk();
        $response->assertSee('id="passive-skills-admin-app"', false);
    }
}
