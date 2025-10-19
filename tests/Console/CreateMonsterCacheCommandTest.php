<?php

namespace Tests\Console;

use App\Flare\Services\BuildMonsterCacheService;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class CreateMonsterCacheCommandTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $mock = Mockery::mock(BuildMonsterCacheService::class);
        $mock->shouldReceive('buildCache')->once()->andReturnUsing(function () {
            Cache::put('__called_buildCache', true);
        });
        $mock->shouldReceive('buildCelesetialCache')->once()->andReturnUsing(function () {
            Cache::put('__called_buildCelesetialCache', true);
        });
        $mock->shouldReceive('buildRaidCache')->once()->andReturnUsing(function () {
            Cache::put('__called_buildRaidCache', true);
        });
        $mock->shouldReceive('buildSpecialLocationMonsterList')->once()->andReturnUsing(function () {
            Cache::put('__called_buildSpecialLocationMonsterList', true);
        });

        $this->app->instance(BuildMonsterCacheService::class, $mock);
    }

    public function tearDown(): void
    {
        Mockery::close();
        Cache::flush();

        parent::tearDown();
    }

    public function test_command_invokes_all_cache_builders_and_marks_cache_keys()
    {
        $this->artisan('generate:monster-cache');

        $this->assertTrue(Cache::get('__called_buildCache', false));
        $this->assertTrue(Cache::get('__called_buildCelesetialCache', false));
        $this->assertTrue(Cache::get('__called_buildRaidCache', false));
        $this->assertTrue(Cache::get('__called_buildSpecialLocationMonsterList', false));
    }
}
