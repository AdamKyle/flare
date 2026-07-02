<?php

namespace Tests\Unit\Game\Events;

use App\Admin\Events\AdminStatisticsDashboardUpdated;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Tests\TestCase;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class AdminStatisticsDashboardUpdatedTest extends TestCase
{
    use CreateRole, CreateUser, RefreshDatabase;

    public function testWebsocketEventPayloadContainsSnapshotStructure(): void
    {
        $event = new AdminStatisticsDashboardUpdated([
            'generated_at' => now()->toIso8601String(),
            'summary' => [],
            'login_chart' => [],
            'registration_chart' => [],
            'login_duration_chart' => [],
            'today_login_count_chart' => [],
            'login_participation_summary' => [],
            'login_participation_chart' => [],
            'inactive_user_deletion_summary' => [],
            'online_characters' => [],
            'reincarnation_chart' => [],
            'quest_completion_chart' => [],
            'guide_quest_completion_chart' => [],
            'gold_chart' => [],
            'kingdom_summary' => [],
            'top_kingdom_holders' => [],
            'metric_definitions' => [],
        ]);

        $payload = $event->broadcastWith();

        $this->assertInstanceOf(PrivateChannel::class, $event->broadcastOn());
        $this->assertSame('admin.statistics.dashboard.updated', $event->broadcastAs());
        $this->assertArrayHasKey('snapshot', $payload);
        $this->assertArrayHasKey('generated_at', $payload['snapshot']);
        $this->assertArrayHasKey('summary', $payload['snapshot']);
        $this->assertArrayHasKey('login_chart', $payload['snapshot']);
        $this->assertArrayHasKey('registration_chart', $payload['snapshot']);
        $this->assertArrayHasKey('login_duration_chart', $payload['snapshot']);
        $this->assertArrayHasKey('today_login_count_chart', $payload['snapshot']);
        $this->assertArrayHasKey('login_participation_summary', $payload['snapshot']);
        $this->assertArrayHasKey('login_participation_chart', $payload['snapshot']);
        $this->assertArrayHasKey('inactive_user_deletion_summary', $payload['snapshot']);
        $this->assertArrayHasKey('online_characters', $payload['snapshot']);
        $this->assertArrayHasKey('reincarnation_chart', $payload['snapshot']);
        $this->assertArrayHasKey('quest_completion_chart', $payload['snapshot']);
        $this->assertArrayHasKey('guide_quest_completion_chart', $payload['snapshot']);
        $this->assertArrayHasKey('gold_chart', $payload['snapshot']);
        $this->assertArrayHasKey('kingdom_summary', $payload['snapshot']);
        $this->assertArrayHasKey('top_kingdom_holders', $payload['snapshot']);
        $this->assertArrayHasKey('metric_definitions', $payload['snapshot']);
    }

    public function testWebsocketChannelIsPrivateAndAdminOnly(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $user = $this->createUser();
        $callback = Broadcast::driver()->getChannels()->get('admin-statistics-dashboard');

        $this->assertTrue($callback($admin));
        $this->assertFalse($callback($user));
    }
}
