<?php

namespace Tests\Feature\Admin\Statistics;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateRole;
use Tests\Setup\Character\CharacterFactory;

class StatisticsControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateUser,
        CreateRole;

    private $user;

    private $quest;

    public function setUp(): void {
        parent::setUp();

        $role = $this->createAdminRole();

        $this->user = $this->createAdmin($role, []);

        (new CharacterFactory())->createBaseCharacter();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->user = null;
    }

    public function testCanSeeStatisticsPage() {
        $this->actingAs($this->user)->visitRoute('admin.statistics')->see('Statistical Data');
    }

}
