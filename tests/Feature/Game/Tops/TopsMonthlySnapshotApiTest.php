<?php

namespace Tests\Feature\Game\Tops;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateTopsMonthlySnapshot;
use Tests\Traits\CreateUser;

class TopsMonthlySnapshotApiTest extends TestCase
{
    use CreateTopsMonthlySnapshot, CreateUser, RefreshDatabase;

    public function test_authenticated_users_can_list_archived_snapshot_months(): void
    {
        $user = $this->createUser();
        $this->createTopsMonthlySnapshot(['period_start' => '2026-05-01', 'period_end' => '2026-05-31']);

        $response = $this->actingAs($user)->call('GET', '/api/game/tops/monthly-snapshots');
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertSame('2026-05', $data['months'][0]['period']);
    }
}
