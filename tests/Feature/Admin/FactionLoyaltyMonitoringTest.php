<?php

namespace Tests\Feature\Admin;

use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\FactionLoyaltyAutomation;
use App\Flare\Values\AutomationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class FactionLoyaltyMonitoringTest extends TestCase
{
    use CreateRole, CreateUser, RefreshDatabase;

    public function testNonAdminCannotAccessFactionLoyaltyMonitoringPage(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->call('GET', '/admin/monitoring/faction-loyalty');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function testAdminCanViewFactionLoyaltyMonitoringPage(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/admin/monitoring/faction-loyalty');

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testNonAdminCannotAccessFactionLoyaltyActiveApi(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->call('GET', '/api/admin/monitoring/faction-loyalty/active');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function testFactionLoyaltyActiveApiReturnsEmptyWhenNoActiveRunners(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/faction-loyalty/active');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([], $response->json());
    }

    public function testFactionLoyaltyActiveApiReturnsActiveRunner(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $automation = CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'completed_at' => now()->addHour(),
        ]);

        FactionLoyaltyAutomation::factory()->create([
            'character_automation_id' => $automation->id,
            'character_id' => $character->id,
            'faction_loyalty_npc_id' => 0,
            'completed_at' => null,
        ]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/faction-loyalty/active');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(1, $response->json());
    }

    public function testFactionLoyaltyActiveApiExcludesCompletedRunners(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $automation = CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'completed_at' => now()->addHour(),
        ]);

        FactionLoyaltyAutomation::factory()->create([
            'character_automation_id' => $automation->id,
            'character_id' => $character->id,
            'faction_loyalty_npc_id' => 0,
            'completed_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/faction-loyalty/active');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(0, $response->json());
    }

    public function testFactionLoyaltyRunsApiReturnsPaginatedResults(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $automation = CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'completed_at' => now()->addHour(),
        ]);

        FactionLoyaltyAutomation::factory()->create([
            'character_automation_id' => $automation->id,
            'character_id' => $character->id,
            'faction_loyalty_npc_id' => 0,
        ]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/faction-loyalty/runs');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertArrayHasKey('data', $response->json());
        $this->assertCount(1, $response->json('data'));
    }

    public function testNonAdminCannotAccessFactionLoyaltyRunsApi(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->call('GET', '/api/admin/monitoring/faction-loyalty/runs');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function testFactionLoyaltySummaryApiReturnsExpectedKeys(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/faction-loyalty/summary');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertArrayHasKey('total_runs', $response->json());
        $this->assertArrayHasKey('active', $response->json());
        $this->assertArrayHasKey('completed', $response->json());
    }

    public function testFactionLoyaltyChartApiReturnsArray(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/faction-loyalty/chart');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertIsArray($response->json());
    }

    public function testFactionLoyaltyActiveApiIncludesLastFightOutcome(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $automation = CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'completed_at' => now()->addHour(),
        ]);

        FactionLoyaltyAutomation::factory()->create([
            'character_automation_id' => $automation->id,
            'character_id' => $character->id,
            'faction_loyalty_npc_id' => 0,
            'completed_at' => null,
            'last_fight_outcome' => 'won',
            'last_fight_was_bounty_target' => true,
        ]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/faction-loyalty/active');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(1, $response->json());
        $this->assertArrayHasKey('last_fight_outcome', $response->json()[0]);
        $this->assertArrayHasKey('last_fight_was_bounty_target', $response->json()[0]);
        $this->assertSame('won', $response->json()[0]['last_fight_outcome']);
        $this->assertTrue($response->json()[0]['last_fight_was_bounty_target']);
    }
}
