<?php

namespace Tests;

use Laravel\BrowserKitTesting\TestCase as BaseTestCase;
use Tests\Setup\AttackDataCacheSetUp;

abstract class TestCase extends BaseTestCase {

    use CreatesApplication;

    public string $baseUrl = 'http://localhost';

    public AttackDataCacheSetUp $attackDataCacheSetUp;

    public function setUp(): void {

        parent::setUp();

        $this->attackDataCacheSetUp = new AttackDataCacheSetUp();

        $this->attackDataCacheSetUp->mockCacheBuilder($this->app);
    }

    public function tearDown(): void {
        parent::tearDown();
    }
}


