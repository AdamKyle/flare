<?php

namespace Tests\Feature\Admin;

use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestPriority;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestSourceType;
use App\Game\BattleRewardProcessing\Enums\BattleRewardRequestStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\ConfiguresAdminMonitoringBroadcasting;
use Tests\Traits\CreateCharacterBattleReward;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class BattleRewardQueueControllerTest extends TestCase
{
    use ConfiguresAdminMonitoringBroadcasting, CreateCharacterBattleReward, CreateRole, CreateUser, RefreshDatabase;

    public function test_admin_can_view_reward_queue_home_card(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $homeResponse = $this->actingAs($admin)->get(route('home'));

        $homeResponse->assertSee('Character Reward Queue');
    }

    public function test_admin_can_view_reward_queue_page(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $queueResponse = $this->actingAs($admin)->get(route('admin.character-reward-queue'));

        $queueResponse->assertOk();
        $queueResponse->assertSee('Character Reward Queue');
    }

    public function test_non_admin_cannot_view_reward_queue_page(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)
            ->call('GET', '/admin/character-reward-queue');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_admin_api_returns_summary(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::FAILED,
        ]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/character-reward-queue/summary');

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertSame(1, json_decode($response->getContent(), true)['failed']);
    }

    public function test_admin_api_returns_characters(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
        ]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/character-reward-queue/characters');

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertSame($character->name, json_decode($response->getContent(), true)['data'][0]['character_name']);
    }

    public function test_admin_api_returns_charts(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
        ]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/character-reward-queue/charts');

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertArrayHasKey('last_hour', json_decode($response->getContent(), true));
    }

    public function test_admin_api_returns_filtered_requests(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'source_id' => 'quest-1',
            'status' => BattleRewardRequestStatus::FAILED,
            'failed_reason' => 'specific failure',
        ]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/character-reward-queue/requests', [
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
            'character_name' => $character->name,
            'failed_reason' => 'specific',
            'source_id' => 'quest-1',
        ]);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertCount(1, json_decode($response->getContent(), true)['data']);
    }

    public function test_admin_api_returns_character_detail(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
        ]);

        $response = $this->actingAs($admin)->call(
            'GET',
            '/api/admin/character-reward-queue/characters/'.$character->id,
        );

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertArrayHasKey('30', json_decode($response->getContent(), true)['charts']);
    }

    public function test_admin_api_returns_status_breakdown(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::FAILED,
        ]);

        $response = $this->actingAs($admin)->call(
            'GET',
            '/api/admin/character-reward-queue/status-breakdown',
            ['days' => 30],
        );

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertCount(1, json_decode($response->getContent(), true));
    }

    public function test_non_admin_cannot_access_reward_queue_api(): void
    {
        $response = $this->actingAs($this->createUser())
            ->call('GET', '/api/admin/character-reward-queue/summary', [], [], [], [
                'HTTP_ACCEPT' => 'application/json',
            ]);

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_reward_queue_requests_can_be_filtered_by_status(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::FAILED,
        ]);
        $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'status' => BattleRewardRequestStatus::COMPLETED,
        ]);

        $response = $this->actingAs($admin)->call(
            'GET',
            '/api/admin/character-reward-queue/requests',
            ['status' => BattleRewardRequestStatus::FAILED->value],
        );

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertCount(1, json_decode($response->getContent(), true)['data']);
    }

    public function test_reward_queue_requests_can_be_filtered_by_priority(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'priority' => BattleRewardRequestPriority::FIRST,
        ]);
        $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'priority' => BattleRewardRequestPriority::SECOND,
        ]);

        $response = $this->actingAs($admin)->call(
            'GET',
            '/api/admin/character-reward-queue/requests',
            ['priority' => BattleRewardRequestPriority::SECOND->value],
        );

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertCount(1, json_decode($response->getContent(), true)['data']);
    }

    public function test_reward_queue_requests_can_be_filtered_by_source_type(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::QUEST,
        ]);
        $this->createCharacterBattleRewardRequest([
            'character_id' => $character->id,
            'source_type' => BattleRewardRequestSourceType::BATTLE,
        ]);

        $response = $this->actingAs($admin)->call(
            'GET',
            '/api/admin/character-reward-queue/requests',
            ['source_type' => BattleRewardRequestSourceType::QUEST->value],
        );

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertCount(1, json_decode($response->getContent(), true)['data']);
    }

    public function test_unauthenticated_request_is_rejected_for_admin_reward_queue_channel(): void
    {
        $this->configureAdminMonitoringBroadcasting();

        $response = $this->post('/broadcasting/auth', [
            'channel_name' => 'private-admin-character-reward-queue',
            'socket_id' => '1234.5678',
        ]);

        $response->assertForbidden();
    }

    public function test_authenticated_non_admin_is_rejected_for_admin_reward_queue_channel(): void
    {
        $this->configureAdminMonitoringBroadcasting();

        $response = $this->actingAs($this->createUser())->post('/broadcasting/auth', [
            'channel_name' => 'private-admin-character-reward-queue',
            'socket_id' => '1234.5678',
        ]);

        $response->assertForbidden();
    }

    public function test_authenticated_admin_is_authorized_for_admin_reward_queue_channel(): void
    {
        $this->configureAdminMonitoringBroadcasting();

        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->post('/broadcasting/auth', [
            'channel_name' => 'private-admin-character-reward-queue',
            'socket_id' => '1234.5678',
        ]);

        $response->assertOk();
    }

    public function test_admin_can_fetch_stale_queue_state_data(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $this->createCharacterBattleRewardQueueState([
            'character_id' => $character->id,
            'is_processing' => true,
            'started_at' => now()->subMinutes(15),
            'heartbeat_at' => now()->subMinutes(10),
        ]);
        $this->createCharacterBattleRewardRequest([
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
        $this->createCharacterBattleRewardQueueState([
            'character_id' => $character->id,
            'is_processing' => true,
            'heartbeat_at' => now()->subMinutes(10),
        ]);
        $this->createCharacterBattleRewardRequest([
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
