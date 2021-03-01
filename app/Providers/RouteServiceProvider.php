<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        //

        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();

        $this->mapWebRoutes();

        $this->mapAdminRoutes();

        $this->adventureRoutes();
        $this->mapGameRoutes();
        $this->mapGameCoreApiRoutes();
        $this->mapGameMessageApiRoutes();
        $this->mapGameBattleApiRoutes();
        $this->mapGameAdventureMapApiRoutes();
        $this->mapGameSkillsApiRoutes();
        $this->mapGameKingdomApiRoutes();
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
             ->namespace($this->namespace)
             ->group(base_path('routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
             ->middleware('api')
             ->namespace($this->namespace)
             ->group(base_path('routes/api.php'));
    }

    protected function mapGameKingdomApiRoutes()
    {
        Route::prefix('api')
             ->middleware('api')
             ->namespace('App\Game\Kingdoms\Controllers')
             ->group(base_path('routes/game/kingdoms/api.php'));
    }

    protected function mapGameSkillsApiRoutes()
    {
        Route::prefix('api')
             ->middleware('api')
             ->namespace('App\Game\Skills\Controllers')
             ->group(base_path('routes/game/skills/api.php'));
    }

    protected function adventureRoutes()
    {
        Route::middleware('web')
             ->namespace('App\Game\Maps\Adventure\Controllers')
             ->group(base_path('routes/game/maps/adventure/web.php'));
    }

    protected function mapAdminRoutes()
    {
        Route::middleware('web')
             ->namespace('App\Admin\Controllers')
             ->group(base_path('routes/admin/web.php'));
    }

    protected function mapGameRoutes()
    {
        Route::middleware('web')
             ->namespace('App\Game\Core\Controllers')
             ->group(base_path('routes/game/web.php'));
    }

    protected function mapGameCoreApiRoutes()
    {
        Route::prefix('api')
             ->middleware('api')
             ->namespace('App\Game\Core\Controllers')
             ->group(base_path('routes/game/api.php'));
    }

    protected function mapGameMessageApiRoutes()
    {
        Route::prefix('api')
             ->middleware('api')
             ->namespace('App\Game\Messages\Controllers')
             ->group(base_path('routes/game/messages/api.php'));
    }

    protected function mapGameBattleApiRoutes()
    {
        Route::prefix('api')
             ->middleware('api')
             ->namespace('App\Game\Battle\Controllers')
             ->group(base_path('routes/game/battle/api.php'));
    }

    protected function mapGameAdventureMapApiRoutes()
    {
        Route::prefix('api')
             ->middleware('api')
             ->namespace('App\Game\Maps\Adventure\Controllers')
             ->group(base_path('routes/game/maps/adventure/api.php'));
    }
}
