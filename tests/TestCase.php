<?php

namespace Tests;

use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\HtmlString;
use Laravel\BrowserKitTesting\TestCase as BaseTestCase;
use Mockery;
use Tests\Setup\AttackDataCacheSetUp;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected bool $useMockForAttackDataCache = true;

    public string $baseUrl = 'http://localhost';

    public ?AttackDataCacheSetUp $attackDataCacheSetUp;

    protected function setUp(): void
    {

        parent::setUp();

        Facade::clearResolvedInstance(Vite::class);

        $this->app->instance(Vite::class, new class extends Vite
        {
            public function __invoke($entrypoints, $buildDirectory = null): HtmlString
            {
                return new HtmlString('');
            }

            public function __call($method, $parameters)
            {
                return '';
            }

            public function __toString()
            {
                return '';
            }

            public function useIntegrityKey($key)
            {
                return $this;
            }

            public function useBuildDirectory($path)
            {
                return $this;
            }

            public function useHotFile($path)
            {
                return $this;
            }

            public function withEntryPoints($entryPoints)
            {
                return $this;
            }

            public function useScriptTagAttributes($attributes)
            {
                return $this;
            }

            public function useStyleTagAttributes($attributes)
            {
                return $this;
            }

            public function usePreloadTagAttributes($attributes)
            {
                return $this;
            }

            public function preloadedAssets()
            {
                return [];
            }

            public function reactRefresh()
            {
                return '';
            }

            public function content($asset, $buildDirectory = null)
            {
                return '';
            }

            public function asset($asset, $buildDirectory = null)
            {
                return '';
            }
        });

        if ($this->useMockForAttackDataCache) {
            $this->attackDataCacheSetUp = new AttackDataCacheSetUp;

            $this->attackDataCacheSetUp->mockCacheBuilder($this->app);
        }

        config([
            'queue.default' => 'sync',
            'queue.connections.battle_reward_xp' => [
                'driver' => 'sync',
            ],
            'queue.connections.event_battle_reward' => [
                'driver' => 'sync',
            ],
            'queue.connections.battle_reward_processing' => [
                'driver' => 'sync',
            ],
            'queue.connections.battle_reward_factions' => [
                'driver' => 'sync',
            ],
            'queue.connections.battle_secondary_reward' => [
                'driver' => 'sync',
            ],
            'queue.connections.battle_reward_currencies' => [
                'driver' => 'sync',
            ],
            'queue.connections.battle_reward_global_event' => [
                'driver' => 'sync',
            ],
            'queue.connections.battle_reward_location_handlers' => [
                'driver' => 'sync',
            ],
            'queue.connections.battle_reward_weekly_fights' => [
                'driver' => 'sync',
            ],
            'queue.connections.battle_reward_item_handler' => [
                'driver' => 'sync',
            ],
            'queue.connections.long_running' => [
                'driver' => 'sync',
            ],
        ]);

    }

    protected function tearDown(): void
    {

        $this->attackDataCacheSetUp = null;

        $this->useMockForAttackDataCache = true;

        Mockery::close();

        parent::tearDown();
    }
}
