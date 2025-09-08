<?php

namespace App\Game\NpcActions\QueenOfHeartsActions\Providers;

use App\Flare\Items\Builders\AffixAttributeBuilder;
use App\Flare\Items\Builders\RandomAffixGenerator;
use App\Game\NpcActions\QueenOfHeartsActions\Services\QueenOfHeartsService;
use App\Game\NpcActions\QueenOfHeartsActions\Services\RandomEnchantmentService;
use App\Game\NpcActions\QueenOfHeartsActions\Services\ReRollEnchantmentService;
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

        $this->app->bind(RandomEnchantmentService::class, function ($app) {
            // @codeCoverageIgnoreStart
            return new RandomEnchantmentService(
                $app->make(RandomAffixGenerator::class)
            );
            // @codeCoverageIgnoreEnd
        });

        $this->app->bind(ReRollEnchantmentService::class, function ($app) {
            return new ReRollEnchantmentService(
                $app->make(AffixAttributeBuilder::class),
                $app->make(RandomEnchantmentService::class)
            );
        });

        $this->app->bind(QueenOfHeartsService::class, function ($app) {
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
    public function boot() {}
}
