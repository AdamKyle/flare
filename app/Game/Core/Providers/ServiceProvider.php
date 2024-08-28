<?php

namespace App\Game\Core\Providers;

use App\Flare\Builders\BuildMythicItem;
use App\Flare\Transformers\Serializers\CoreSerializer;
use App\Game\Battle\Services\BattleDrop;
use App\Game\Core\Comparison\ItemComparison;
use App\Game\Core\Services\CharacterPassiveSkills;
use App\Game\Core\Services\CharactersOnline;
use App\Game\Core\Services\DropCheckService;
use App\Game\Core\Services\GoldRush;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use League\Fractal\Manager;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(Manager::class, function ($app) {
            $manager = new Manager;

            // Attach the serializer
            $manager->setSerializer(new CoreSerializer);

            return $manager;
        });

        $this->app->bind(CharacterPassiveSkills::class, function () {
            return new CharacterPassiveSkills;
        });

        $this->app->bind(GoldRush::class, function () {
            return new GoldRush;
        });

        $this->app->bind(DropCheckService::class, function ($app) {
            return new DropCheckService(
                $app->make(BattleDrop::class),
                $app->make(BuildMythicItem::class)
            );
        });

        $this->app->bind(ItemComparison::class, function ($app) {
            return new ItemComparison;
        });

        $this->app->bind(CharactersOnline::class, function($app) {
            return new CharactersOnline;
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {}
}
