<?php

namespace App\Flare\Providers;

use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use App\Flare\Values\BaseStatValue;
use App\Flare\Builders\CharacterBuilder;
use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Builders\RandomItemDropBuilder;
use App\Flare\Console\Commands\CreateAdminAccount;
use App\Flare\Transformers\CharacterAttackTransformer;
use App\Flare\Transformers\CharacterSheetTransformer;
use App\Flare\Values\BaseSkillValue;
use App\Flare\Values\MaxDamageForItemValue;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(BaseStatValue::class, function ($app) {
            return new BaseStatValue();
        });

        $this->app->bind(CharacterBuilder::class, function ($app) {
            return new CharacterBuilder();
        });

        $this->app->bind(CharacterInformationBuilder::class, function($app) {
            return new CharacterInformationBuilder();
        });

        $this->app->bind(RandomItemDropBuilder::class, function($app) {
            return new RandomItemDropBuilder();
        });

        $this->app->bind(CharacterAttackTransformer::class, function($app) {
            return new CharacterAttackTransformer();
        });

        $this->app->bind(CharacterSheetTransformer::class, function($app){
            return new CharacterSheetTransformer();
        });

        $this->app->bind(BaseSkillValue::class, function($app) {
            return new BaseSkillValue();
        });

        $this->commands([CreateAdminAccount::class]);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
