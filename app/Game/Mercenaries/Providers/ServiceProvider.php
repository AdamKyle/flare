<?php

namespace App\Game\Mercenaries\Providers;

use App\Game\Core\Services\EquipItemService;
use App\Game\Market\Services\MarketBoard;
use App\Game\Market\Services\MarketHistory;
use App\Game\Mercenaries\Services\MercenaryService;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use App\Game\Market\Middleware\CanCharacterAccessMarket;

class ServiceProvider extends ApplicationServiceProvider {

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void {
        $this->app->bind(MercenaryService::class, function() {
            return new MercenaryService();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void { }
}
