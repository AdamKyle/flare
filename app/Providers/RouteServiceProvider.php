<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider {
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
        RateLimiter::for('chat', function (Request $request) {
            return Limit::perMinute(25)->by($request->ip());
        });

        // When fighting monsters
        RateLimiter::for('fighting', function (Request $request) {
            return Limit::perMinute(100)->by($request->ip());
        });

        // When crafting items
        RateLimiter::for('crafting', function (Request $request) {
            return Limit::perMinute(100)->by($request->ip());
        });

        // When enchanting items
        RateLimiter::for('enchanting', function (Request $request) {
            return Limit::perMinute(100)->by($request->ip());
        });

        // When moving around the map (including traversing)
        RateLimiter::for('moving', function (Request $request) {
            return Limit::perMinute(100)->by($request->ip());
        });

        RateLimiter::for('attacking', function (Request $request) {
            return Limit::perMinute(20)->by($request->ip());
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
        $this->mapQuestRoutes();
        $this->mapGuideQuestsRoutes();
        $this->mapKingdomRoutes();
        $this->mapGameMarketRoutes();
        $this->mapCharacterPassiveSkillsRoutes();
        $this->mapGameCoreRoutes();
        $this->mapShopRoutes();
        $this->mapGamblingRoutes();
        $this->mapEvents();

        // Api Routes:
        $this->mapAdminApiRoutes();

        // Game Core Api Routes:
        $this->mapGemRoutes();

        // NPC Actions:
        $this->mapSeerActions();
        $this->mapQueenOfHeartsActions();
        $this->mapWorkBenchActions();
        $this->mapLabyrinthOracleRoutes();

        // Game Api Routes
        $this->mapApiRoutes();
        $this->mapCharacterInventoryRoutes();
        $this->mapExplorationApiRoutes();
        $this->mapGameCoreApiRoutes();
        $this->mapGameMarketApiRoutes();
        $this->mapGameMessageApiRoutes();
        $this->mapGameBattleApiRoutes();
        $this->mapGameMapApiRoutes();
        $this->mapGameSkillsApiRoutes();
        $this->mapGameKingdomApiRoutes();
        $this->mapGamePassiveSkillApiRoutes();
        $this->mapQuestApiRoutes();
        $this->mapShopApiRoutes();
        $this->mapGameGuideQuestsApiRoutes();
        $this->mapSpecialtyShopApiRoutes();
        $this->mapReincarnateApiRoutes();
        $this->mapClassRanksApiRoutes();
        $this->mapFactionLoyaltyApiRoutes();
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

    protected function mapGemRoutes() {
        Route::prefix('api')
            ->middleware('web')
            ->namespace('App\Game\Gems\Controllers')
            ->group(base_path('routes/game/gems/api.php'));
    }

    protected function mapApiRoutes() {
        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->namespace)
            ->group(base_path('routes/api.php'));
    }

    protected function mapCharacterInventoryRoutes() {
        Route::prefix('api')
            ->middleware('web')
            ->namespace('App\Game\CharacterInventory\Controllers')
            ->group(base_path('routes/game/character-inventory/api.php'));
    }

    protected function mapExplorationApiRoutes() {
        Route::prefix('api')
            ->middleware('web')
            ->namespace('App\Game\Exploration\Controllers')
            ->group(base_path('routes/game/exploration/api.php'));
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

    protected function mapShopApiRoutes() {
        Route::prefix('api')
            ->middleware('web')
            ->namespace('App\Game\Shop\Controllers')
            ->group(base_path('routes/game/shop/api.php'));
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

    protected function mapGuideQuestsRoutes() {
        Route::middleware('web')
            ->namespace('App\Game\GuideQuests\Controllers')
            ->group(base_path('routes/game/guide-quests/web.php'));
    }

    protected function mapShopRoutes() {
        Route::middleware('web')
            ->namespace('App\Game\Shop\Controllers')
            ->group(base_path('routes/game/shop/web.php'));
    }

    protected function mapCharacterPassiveSkillsRoutes() {
        Route::middleware('web')
            ->namespace('App\Game\PassiveSkills\Controllers')
            ->group(base_path('routes/game/passive-skills/web.php'));
    }

    protected function mapGamblingRoutes() {
        Route::prefix('api')
            ->middleware('web')
            ->namespace('App\Game\Gambler\Controllers')
            ->group(base_path('routes/game/gambler/api.php'));
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

    protected function mapQuestApiRoutes() {
        Route::middleware('web')
            ->namespace('App\Game\Quests\Controllers')
            ->group(base_path('routes/game/quests/api.php'));
    }

    protected function mapGamePassiveSkillApiRoutes() {
        Route::prefix('api')
            ->middleware('web')
            ->namespace('App\Game\PassiveSkills\Controllers')
            ->group(base_path('routes/game/passive-skills/api.php'));
    }

    protected function mapGameGuideQuestsApiRoutes() {
        Route::prefix('api')
            ->middleware('web')
            ->namespace('App\Game\GuideQuests\Controllers')
            ->group(base_path('routes/game/guide-quests/api.php'));
    }

    protected function mapSpecialtyShopApiRoutes() {
        Route::prefix('api')
            ->middleware('web')
            ->namespace('App\Game\SpecialtyShops\Controllers')
            ->group(base_path('routes/game/specialty-shops/api.php'));
    }

    protected function mapReincarnateApiRoutes() {
        Route::prefix('api')
            ->middleware('web')
            ->namespace('App\Game\Reincarnate\Controllers')
            ->group(base_path('routes/game/reincarnate/api.php'));
    }

    protected function mapClassRanksApiRoutes() {
        Route::prefix('api')
            ->middleware('web')
            ->namespace('App\Game\ClassRanks\Controllers')
            ->group(base_path('routes/game/class-ranks/api.php'));
    }

    protected function mapSeerActions() {
        Route::prefix('api')
            ->middleware('web')
            ->namespace('App\Game\NpcActions\SeerActions\Controllers')
            ->group(base_path('routes/game/npc-actions/seer-actions/api.php'));
    }

    protected function mapQueenOfHeartsActions() {
        Route::prefix('api')
            ->middleware('web')
            ->namespace('App\Game\NpcActions\QueenOfHeartsActions\Controllers')
            ->group(base_path('routes/game/npc-actions/queen-of-hearts/api.php'));
    }

    protected function mapLabyrinthOracleRoutes() {
        Route::prefix('api')
            ->middleware('web')
            ->namespace('App\Game\NpcActions\LabyrinthOracle\Controllers')
            ->group(base_path('routes/game/npc-actions/labyrinth-oracle/api.php'));
    }

    protected function mapWorkBenchActions() {
        Route::prefix('api')
            ->middleware('web')
            ->namespace('App\Game\NpcActions\WorkBench\Controllers')
            ->group(base_path('routes/game/npc-actions/work-bench/api.php'));
    }

    protected function mapEvents() {
        Route::prefix('api')
            ->middleware('web')
            ->namespace('App\Game\Events\Controllers')
            ->group(base_path('routes/game/events/api.php'));
    }

    protected function mapFactionLoyaltyApiRoutes() {
        Route::prefix('api')
            ->middleware('web')
            ->namespace('App\Game\Factions\FactionLoyalty\Controllers')
            ->group(base_path('routes/game/factions/faction-loyalty/api.php'));
    }
}
