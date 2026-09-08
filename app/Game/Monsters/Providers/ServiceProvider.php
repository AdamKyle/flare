<?php

namespace App\Game\Monsters\Providers;

use App\Game\Monsters\Console\Commands\CreateMonsterCache;
use App\Game\Monsters\Services\MonsterGemEffectContextService;
use App\Game\Monsters\Services\MonsterListService;
use App\Game\Monsters\Services\MonsterStatsService;
use App\Game\Monsters\Transformers\MonsterDetailTransformer;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(MonsterStatsService::class, function ($app) {
            return new MonsterStatsService(
                $app->make(MonsterListService::class),
                $app->make(MonsterDetailTransformer::class),
                $app->make(MonsterGemEffectContextService::class),
            );
        });

        $this->commands([
            CreateMonsterCache::class,
        ]);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // ...
    }
}
