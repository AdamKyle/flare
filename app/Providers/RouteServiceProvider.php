<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
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
    public function boot() {
        $this->configureRateLimiting();

        parent::boot();
    }

    /**
     * Custom Rate Limiters go here.
     */
    protected function configureRateLimiting() {

        // When sending public or private messages
        RateLimiter::for('chat', function(Request $request) {
            return Limit::perMinute(25)->by($request->ip());
        });

        // When fighting monsters
        RateLimiter::for('fighting', function(Request $request) {
            return Limit::perMinute(100)->by($request->ip());
        });

        // When crafting items
        RateLimiter::for('crafting', function(Request $request) {
            return Limit::perMinute(25)->by($request->ip());
        });

        // When enchanting items
        RateLimiter::for('enchanting', function(Request $request) {
            return Limit::perMinute(25)->by($request->ip());
        });

        // When moving around the map (including traversing)
        RateLimiter::for('moving', function(Request $request) {
            return Limit::perMinute(100)->by($request->ip());
        });
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map() {

        // Map Routes:
        $this->mapWebRoutes();
        $this->mapAdminRoutes();
        $this->mapAdventureRoutes();
        $this->mapQuestRoutes();
        $this->mapKingdomRoutes();
        $this->mapGameMarketRoutes();
        $this->mapGameCoreRoutes();

        // Api Routes:
        $this->mapAdminApiRoutes();
        $this->mapApiRoutes();
        $this->mapGameCoreApiRoutes();
        $this->mapGameMarketApiRoutes();
        $this->mapGameMessageApiRoutes();
        $this->mapGameBattleApiRoutes();
        $this->mapGameMapApiRoutes();
        $this->mapGameAdventuresApiRoutes();
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
    protected function mapWebRoutes() {
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
    protected function mapApiRoutes() {
        Route::prefix('api')
             ->middleware('api')
             ->namespace($this->namespace)
             ->group(base_path('routes/api.php'));
    }

    protected function mapGameKingdomApiRoutes() {
        Route::prefix('api')
             ->middleware('web')
             ->namespace('App\Game\Kingdoms\Controllers')
             ->group(base_path('routes/game/kingdoms/api.php'));
    }

    protected function mapGameSkillsApiRoutes() {
        Route::prefix('api')
             ->middleware('web')
             ->namespace('App\Game\Skills\Controllers')
             ->group(base_path('routes/game/skills/api.php'));
    }

    protected function mapAdventureRoutes() {
        Route::middleware('web')
             ->namespace('App\Game\Adventures\Controllers')
             ->group(base_path('routes/game/adventures/web.php'));
    }

    protected function mapAdminRoutes() {
        Route::middleware('web')
             ->namespace('App\Admin\Controllers')
             ->group(base_path('routes/admin/web.php'));
    }

    protected function mapAdminApiRoutes() {
        Route::prefix('api')
            ->middleware('web')
            ->namespace('App\Admin\Controllers')
            ->group(base_path('routes/admin/api.php'));
    }

    protected function mapGameCoreRoutes() {
        Route::middleware('web')
             ->namespace('App\Game\Core\Controllers')
             ->group(base_path('routes/game/web.php'));
    }

    protected function mapKingdomRoutes() {
        Route::middleware('web')
            ->namespace('App\Game\Kingdoms\Controllers')
            ->group(base_path('routes/game/kingdoms/web.php'));
    }

    protected function mapGameMarketRoutes() {
        Route::middleware('web')
            ->namespace('App\Game\Market\Controllers')
            ->group(base_path('routes/game/market-board/web.php'));
    }

    protected function mapQuestRoutes() {
        Route::middleware('web')
            ->namespace('App\Game\Quests\Controllers')
            ->group(base_path('routes/game/quests/web.php'));
    }

    protected function mapGameCoreApiRoutes() {
        Route::prefix('api')
             ->middleware('web')
             ->namespace('App\Game\Core\Controllers')
             ->group(base_path('routes/game/api.php'));
    }

    protected function mapGameMessageApiRoutes() {
        Route::prefix('api')
             ->middleware('web')
             ->namespace('App\Game\Messages\Controllers')
             ->group(base_path('routes/game/messages/api.php'));
    }

    protected function mapGameBattleApiRoutes() {
        Route::prefix('api')
             ->middleware('web')
             ->namespace('App\Game\Battle\Controllers')
             ->group(base_path('routes/game/battle/api.php'));
    }

    protected function mapGameAdventuresApiRoutes() {
        Route::prefix('api')
             ->middleware('web')
             ->namespace('App\Game\Adventures\Controllers')
             ->group(base_path('routes/game/adventures/api.php'));
    }

    protected function mapGameMapApiRoutes() {
        Route::prefix('api')
             ->middleware('web')
             ->namespace('App\Game\Maps\Controllers')
             ->group(base_path('routes/game/maps/api.php'));
    }

    protected function mapGameMarketApiRoutes() {
        Route::prefix('api')
            ->middleware('web')
            ->namespace('App\Game\Market\Controllers')
            ->group(base_path('routes/game/market-board/api.php'));
    }
}
