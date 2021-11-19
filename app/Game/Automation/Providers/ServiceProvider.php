<?php

namespace App\Game\Automation\Providers;


use App\Flare\Services\BuildCharacterAttackTypes;
use App\Flare\Services\FightService;
use App\Flare\Transformers\CharacterAttackTransformer;
use App\Flare\Transformers\MonsterTransfromer;
use App\Game\Automation\Console\Commands\ClearAutoAttackTimeOuts;
use App\Game\Automation\Middleware\IsCharacterInAttackAutomation;
use App\Game\Automation\Services\AttackAutomationService;
use App\Game\Automation\Services\ProcessAttackAutomation;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Skills\Services\SkillService;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use App\Game\Messages\Console\Commands\CleanChat;
use App\Game\Messages\Builders\NpcServerMessageBuilder;
use App\Game\Messages\Handlers\NpcCommandHandler;
use League\Fractal\Manager;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {

        $this->app->bind(AttackAutomationService::class, function($app) {
            return new AttackAutomationService(
                $app->make(SkillService::class)
            );
        });

        $this->app->bind(ProcessAttackAutomation::class, function($app) {
            return new ProcessAttackAutomation(
                $app->make(FightService::class),
                $app->make(BattleEventHandler::class),
            );
        });

        $this->commands([ClearAutoAttackTimeOuts::class]);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $router = $this->app['router'];

        $router->aliasMiddleware('character.attack.automation', IsCharacterInAttackAutomation::class);
    }
}
