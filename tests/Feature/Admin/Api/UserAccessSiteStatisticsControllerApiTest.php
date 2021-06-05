<?php

namespace Tests\Feature\Admin\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\User;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateRole;

class UserAccessSiteStatisticsControllerApiTest extends TestCase {

    use RefreshDatabase,
        CreateUser,
        CreateRole;

    /**
     * @var User $admin
     */
    private $admin;

    public function setUp(): void {
        parent::setUp();

        $this->admin = $this->createAdmin([], $this->createAdminRole());
    }

    public function tearDown(): void {
        $this->admin = null;

        parent::tearDown();
    }

    public function testCanGetSiteAccessStatistics() {
        $response = $this->actingAs($this->admin)
            ->json('GET', '/api/admin/site-statistics')
            ->response;

        $content   = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertNotEmpty($content);
    }
}
