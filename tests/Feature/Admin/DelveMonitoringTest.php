<?php

namespace Tests\Feature\Admin;

use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\DelveExploration;
use App\Flare\Models\DelveLog;
use App\Flare\Values\AutomationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class DelveMonitoringTest extends TestCase
{
    use CreateRole, CreateUser, RefreshDatabase;

    public function test_non_admin_cannot_access_delve_monitoring_page(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->call('GET', '/admin/monitoring/delve');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_admin_can_view_delve_monitoring_page(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/admin/monitoring/delve');

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_non_admin_cannot_access_delve_active_api(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->call('GET', '/api/admin/monitoring/delve/active');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_delve_active_api_returns_empty_when_no_active_runners(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/delve/active');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([], $response->json());
    }

    public function test_delve_active_api_returns_active_runner(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE,
            'completed_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/delve/active');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(1, $response->json());
    }

    public function test_delve_active_api_excludes_completed_automations(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE,
            'completed_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/delve/active');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(0, $response->json());
    }

    public function test_delve_active_api_excludes_exploring_automations(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'completed_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/delve/active');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(0, $response->json());
    }

    public function test_delve_runs_api_returns_paginated_results(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        DelveExploration::factory()->create(['character_id' => $character->id]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/delve/runs');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertArrayHasKey('data', $response->json());
        $this->assertCount(1, $response->json('data'));
    }

    public function test_non_admin_cannot_access_delve_runs_api(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->call('GET', '/api/admin/monitoring/delve/runs');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_delve_summary_api_returns_expected_keys(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/delve/summary');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertArrayHasKey('total_runs', $response->json());
        $this->assertArrayHasKey('active', $response->json());
        $this->assertArrayHasKey('completed', $response->json());
    }

    public function test_delve_chart_api_returns_array(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/delve/chart');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertIsArray($response->json());
    }

    public function test_delve_summary_includes_outcome_totals(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $delve = DelveExploration::factory()->create(['character_id' => $character->id]);
        DelveLog::factory()->create(['character_id' => $character->id, 'delve_exploration_id' => $delve->id, 'outcome' => 'survived']);
        DelveLog::factory()->create(['character_id' => $character->id, 'delve_exploration_id' => $delve->id, 'outcome' => 'died']);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/delve/summary');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertArrayHasKey('total_survived', $response->json());
        $this->assertArrayHasKey('total_died', $response->json());
        $this->assertArrayHasKey('total_timeout', $response->json());
        $this->assertSame(1, $response->json('total_survived'));
        $this->assertSame(1, $response->json('total_died'));
    }

    public function test_delve_active_api_includes_outcome_counts_when_logs_exist(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE,
            'completed_at' => now()->addHour(),
        ]);
        $delve = DelveExploration::factory()->create([
            'character_id' => $character->id,
            'completed_at' => null,
        ]);
        DelveLog::factory()->create(['character_id' => $character->id, 'delve_exploration_id' => $delve->id, 'outcome' => 'survived']);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/delve/active');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(1, $response->json());
        $this->assertArrayHasKey('outcome_counts', $response->json()[0]);
        $this->assertSame(1, $response->json()[0]['outcome_counts']['survived']);
    }
}
