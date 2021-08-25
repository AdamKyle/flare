<?php

namespace Tests\Feature\Admin\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\User;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUserSiteAccessStatistics;
use Tests\Setup\Character\CharacterFactory;

class UserAccessSiteStatisticsControllerApiTest extends TestCase {

    use RefreshDatabase,
        CreateUser,
        CreateRole,
        CreateUserSiteAccessStatistics;

    /**
     * @var User $admin
     */
    private $admin;

    public function setUp(): void {
        parent::setUp();

        $this->admin = $this->createAdmin($this->createAdminRole(), []);
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

    public function testCanGetAllTimeLoggedIn() {
        $response = $this->actingAs($this->admin)
            ->json('GET', '/api/admin/site-statistics/all-time-sign-in')
            ->response;

        $content   = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertNotEmpty($content);
    }

    public function testCanGetAllTimeRegistered() {
        $response = $this->actingAs($this->admin)
            ->json('GET', '/api/admin/site-statistics/all-time-register')
            ->response;

        $content   = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertNotEmpty($content);
    }

    public function testGetCharactersGold() {
        (new CharacterFactory())->createBaseCharacter()->updateCharacter(['gold' => 200000]);

        $response = $this->actingAs($this->admin)
            ->json('GET', '/api/admin/site-statistics/all-characters-gold')
            ->response;

        $content   = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertNotEmpty($content);
    }
}
