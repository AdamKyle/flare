<?php

namespace App\Game\ClassRanks\Providers;

use App\Game\BattleRewardProcessing\Handlers\BattleMessageHandler;
use App\Game\Character\Builders\AttackBuilders\Handler\UpdateCharacterAttackTypesHandler;
use App\Game\ClassRanks\Console\Commands\AssignNewClassRanks;
use App\Game\ClassRanks\Services\ClassRankService;
use App\Game\ClassRanks\Services\ManageClassService;
use App\Game\ClassRanks\Transformers\ClassDetailTransformer;
use App\Game\ClassRanks\Transformers\ClassMasteryDetailTransformer;
use App\Game\Gems\Services\AreaGemEffectService;
use App\Game\Skills\Builders\BaseSkillBuilder;
use App\Game\Skills\Services\UpdateCharacterSkillsService;
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
        $this->commands([
            AssignNewClassRanks::class,
        ]);
        $this->app->bind(ClassRankService::class, function ($app) {
            return new ClassRankService(
                $app->make(UpdateCharacterAttackTypesHandler::class),
                $app->make(BattleMessageHandler::class),
                $app->make(AreaGemEffectService::class),
                $app->make(ClassDetailTransformer::class),
                $app->make(ClassMasteryDetailTransformer::class),
            );
        });

        $this->app->bind(ManageClassService::class, function ($app) {
            return new ManageClassService(
                $app->make(UpdateCharacterAttackTypesHandler::class),
                $app->make(UpdateCharacterSkillsService::class),
                $app->make(ClassRankService::class),
                $app->make(BaseSkillBuilder::class),
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
        // ...
    }
}
