<?php

namespace Tests\Feature\Game\Tops;

use App\Flare\Models\TopsMonthlySnapshot;
use App\Flare\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TopsMonthlySnapshotApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_list_archived_snapshot_months(): void
    {
        $user = User::factory()->create();
        TopsMonthlySnapshot::factory()->create(['period_start' => '2026-05-01', 'period_end' => '2026-05-31']);

        $response = $this->actingAs($user)->call('GET', '/api/game/tops/monthly-snapshots');
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertSame('2026-05', $data['months'][0]['period']);
    }
}
