<?php

namespace App\Game\Character\Builders\InformationBuilders\Providers;


use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\ClassRanksWeaponMasteriesBuilder;
use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\DamageBuilder;
use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\DefenceBuilder;
use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\ElementalAtonement;
use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\HealingBuilder;
use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\HolyBuilder;
use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\ReductionsBuilder;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use App\Game\Gems\Services\GemComparison;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;

class ServiceProvider extends ApplicationServiceProvider {

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {
        $this->app->bind(DefenceBuilder::class, function() {
            return new DefenceBuilder();
        });

        $this->app->bind(DamageBuilder::class, function($app) {
            return new DamageBuilder($app->make(ClassRanksWeaponMasteriesBuilder::class));
        });

        $this->app->bind(HealingBuilder::class, function($app) {
            return new HealingBuilder($app->make(ClassRanksWeaponMasteriesBuilder::class));
        });

        $this->app->bind(HolyBuilder::class, function() {
            return new HolyBuilder();
        });

        $this->app->bind(ReductionsBuilder::class, function() {
            return new ReductionsBuilder();
        });

        $this->app->bind(ElementalAtonement::class, function($app) {
            return new ElementalAtonement($app->make(GemComparison::class));
        });

        $this->app->bind(CharacterStatBuilder::class, function($app) {
            return new CharacterStatBuilder(
                $app->make(DefenceBuilder::class),
                $app->make(DamageBuilder::class),
                $app->make(HealingBuilder::class),
                $app->make(HolyBuilder::class),
                $app->make(ReductionsBuilder::class),
                $app->make(ElementalAtonement::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {
    }
}
