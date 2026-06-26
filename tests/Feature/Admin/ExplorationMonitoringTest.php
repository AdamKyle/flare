<?php

namespace Tests\Feature\Admin;

use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\ExplorationLog;
use App\Flare\Values\AutomationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class ExplorationMonitoringTest extends TestCase
{
    use CreateRole, CreateUser, RefreshDatabase;

    public function testNonAdminCannotAccessExplorationMonitoringPage(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->call('GET', '/admin/monitoring/exploration');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function testAdminCanViewExplorationMonitoringPage(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/admin/monitoring/exploration');

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testNonAdminCannotAccessActiveExplorationApi(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->call('GET', '/api/admin/monitoring/exploration/active');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function testActiveExplorationApiReturnsEmptyArrayWhenNoActiveExplorers(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/exploration/active');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([], $response->json());
    }

    public function testActiveExplorationApiReturnsActiveExplorer(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'completed_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/exploration/active');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(1, $response->json());
    }

    public function testActiveExplorationApiExcludesCompletedAutomations(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'completed_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/exploration/active');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(0, $response->json());
    }

    public function testActiveExplorationApiExcludesDelveAutomations(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE,
            'completed_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/exploration/active');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(0, $response->json());
    }

    public function testExplorationLogsApiReturnsPaginatedResults(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        ExplorationLog::factory()->create(['character_id' => $character->id]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/exploration/logs');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertArrayHasKey('data', $response->json());
        $this->assertCount(1, $response->json('data'));
    }

    public function testNonAdminCannotAccessExplorationLogsApi(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->call('GET', '/api/admin/monitoring/exploration/logs');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function testExplorationSummaryApiReturnsExpectedKeys(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/exploration/summary');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertArrayHasKey('total_runs', $response->json());
        $this->assertArrayHasKey('stopped_by_player', $response->json());
        $this->assertArrayHasKey('total_kills', $response->json());
    }

    public function testExplorationChartApiReturnsArray(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/exploration/chart');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertIsArray($response->json());
    }

    public function testExplorationSummaryIncludesWeaponDamageAndSpellDamageFields(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/exploration/summary');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertArrayHasKey('total_weapon_damage', $response->json());
        $this->assertArrayHasKey('total_spell_damage', $response->json());
        $this->assertArrayHasKey('total_faction_points_gained', $response->json());
    }
}
