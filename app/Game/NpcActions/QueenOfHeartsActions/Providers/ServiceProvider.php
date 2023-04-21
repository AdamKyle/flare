<?php

namespace App\Game\NpcActions\QueenOfHeartsActions\Providers;

use App\Game\NpcActions\QueenOfHeartsActions\Services\QueenOfHeartsService;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use App\Flare\Builders\AffixAttributeBuilder;
use App\Flare\Builders\RandomAffixGenerator;
use App\Game\NpcActions\QueenOfHeartsActions\Services\RandomEnchantmentService;
use App\Game\NpcActions\QueenOfHeartsActions\Services\ReRollEnchantmentService;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

        $this->app->bind(RandomEnchantmentService::class, function($app) {
            return new RandomEnchantmentService(
                $app->make(RandomAffixGenerator::class)
            );
        });

        $this->app->bind(ReRollEnchantmentService::class, function($app) {
            return new ReRollEnchantmentService(
                $app->make(AffixAttributeBuilder::class),
                $app->make(RandomEnchantmentService::class)
            );
        });

        $this->app->bind(QueenOfHeartsService::class, function($app) {
            return new QueenOfHeartsService(
                $app->make(RandomEnchantmentService::class),
                $app->make(ReRollEnchantmentService::class),
            );
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
    }
}
