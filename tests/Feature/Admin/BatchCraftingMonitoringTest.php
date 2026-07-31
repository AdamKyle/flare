<?php

namespace Tests\Feature\Admin;

use App\Admin\Events\BatchCraftingMonitoringUpdated;
use App\Game\BatchCrafting\Values\BatchCraftingDisposition;
use App\Game\BatchCrafting\Values\BatchCraftingType;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
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

    public function testBatchCraftingMonitoringChannelAllowsAdmin(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $callback = Broadcast::driver()->getChannels()->get('admin-monitoring-batch-crafting');
        $result = $callback($admin);

        $this->assertTrue($result);
    }

    public function testBatchCraftingMonitoringChannelRejectsNonAdmin(): void
    {
        $user = $this->createUser();

        $callback = Broadcast::driver()->getChannels()->get('admin-monitoring-batch-crafting');
        $result = $callback($user);

        $this->assertFalse($result);
    }

    public function testBatchCraftingMonitoringEventBroadcastsOnAdminChannel(): void
    {
        $event = new BatchCraftingMonitoringUpdated(42);

        $channel = $event->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertSame('private-admin-monitoring-batch-crafting', $channel->name);
    }

    public function testBatchCraftingMonitoringEventBroadcastNameIsCorrect(): void
    {
        $event = new BatchCraftingMonitoringUpdated(42);

        $this->assertSame('batch-crafting.monitoring.updated', $event->broadcastAs());
    }

    public function testAdminHomeRendersBatchCraftingMonitoringBesideApplicationLogs(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/admin');

        $this->assertSame(200, $response->getStatusCode());
        $response->assertSee('Batch Crafting Monitoring');
        $response->assertSee('Application Logs');
        $response->assertSee('lg:grid-cols-2', false);
    }
}
