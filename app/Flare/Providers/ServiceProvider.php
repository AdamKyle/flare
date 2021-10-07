<?php

namespace App\Flare\Providers;


use App\Flare\Builders\CharacterAttackBuilder;
use App\Flare\Handlers\AttackExtraActionHandler;
use App\Flare\Handlers\AttackHandlers\AttackHandler;
use App\Flare\Handlers\AttackHandlers\CanHitHandler;
use App\Flare\Handlers\AttackHandlers\EntrancingChanceHandler;
use App\Flare\Handlers\AttackHandlers\ItemHandler;
use App\Flare\Handlers\CharacterAttackHandler;
use App\Flare\Handlers\HealingExtraActionHandler;
use App\Flare\Handlers\SetupFightHandler;
use App\Flare\Middleware\IsCharacterLoggedInMiddleware;
use App\Flare\Middleware\IsCharacterWhoTheySayTheyAreMiddleware;
use App\Flare\Middleware\IsGloballyTimedOut;
use App\Flare\View\Components\EquipmentButtonForm;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use App\Flare\Values\BaseStatValue;
use App\Flare\Builders\CharacterBuilder;
use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Builders\RandomItemDropBuilder;
use App\Flare\Cache\CoordinatesCache;
use App\Flare\Handlers\MessageThrottledHandler;
use App\Flare\Middleware\IsCharacterDeadMiddleware;
use App\Flare\Middleware\IsPlayerBannedMiddleware;
use App\Flare\Services\CharacterRewardService;
use App\Flare\Services\FightService;
use App\Flare\Transformers\KingdomBuildingTransformer;
use App\Flare\Transformers\CharacterAttackTransformer;
use App\Flare\Transformers\CharacterSheetTransformer;
use App\Flare\Transformers\ItemTransfromer;
use App\Flare\Transformers\KingdomTransformer;
use App\Flare\Transformers\MarketItemsTransfromer;
use App\Flare\Transformers\MonsterTransfromer;
use App\Flare\Transformers\UnitTransformer;
use App\Flare\Values\BaseSkillValue;
use App\Flare\View\Components\AdventureLogs;
use App\Flare\View\Components\ItemDisplayColor;
use App\Flare\View\Livewire\Admin\Items\Validators\ItemValidator;
use Blade;

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

        $this->app->bind(CharacterAttackBuilder::class, function($app) {
            return new CharacterAttackBuilder($app->make(CharacterInformationBuilder::class));
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

        $this->app->bind(KingdomTransformer::class, function($app){
            return new KingdomTransformer();
        });

        $this->app->bind(KingdomBuildingTransformer::class, function($app) {
            return new KingdomBuildingTransformer();
        });

        $this->app->bind(UnitTransformer::class, function($app) {
            return new UnitTransformer();
        });

        $this->app->bind(MonsterTransfromer::class, function($app){
            return new MonsterTransfromer();
        });

        $this->app->bind(MarketItemsTransfromer::class, function($app){
            return new MarketItemsTransfromer();
        });

        $this->app->bind(ItemTransfromer::class, function($app) {
            return new ItemTransfromer();
        });

        $this->app->bind(BaseSkillValue::class, function($app) {
            return new BaseSkillValue();
        });

        $this->app->bind(CoordinatesCache::class, function($app) {
            return new CoordinatesCache();
        });

        $this->app->bind(CharacterRewardService::class, function($app, $paramters) {
            return new CharacterRewardService($paramters['character']);
        });

        $this->app->bind(ItemValidator::class, function($app) {
            return new ItemValidator;
        });

        $this->app->bind(MessageThrottledHandler::class, function($app) {
            return new MessageThrottledHandler;
        });

        $this->app->bind(SetupFightHandler::class, function($app) {
            return new SetupFightHandler(
                $app->make(CharacterInformationBuilder::class),
            );
        });

        $this->app->bind(CharacterAttackBuilder::class, function($app) {
            return new CharacterAttackBuilder(
                $app->make(CharacterInformationBuilder::class)
            );
        });

        $this->app->bind(EntrancingChanceHandler::class, function($app) {
            return new EntrancingChanceHandler(
                $app->make(CharacterInformationBuilder::class)
            );
        });

        $this->app->bind(AttackExtraActionHandler::class, function($app) {
            return new AttackExtraActionHandler();
        });

        $this->app->bind(ItemHandler::class, function($app) {
            return new ItemHandler(
                $app->make(CharacterInformationBuilder::class)
            );
        });

        $this->app->bind(CanHitHandler::class, function($app) {
            return new CanHitHandler(
                $app->make(AttackExtraActionHandler::class),
                $app->make(CharacterInformationBuilder::class)
            );
        });

        $this->app->bind(AttackHandler::class, function($app) {
            return new AttackHandler(
                $app->make(CharacterAttackBuilder::class),
                $app->make(EntrancingChanceHandler::class),
                $app->make(AttackExtraActionHandler::class),
                $app->make(ItemHandler::class),
                $app->make(CanHitHandler::class),
            );
        });

        $this->app->bind(CharacterAttackHandler::class, function($app) {
            return new CharacterAttackHandler(
                $app->make(AttackHandler::class),
            );
        });

        $this->app->bind(FightService::class, function($app) {
            return new FightService(
                $app->make(SetupFightHandler::class),
                $app->make(CharacterInformationBuilder::class),
                $app->make(CharacterAttackHandler::class),
            );
        });

        $this->app->bind(AttackExtraActionHandler::class, function($app) {
            return new AttackExtraActionHandler();
        });

        $this->app->bind(HealingExtraActionHandler::class, function($app) {
            return new HealingExtraActionHandler();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $router = $this->app['router'];

        $router->aliasMiddleware('is.character.dead', IsCharacterDeadMiddleware::class);
        $router->aliasMiddleware('is.player.banned', IsPlayerBannedMiddleware::class);
        $router->aliasMiddleware('is.character.who.they.say.they.are', IsCharacterWhoTheySayTheyAreMiddleware::class);
        $router->aliasMiddleware('is.character.logged.in', IsCharacterLoggedInMiddleware::class);
        $router->aliasMiddleware('is.globally.timed.out', IsGloballyTimedOut::class);

        // Blade Components - Cross System:
        Blade::component('item-display-color', ItemDisplayColor::class);
        Blade::component('adventure-logs', AdventureLogs::class);
    }
}
