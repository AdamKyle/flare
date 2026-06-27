<?php

namespace Tests\Feature\Admin;

use App\Flare\Models\CharacterBattleRewardQueueState;
use App\Flare\Models\CharacterBattleRewardRequest;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestPriority;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestSourceType;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class BattleRewardQueueControllerTest extends TestCase
{
    use CreateRole, CreateUser, RefreshDatabase;

    public function test_admin_can_view_reward_queue_page_and_home_card(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $this->actingAs($admin)
            ->visitRoute('home')
            ->see('Character Reward Queue')
            ->click('Character Reward Queue')
            ->seeRouteIs('admin.character-reward-queue')
            ->see('Character Reward Queue');
    }

    public function test_non_admin_cannot_view_reward_queue_page(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)
            ->call('GET', '/admin/character-reward-queue');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_admin_api_returns_summary_characters_charts_and_filtered_requests(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'priority' => BattleRewardRequestPriority::FIRST,
            'source_type' => BattleRewardRequestSourceType::QUEST,
            'source_id' => 'quest-1',
            'status' => BattleRewardRequestStatus::FAILED,
            'failed_reason' => 'specific failure',
        ]);

        $summary = $this->actingAs($admin)->call('GET', '/api/admin/character-reward-queue/summary');
        $characters = $this->actingAs($admin)->call('GET', '/api/admin/character-reward-queue/characters');
        $charts = $this->actingAs($admin)->call('GET', '/api/admin/character-reward-queue/charts');
        $requests = $this->actingAs($admin)->call('GET', '/api/admin/character-reward-queue/requests', [
            'status' => BattleRewardRequestStatus::FAILED->value,
            'priority' => BattleRewardRequestPriority::FIRST->value,
            'source_type' => BattleRewardRequestSourceType::QUEST->value,
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
            'character_name' => $character->name,
            'failed_reason' => 'specific',
            'source_id' => 'quest-1',
        ]);
        $detail = $this->actingAs($admin)->call(
            'GET',
            '/api/admin/character-reward-queue/characters/'.$character->id,
        );
        $statusBreakdown = $this->actingAs($admin)->call(
            'GET',
            '/api/admin/character-reward-queue/status-breakdown',
            ['days' => 30],
        );

        $this->assertSame(200, $summary->getStatusCode());
        $this->assertSame(200, $characters->getStatusCode(), $characters->getContent());
        $this->assertSame(200, $charts->getStatusCode(), $charts->getContent());
        $this->assertSame(200, $requests->getStatusCode(), $requests->getContent());
        $this->assertSame(200, $detail->getStatusCode(), $detail->getContent());
        $this->assertSame(200, $statusBreakdown->getStatusCode(), $statusBreakdown->getContent());
        $this->assertSame(1, json_decode($summary->getContent(), true)['failed']);
        $this->assertSame($character->name, json_decode($characters->getContent(), true)['data'][0]['character_name']);
        $this->assertArrayHasKey('last_hour', json_decode($charts->getContent(), true));
        $this->assertCount(1, json_decode($requests->getContent(), true)['data']);
        $this->assertArrayHasKey('30', json_decode($detail->getContent(), true)['charts']);
        $this->assertCount(1, json_decode($statusBreakdown->getContent(), true));
    }

    public function test_non_admin_cannot_access_reward_queue_api(): void
    {
        $response = $this->actingAs($this->createUser())
            ->call('GET', '/api/admin/character-reward-queue/summary', [], [], [], [
                'HTTP_ACCEPT' => 'application/json',
            ]);

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_request_enum_filters_work_individually(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'priority' => BattleRewardRequestPriority::FIRST,
            'source_type' => BattleRewardRequestSourceType::QUEST,
            'status' => BattleRewardRequestStatus::FAILED,
        ]);
        CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'priority' => BattleRewardRequestPriority::SECOND,
            'source_type' => BattleRewardRequestSourceType::BATTLE,
            'status' => BattleRewardRequestStatus::COMPLETED,
        ]);

        $statusResponse = $this->actingAs($admin)->call(
            'GET',
            '/api/admin/character-reward-queue/requests',
            ['status' => BattleRewardRequestStatus::FAILED->value],
        );
        $priorityResponse = $this->actingAs($admin)->call(
            'GET',
            '/api/admin/character-reward-queue/requests',
            ['priority' => BattleRewardRequestPriority::SECOND->value],
        );
        $sourceResponse = $this->actingAs($admin)->call(
            'GET',
            '/api/admin/character-reward-queue/requests',
            ['source_type' => BattleRewardRequestSourceType::QUEST->value],
        );

        $this->assertCount(1, json_decode($statusResponse->getContent(), true)['data']);
        $this->assertCount(1, json_decode($priorityResponse->getContent(), true)['data']);
        $this->assertCount(1, json_decode($sourceResponse->getContent(), true)['data']);
    }

    public function test_reward_queue_broadcast_channel_is_admin_only(): void
    {
        $callback = Broadcast::driver()->getChannels()->get('admin-character-reward-queue');
        $admin = $this->createAdmin($this->createAdminRole());

        $this->assertTrue($callback($admin));
        $this->assertFalse($callback($this->createUser()));
    }

    public function test_admin_can_fetch_stale_queue_state_data(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        CharacterBattleRewardQueueState::factory()->create([
            'character_id' => $character->id,
            'is_processing' => true,
            'started_at' => now()->subMinutes(15),
            'heartbeat_at' => now()->subMinutes(10),
        ]);
        CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::PROCESSING,
        ]);

        $response = $this->actingAs($admin)->call(
            'GET',
            '/api/admin/character-reward-queue/stale',
        );
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($character->id, $data[0]['character_id']);
        $this->assertSame($character->name, $data[0]['character_name']);
        $this->assertSame(1, $data[0]['processing_request_count']);
        $this->assertCount(1, $data[0]['requests']);
    }

    public function test_non_admin_cannot_fetch_stale_queue_state_data(): void
    {
        $response = $this->actingAs($this->createUser())
            ->call('GET', '/api/admin/character-reward-queue/stale', [], [], [], [
                'HTTP_ACCEPT' => 'application/json',
            ]);

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_admin_can_repair_stale_queues_and_receive_counts(): void
    {
        Queue::fake();
        $admin = $this->createAdmin($this->createAdminRole());
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        CharacterBattleRewardQueueState::factory()->create([
            'character_id' => $character->id,
            'is_processing' => true,
            'heartbeat_at' => now()->subMinutes(10),
        ]);
        CharacterBattleRewardRequest::factory()->create([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::PENDING,
        ]);

        $response = $this->actingAs($admin)->call(
            'POST',
            '/api/admin/character-reward-queue/stale/repair',
            ['_token' => csrf_token()],
        );
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, $data['repaired_queue_state_count']);
        $this->assertSame(1, $data['restarted_processor_count']);
    }
}
