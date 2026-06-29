<?php

namespace Tests\Feature\Admin;

use App\Game\BatchCrafting\Values\BatchCraftingDisposition;
use App\Game\BatchCrafting\Values\BatchCraftingType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class BatchCraftingMonitoringTest extends TestCase
{
    use CreateBatchCrafting, CreateRole, CreateUser, RefreshDatabase;

    public function testNonAdminCannotAccessBatchCraftingMonitoringPage(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->call('GET', '/admin/monitoring/batch-crafting');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function testAdminCanViewBatchCraftingMonitoringPage(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/admin/monitoring/batch-crafting');

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testNonAdminCannotAccessBatchCraftingActiveApi(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->call('GET', '/api/admin/monitoring/batch-crafting/active');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function testBatchCraftingActiveApiReturnsEmptyWhenNoBatchesRunning(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/batch-crafting/active');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([], $response->json());
    }

    public function testBatchCraftingActiveApiReturnsActiveBatch(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/batch-crafting/active');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(1, $response->json());
    }

    public function testBatchCraftingActiveApiExcludesCompletedBatches(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/batch-crafting/active');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(0, $response->json());
    }

    public function testNonAdminCannotAccessBatchCraftingRunsApi(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->call('GET', '/api/admin/monitoring/batch-crafting/runs');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function testBatchCraftingRunsApiReturnsPaginatedResults(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => BatchCraftingType::CRAFT->value,
            'disposition' => BatchCraftingDisposition::KEEP->value,
        ]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/batch-crafting/runs');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertArrayHasKey('data', $response->json());
        $this->assertCount(1, $response->json('data'));
    }

    public function testBatchCraftingSummaryApiReturnsExpectedKeys(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/batch-crafting/summary');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertArrayHasKey('total_runs', $response->json());
        $this->assertArrayHasKey('active', $response->json());
        $this->assertArrayHasKey('completed', $response->json());
        $this->assertArrayHasKey('cancelled', $response->json());
        $this->assertArrayHasKey('total_crafted', $response->json());
        $this->assertArrayHasKey('total_failed', $response->json());
    }

    public function testBatchCraftingChartApiReturnsArray(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/batch-crafting/chart');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertIsArray($response->json());
    }
}
