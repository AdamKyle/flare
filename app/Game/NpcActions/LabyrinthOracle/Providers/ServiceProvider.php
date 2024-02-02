<?php

namespace App\Game\NpcActions\LabyrinthOracle\Providers;

use App\Game\NpcActions\LabyrinthOracle\Services\ItemTransferService;
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

        $this->app->bind(ItemTransferService::class, function($app) {
            return new ItemTransferService();
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
