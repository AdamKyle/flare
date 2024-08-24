<?php

namespace App\Flare\AlchemyItemGenerator\Providers;

use App\Flare\AlchemyItemGenerator\Console\Commands\MassGenerateAlchemyItems;
use App\Flare\AlchemyItemGenerator\DTO\AlchemyItemDTO;
use App\Flare\AlchemyItemGenerator\Generator\GenerateAlchemyItem;
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

        $this->app->bind(AlchemyItemDTO::class, function () {
            return new AlchemyItemDTO;
        });

        $this->app->bind(GenerateAlchemyItem::class, function () {
            return new GenerateAlchemyItem;
        });

        $this->commands([
            MassGenerateAlchemyItems::class,
        ]);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {}
}
