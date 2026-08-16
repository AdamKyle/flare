<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        $this->configureRateLimiting();

        parent::boot();
    }

    /**
     * Register the application's custom rate limiters.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {

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
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {

        // Map Routes:
        $this->mapWebRoutes();
        $this->mapAdminRoutes();
        $this->mapQuestRoutes();
        $this->mapGuideQuestsRoutes();
        $this->mapGameMarketRoutes();
        $this->mapCharacterPassiveSkillsRoutes();
        $this->mapGameCoreRoutes();
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
        $this->mapCharacterSheetRoutes();
        $this->mapCharacterInventoryRoutes();
        $this->mapExplorationAutomationApiRoutes();
        $this->mapDelveAutomationApiRoutes();
        $this->mapFactionLoyaltyAutomationApiRoutes();
        $this->mapBatchCraftingAutomationApiRoutes();
        $this->mapGameCoreApiRoutes();
        $this->mapGameMarketApiRoutes();
        $this->mapGameMessageApiRoutes();
        $this->mapMonstersApiRoutes();
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
        $this->mapTopsApiRoutes();
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
     * Define the Game Core Gem routes.
     *
     * @return void
     */
    protected function mapGemRoutes()
    {
        Route::prefix('api')
            ->middleware(['web', 'update.player-activity'])
            ->namespace('App\Game\Gems\Controllers')
            ->group(base_path('routes/game/gems/api.php'));
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
            ->middleware(['api', 'update.player-activity'])
            ->namespace($this->namespace)
            ->group(base_path('routes/api.php'));
    }

    /**
     * Define the Monsters api routes.
     *
     * @return void
     */
    protected function mapMonstersApiRoutes()
    {
        Route::prefix('api')
            ->middleware(['web', 'update.player-activity'])
            ->namespace('App\Game\Monsters\Controllers')
            ->group(base_path('routes/game/monsters/api.php'));
    }

    /**
     * Define the Character Sheet api routes.
     *
     * @return void
     */
    protected function mapCharacterSheetRoutes()
    {
        Route::prefix('api')
            ->middleware(['web', 'update.player-activity'])
            ->namespace('App\Game\Character\CharacterSheet\Controllers')
            ->group(base_path('routes/game/character/character-sheet/api.php'));
    }

    /**
     * Define the Character Inventory api routes.
     *
     * @return void
     */
    protected function mapCharacterInventoryRoutes()
    {
        Route::prefix('api')
            ->middleware(['web', 'update.player-activity'])
            ->namespace('App\Game\Character\CharacterInventory\Controllers')
            ->group(base_path('routes/game/character/character-inventory/api.php'));
    }

    /**
     * Define the Exploration automation api routes.
     *
     * @return void
     */
    protected function mapExplorationAutomationApiRoutes()
    {
        Route::prefix('api')
            ->middleware(['web', 'update.player-activity'])
            ->namespace('App\Game\Automation\Exploration\Controllers')
            ->group(base_path('routes/game/automation/exploration/api.php'));
    }

    /**
     * Define the Delve automation api routes.
     *
     * @return void
     */
    protected function mapDelveAutomationApiRoutes()
    {
        Route::prefix('api')
            ->middleware(['web', 'update.player-activity'])
            ->namespace('App\Game\Automation\Delve\Controllers')
            ->group(base_path('routes/game/automation/delve/api.php'));
    }

    /**
     * Define the Faction Loyalty automation api routes.
     *
     * @return void
     */
    protected function mapFactionLoyaltyAutomationApiRoutes()
    {
        Route::prefix('api')
            ->middleware(['web', 'update.player-activity'])
            ->namespace('App\Game\Automation\FactionLoyalty\Controllers')
            ->group(base_path('routes/game/automation/faction-loyalty/api.php'));
    }

    /**
     * Define the Batch Crafting automation api routes.
     *
     * @return void
     */
    protected function mapBatchCraftingAutomationApiRoutes()
    {
        Route::prefix('api')
            ->middleware(['web', 'update.player-activity'])
            ->namespace('App\Game\Automation\BatchCrafting\Controllers')
            ->group(base_path('routes/game/automation/batch-crafting/api.php'));
    }

    /**
     * Define the Kingdoms api routes.
     *
     * @return void
     */
    protected function mapGameKingdomApiRoutes()
    {
        Route::prefix('api')
            ->middleware(['web', 'update.player-activity'])
            ->namespace('App\Game\Kingdoms\Controllers')
            ->group(base_path('routes/game/kingdoms/api.php'));
    }

    /**
     * Define the Skills api routes.
     *
     * @return void
     */
    protected function mapGameSkillsApiRoutes()
    {
        Route::prefix('api')
            ->middleware(['web', 'update.player-activity'])
            ->namespace('App\Game\Skills\Controllers')
            ->group(base_path('routes/game/skills/api.php'));
    }

    /**
     * Define the Shop api routes.
     *
     * @return void
     */
    protected function mapShopApiRoutes()
    {
        Route::prefix('api')
            ->middleware(['web', 'update.player-activity'])
            ->namespace('App\Game\Shop\Controllers')
            ->group(base_path('routes/game/shop/api.php'));
    }

    /**
     * Define the Admin web routes.
     *
     * @return void
     */
    protected function mapAdminRoutes()
    {
        Route::middleware('web')
            ->namespace('App\Admin\Controllers')
            ->group(base_path('routes/admin/web.php'));
    }

    /**
     * Define the Admin api routes.
     *
     * @return void
     */
    protected function mapAdminApiRoutes()
    {
        Route::prefix('api')
            ->middleware(['web', 'update.player-activity'])
            ->namespace('App\Admin\Controllers')
            ->group(base_path('routes/admin/api.php'));
    }

    /**
     * Define the Game Core web routes.
     *
     * @return void
     */
    protected function mapGameCoreRoutes()
    {
        Route::middleware('web')
            ->namespace('App\Game\Core\Controllers')
            ->group(base_path('routes/game/web.php'));
    }

    /**
     * Define the Market web routes.
     *
     * @return void
     */
    protected function mapGameMarketRoutes()
    {
        Route::middleware('web')
            ->namespace('App\Game\Market\Controllers')
            ->group(base_path('routes/game/market-board/web.php'));
    }

    /**
     * Define the Quests web routes.
     *
     * @return void
     */
    protected function mapQuestRoutes()
    {
        Route::middleware('web')
            ->namespace('App\Game\Quests\Controllers')
            ->group(base_path('routes/game/quests/web.php'));
    }

    /**
     * Define the Guide Quests web routes.
     *
     * @return void
     */
    protected function mapGuideQuestsRoutes()
    {
        Route::middleware('web')
            ->namespace('App\Game\GuideQuests\Controllers')
            ->group(base_path('routes/game/guide-quests/web.php'));
    }

    /**
     * Define the Character Passive Skills web routes.
     *
     * @return void
     */
    protected function mapCharacterPassiveSkillsRoutes()
    {
        Route::middleware('web')
            ->namespace('App\Game\PassiveSkills\Controllers')
            ->group(base_path('routes/game/passive-skills/web.php'));
    }

    /**
     * Define the Gambler api routes.
     *
     * @return void
     */
    protected function mapGamblingRoutes()
    {
        Route::prefix('api')
            ->middleware(['web', 'update.player-activity'])
            ->namespace('App\Game\Gambler\Controllers')
            ->group(base_path('routes/game/gambler/api.php'));
    }

    /**
     * Define the Game Core api routes.
     *
     * @return void
     */
    protected function mapGameCoreApiRoutes()
    {
        Route::prefix('api')
            ->middleware(['web'])
            ->namespace('App\Game\Core\Controllers')
            ->group(base_path('routes/game/api.php'));
    }

    /**
     * Define the Messages api routes.
     *
     * @return void
     */
    protected function mapGameMessageApiRoutes()
    {
        Route::prefix('api')
            ->middleware('web')
            ->namespace('App\Game\Messages\Controllers')
            ->group(base_path('routes/game/messages/api.php'));
    }

    /**
     * Define the Battle api routes.
     *
     * @return void
     */
    protected function mapGameBattleApiRoutes()
    {
        Route::prefix('api')
            ->middleware(['web', 'update.player-activity'])
            ->namespace('App\Game\Battle\Controllers')
            ->group(base_path('routes/game/battle/api.php'));
    }

    /**
     * Define the Maps api routes.
     *
     * @return void
     */
    protected function mapGameMapApiRoutes()
    {
        Route::prefix('api')
            ->middleware('web')
            ->namespace('App\Game\Maps\Controllers')
            ->group(base_path('routes/game/maps/api.php'));
    }

    /**
     * Define the Market api routes.
     *
     * @return void
     */
    protected function mapGameMarketApiRoutes()
    {
        Route::prefix('api')
            ->middleware(['web', 'update.player-activity'])
            ->namespace('App\Game\Market\Controllers')
            ->group(base_path('routes/game/market-board/api.php'));
    }

    /**
     * Define the Quests api routes.
     *
     * @return void
     */
    protected function mapQuestApiRoutes()
    {
        Route::middleware(['web', 'update.player-activity'])
            ->namespace('App\Game\Quests\Controllers')
            ->group(base_path('routes/game/quests/api.php'));
    }

    /**
     * Define the Passive Skills api routes.
     *
     * @return void
     */
    protected function mapGamePassiveSkillApiRoutes()
    {
        Route::prefix('api')
            ->middleware('web')
            ->namespace('App\Game\PassiveSkills\Controllers')
            ->group(base_path('routes/game/passive-skills/api.php'));
    }

    /**
     * Define the Guide Quests api routes.
     *
     * @return void
     */
    protected function mapGameGuideQuestsApiRoutes()
    {
        Route::prefix('api')
            ->middleware(['web', 'update.player-activity'])
            ->namespace('App\Game\GuideQuests\Controllers')
            ->group(base_path('routes/game/guide-quests/api.php'));
    }

    /**
     * Define the Specialty Shops api routes.
     *
     * @return void
     */
    protected function mapSpecialtyShopApiRoutes()
    {
        Route::prefix('api')
            ->middleware(['web', 'update.player-activity'])
            ->namespace('App\Game\SpecialtyShops\Controllers')
            ->group(base_path('routes/game/specialty-shops/api.php'));
    }

    /**
     * Define the Reincarnate api routes.
     *
     * @return void
     */
    protected function mapReincarnateApiRoutes()
    {
        Route::prefix('api')
            ->middleware(['web', 'update.player-activity'])
            ->namespace('App\Game\Reincarnate\Controllers')
            ->group(base_path('routes/game/reincarnate/api.php'));
    }

    /**
     * Define the Class Ranks api routes.
     *
     * @return void
     */
    protected function mapClassRanksApiRoutes()
    {
        Route::prefix('api')
            ->middleware(['web', 'update.player-activity'])
            ->namespace('App\Game\ClassRanks\Controllers')
            ->group(base_path('routes/game/class-ranks/api.php'));
    }

    /**
     * Define the Seer npc action api routes.
     *
     * @return void
     */
    protected function mapSeerActions()
    {
        Route::prefix('api')
            ->middleware(['web', 'update.player-activity'])
            ->namespace('App\Game\Npcs\Actions\Seer\Controllers')
            ->group(base_path('routes/game/npc-actions/seer-actions/api.php'));
    }

    /**
     * Define the Queen of Hearts npc action api routes.
     *
     * @return void
     */
    protected function mapQueenOfHeartsActions()
    {
        Route::prefix('api')
            ->middleware(['web', 'update.player-activity'])
            ->namespace('App\Game\Npcs\Actions\QueenOfHearts\Controllers')
            ->group(base_path('routes/game/npc-actions/queen-of-hearts/api.php'));
    }

    /**
     * Define the Labyrinth Oracle npc action api routes.
     *
     * @return void
     */
    protected function mapLabyrinthOracleRoutes()
    {
        Route::prefix('api')
            ->middleware(['web', 'update.player-activity'])
            ->namespace('App\Game\Npcs\Actions\LabyrinthOracle\Controllers')
            ->group(base_path('routes/game/npc-actions/labyrinth-oracle/api.php'));
    }

    /**
     * Define the Work Bench npc action api routes.
     *
     * @return void
     */
    protected function mapWorkBenchActions()
    {
        Route::prefix('api')
            ->middleware(['web', 'update.player-activity'])
            ->namespace('App\Game\Npcs\Actions\WorkBench\Controllers')
            ->group(base_path('routes/game/npc-actions/work-bench/api.php'));
    }

    /**
     * Define the Events api routes.
     *
     * @return void
     */
    protected function mapEvents()
    {
        Route::prefix('api')
            ->middleware(['web', 'update.player-activity'])
            ->namespace('App\Game\Events\Controllers')
            ->group(base_path('routes/game/events/api.php'));
    }

    /**
     * Define the Faction Loyalty api routes.
     *
     * @return void
     */
    protected function mapFactionLoyaltyApiRoutes()
    {
        Route::prefix('api')
            ->middleware(['web', 'update.player-activity'])
            ->namespace('App\Game\Factions\FactionLoyalty\Controllers')
            ->group(base_path('routes/game/factions/faction-loyalty/api.php'));
    }

    /**
     * Define the Tops api routes.
     *
     * @return void
     */
    protected function mapTopsApiRoutes()
    {
        Route::prefix('api')
            ->middleware(['web', 'update.player-activity'])
            ->namespace('App\Game\Tops\Controllers')
            ->group(base_path('routes/game/tops/api.php'));
    }
}
