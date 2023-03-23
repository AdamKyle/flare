<?php

namespace App\Game\Core\Gems\Providers;

use App\Flare\Builders\BuildMythicItem;
use App\Flare\Builders\RandomAffixGenerator;
use App\Flare\Services\BuildCharacterAttackTypes;
use App\Flare\Services\CharacterRewardService;
use App\Flare\Services\CharacterXPService;
use App\Flare\Transformers\CharacterAttackTransformer;
use App\Flare\Transformers\CharacterGemsTransformer;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Flare\Transformers\InventoryTransformer;
use App\Flare\Transformers\Serializers\CoreSerializer;
use App\Flare\Transformers\UsableItemTransformer;
use App\Game\Battle\Services\BattleDrop;
use App\Game\Core\Gems\Services\GemComparison;
use App\Game\Core\Handlers\HandleGoldBarsAsACurrency;
use App\Game\Core\Services\CharacterPassiveSkills;
use App\Game\Core\Services\DropCheckService;
use App\Game\Core\Services\HolyItemService;
use App\Game\Core\Services\InventorySetService;
use App\Game\Core\Services\RandomEnchantmentService;
use App\Game\Core\Services\SeerService;
use App\Game\Core\Services\UseItemService;
use App\Game\Kingdoms\Handlers\UpdateKingdomHandler;
use App\Game\Skills\Services\MassDisenchantService;
use App\Game\Skills\Services\UpdateCharacterSkillsService;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use League\Fractal\Manager;
use App\Game\Core\Comparison\ItemComparison;
use App\Game\Core\Middleware\IsCharacterAtLocationMiddleware;
use App\Game\Core\Middleware\IsCharacterWhoTheySayTheyAre;
use App\Game\Core\Services\AdventureRewardService;
use App\Game\Core\Services\CharacterInventoryService;
use App\Game\Core\Services\CharacterService;
use App\Game\Core\Services\CraftingSkillService;
use App\Game\Core\Services\EquipItemService;
use App\Game\Core\Services\ShopService;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(GemComparison::class, function($app) {
            return new GemComparison($app->make(CharacterGemsTransformer::class), $app->make(Manager::class));
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
    }
}
