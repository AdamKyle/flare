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

    public function testNonAdminCannotAccessDelveMonitoringPage(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->call('GET', '/admin/monitoring/delve');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function testAdminCanViewDelveMonitoringPage(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/admin/monitoring/delve');

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testNonAdminCannotAccessDelveActiveApi(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->call('GET', '/api/admin/monitoring/delve/active');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function testDelveActiveApiReturnsEmptyWhenNoActiveRunners(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/delve/active');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([], $response->json());
    }

    public function testDelveActiveApiReturnsActiveRunner(): void
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

    public function testDelveActiveApiExcludesCompletedAutomations(): void
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

    public function testDelveActiveApiExcludesExploringAutomations(): void
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

    public function testDelveRunsApiReturnsPaginatedResults(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        DelveExploration::factory()->create(['character_id' => $character->id]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/delve/runs');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertArrayHasKey('data', $response->json());
        $this->assertCount(1, $response->json('data'));
    }

    public function testNonAdminCannotAccessDelveRunsApi(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->call('GET', '/api/admin/monitoring/delve/runs');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function testDelveSummaryApiReturnsExpectedKeys(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/delve/summary');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertArrayHasKey('total_runs', $response->json());
        $this->assertArrayHasKey('active', $response->json());
        $this->assertArrayHasKey('completed', $response->json());
    }

    public function testDelveChartApiReturnsArray(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/delve/chart');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertIsArray($response->json());
    }

    public function testDelveSummaryIncludesOutcomeTotals(): void
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

    public function testDelveActiveApiIncludesOutcomeCountsWhenLogsExist(): void
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
