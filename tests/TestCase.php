<?php

namespace Tests;

use App\Flare\Services\BuildCharacterAttackTypes;
use App\Game\Exploration\Handlers\RewardHandler;
use App\Game\Skills\Services\AlchemyService;
use Database\Seeders\GameSkillsSeeder;
use Illuminate\Support\Facades\Cache;
use Laravel\BrowserKitTesting\TestCase as BaseTestCase;
use Tests\Setup\AttackDataCacheSetUp;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public $baseUrl = 'http://localhost';

    public $attackDataMock;

    public function setUp(): void {

        parent::setUp();

        $app = $this->app;

        $this->attackDataMock = new AttackDataCacheSetUp();

        $this->attackDataMock->mockCacheBuilder($app);

    }

    public function tearDown(): void {
        parent::tearDown();

        $this->attackDataMock = null;
    }
}


