<?php

namespace Tests\Feature\Admin\Statistics;

use App\Flare\Models\Character;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class AdminStatisticsDashboardControllerTest extends TestCase
{
    use CreateRole, CreateUser, RefreshDatabase;

    public function test_non_admin_users_cannot_access_dashboard_json_endpoint(): void
    {
        $response = $this->actingAs($this->createUser())
            ->call('GET', '/api/admin/statistics/dashboard-data', [], [], [], [
                'HTTP_ACCEPT' => 'application/json',
            ]);

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_admin_users_can_access_dashboard_json_endpoint(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        Character::factory()->create(['user_id' => $admin->id, 'name' => 'Controller Character']);

        $response = $this->actingAs($admin)
            ->call('GET', '/api/admin/statistics/dashboard-data');
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertArrayHasKey('generated_at', $data);
        $this->assertArrayHasKey('summary', $data);
        $this->assertArrayHasKey('login_chart', $data);
        $this->assertArrayHasKey('registration_chart', $data);
        $this->assertArrayHasKey('login_duration_chart', $data);
        $this->assertArrayHasKey('today_login_count_chart', $data);
        $this->assertArrayHasKey('login_participation_summary', $data);
        $this->assertArrayHasKey('login_participation_chart', $data);
        $this->assertArrayHasKey('inactive_user_deletion_summary', $data);
        $this->assertArrayHasKey('online_characters', $data);
        $this->assertArrayHasKey('reincarnation_chart', $data);
        $this->assertArrayHasKey('quest_completion_chart', $data);
        $this->assertArrayHasKey('guide_quest_completion_chart', $data);
        $this->assertArrayHasKey('gold_chart', $data);
        $this->assertArrayHasKey('kingdom_summary', $data);
        $this->assertArrayHasKey('top_kingdom_holders', $data);
        $this->assertArrayHasKey('metric_definitions', $data);
        $this->assertArrayHasKey('series', $data['login_participation_chart']);
        $this->assertArrayHasKey('series', $data['login_chart']);
    }

    public function test_dashboard_page_renders_react_mount_page(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $this->actingAs($admin)
            ->visit('/admin/statistics/dashboard')
            ->see('administrator-statistics');
    }
}
