<?php

namespace App\Game\NpcActions\WorkBench\Providers;

use App\Game\NpcActions\QueenOfHeartsActions\Services\QueenOfHeartsService;
use App\Game\NpcActions\WorkBench\Services\HolyItemService;
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

        $this->app->bind(HolyItemService::class, function() {
            return new HolyItemService();
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
