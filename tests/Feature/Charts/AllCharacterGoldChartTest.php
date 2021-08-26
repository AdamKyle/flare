<?php

namespace Tests\Feature\Charts;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class AllCharacterGoldChartTest extends TestCase
{
    use RefreshDatabase, CreateRole, CreateUser;

    public function testGetCharacterGoldChartData() {
        $adminRole = $this->createAdminRole();

        $user = $this->createAdmin($adminRole);

        $response = $this->actingAs($user)->json('get', 'api/chart/all_character_gold')->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertNotEmpty($content);
    }
}
