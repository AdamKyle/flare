<?php

namespace App\Flare\Providers;


use App\Flare\Builders\AffixAttributeBuilder;
use App\Flare\Builders\Character\AttackDetails\CharacterAffixInformation;
use App\Flare\Builders\Character\AttackDetails\CharacterHealthInformation;
use App\Flare\Builders\Character\AttackDetails\CharacterLifeStealing;
use App\Flare\Builders\Character\AttackDetails\DamageDetails\DamageSpellInformation;
use App\Flare\Builders\Character\AttackDetails\DamageDetails\WeaponInformation;
use App\Flare\Builders\Character\BaseCharacterInfo;
use App\Flare\Builders\Character\AttackDetails\CharacterAttackBuilder;
use App\Flare\Builders\Character\AttackDetails\CharacterAttackInformation;
use App\Flare\Builders\Character\ClassDetails\ClassBonuses;
use App\Flare\Builders\RandomAffixGenerator;
use App\Flare\Handlers\AttackExtraActionHandler;
use App\Flare\Handlers\AttackHandlers\AttackAndCastHandler;
use App\Flare\Handlers\AttackHandlers\AttackHandler;
use App\Flare\Handlers\AttackHandlers\CanHitHandler;
use App\Flare\Handlers\AttackHandlers\CastAndAttackHandler;
use App\Flare\Handlers\AttackHandlers\CastHandler;
use App\Flare\Handlers\AttackHandlers\DefendHandler;
use App\Flare\Handlers\AttackHandlers\EntrancingChanceHandler;
use App\Flare\Handlers\AttackHandlers\ItemHandler;
use App\Flare\Handlers\CharacterAttackHandler;
use App\Flare\Handlers\HealingExtraActionHandler;
use App\Flare\Handlers\MonsterAttackHandler;
use App\Flare\Handlers\SetupFightHandler;
use App\Flare\Handlers\WeaponAndMagicAttackBase;
use App\Flare\Middleware\IsCharacterLoggedInMiddleware;
use App\Flare\Middleware\IsCharacterWhoTheySayTheyAreMiddleware;
use App\Flare\Middleware\IsGloballyTimedOut;
use App\Flare\Services\BuildCharacterAttackTypes;
use App\Flare\Services\BuildMonsterCacheService;
use App\Flare\Services\CanUserEnterSiteService;
use App\Flare\Services\CharacterXPService;
use App\Flare\Services\DailyGoldDustService;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Game\Core\Services\CharacterService;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use App\Flare\Values\BaseStatValue;
use App\Flare\Builders\CharacterBuilder;
use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Builders\RandomItemDropBuilder;
use App\Flare\Builders\Character\AttackDetails\CharacterDamageInformation;
use App\Flare\Cache\CoordinatesCache;
use App\Flare\Handlers\MessageThrottledHandler;
use App\Flare\Middleware\IsCharacterDeadMiddleware;
use App\Flare\Middleware\IsPlayerBannedMiddleware;
use App\Flare\Services\CharacterRewardService;
use App\Flare\Services\FightService;
use App\Flare\Transformers\KingdomBuildingTransformer;
use App\Flare\Transformers\CharacterAttackTransformer;
use App\Flare\Transformers\ItemTransformer;
use App\Flare\Transformers\KingdomTransformer;
use App\Flare\Transformers\MarketItemsTransformer;
use App\Flare\Transformers\MonsterTransformer;
use App\Flare\Transformers\UnitTransformer;
use App\Flare\Values\BaseSkillValue;
use App\Flare\View\Components\AdventureLogs;
use App\Flare\View\Components\ItemDisplayColor;
use App\Flare\Builders\Character\ClassDetails\HolyStacks;
use App\Flare\View\Livewire\Admin\Items\Validators\ItemValidator;
use Blade;
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
        $this->app->bind(BaseStatValue::class, function () {
            return new BaseStatValue();
        });

        $this->app->bind(CharacterBuilder::class, function ($app) {
            return new CharacterBuilder(
                $app->make(BuildCharacterAttackTypes::class)
            );
        });

        $this->app->bind(HolyStacks::class, function() {
            return new HolyStacks();
        });

        $this->app->bind(WeaponInformation::class, function($app) {
            return new WeaponInformation($app->make(HolyStacks::class));
        });

        $this->app->bind(DamageSpellInformation::class, function($app) {
            return new DamageSpellInformation(
                $app->make(ClassBonuses::class)
            );
        });

        $this->app->bind(CharacterDamageInformation::class, function($app) {
            return new CharacterDamageInformation(
                $app->make(WeaponInformation::class),
                $app->make(DamageSpellInformation::class)
            );
        });

        $this->app->bind(CharacterLifeStealing::class, function() {
            return new CharacterLifeStealing();
        });

        $this->app->bind(CharacterAffixInformation::class, function($app) {
            return new CharacterAffixInformation(
                $app->make(CharacterLifeStealing::class)
            );
        });

        $this->app->bind(CharacterAttackInformation::class, function($app) {
            return new CharacterAttackInformation(
                $app->make(CharacterDamageInformation::class),
                $app->make(CharacterAffixInformation::class)
            );
        });

        $this->app->bind(AffixAttributeBuilder::class, function() {
            return new AffixAttributeBuilder();
        });


        $this->app->bind(DailyGoldDustService::class, function() {
            return new DailyGoldDustService();
        });

        $this->app->bind(RandomAffixGenerator::class, function($app) {
            return new RandomAffixGenerator(
                $app->make(AffixAttributeBuilder::class)
            );
        });

        $this->app->bind(ClassBonuses::class, function($app) {
            return new ClassBonuses();
        });

        $this->app->bind(BaseCharacterInfo::class, function($app) {
            return new BaseCharacterInfo(
                $app->make(ClassBonuses::class)
            );
        });

        $this->app->bind(CharacterInformationBuilder::class, function($app) {
            return new CharacterInformationBuilder(
                $app->make(BaseCharacterInfo::class),
                $app->make(CharacterAttackInformation::class),
            );
        });

        $this->app->bind(CharacterHealthInformation::class, function($app) {
            return new CharacterHealthInformation(
                $app->make(CharacterInformationBuilder::class),
                $app->make(ClassBonuses::class),
                $app->make(HolyStacks::class)
            );
        });

        $this->app->bind(CharacterAttackBuilder::class, function($app) {
            return new CharacterAttackBuilder(
                $app->make(CharacterInformationBuilder::class),
                $app->make(CharacterHealthInformation::class),
                $app->make(CharacterAffixInformation::class),
                $app->make(HolyStacks::class)
            );
        });


        $this->app->bind(RandomItemDropBuilder::class, function($app) {
            return new RandomItemDropBuilder();
        });

        $this->app->bind(CharacterAttackTransformer::class, function($app) {
            return new CharacterAttackTransformer();
        });

        $this->app->bind(CharacterSheetBaseInfoTransformer::class, function() {
            return new CharacterSheetBaseInfoTransformer();
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

        $this->app->bind(MonsterTransformer::class, function($app){
            return new MonsterTransformer();
        });

        $this->app->bind(MarketItemsTransformer::class, function($app){
            return new MarketItemsTransformer();
        });

        $this->app->bind(ItemTransformer::class, function($app) {
            return new ItemTransformer();
        });

        $this->app->bind(BaseSkillValue::class, function($app) {
            return new BaseSkillValue();
        });

        $this->app->bind(CoordinatesCache::class, function($app) {
            return new CoordinatesCache();
        });

        $this->app->bind(CharacterXPService::class, function() {
            return new CharacterXPService();
        });

        $this->app->bind(CharacterRewardService::class, function($app) {
            return new CharacterRewardService(
                $app->make(CharacterXPService::class),
                $app->make(CharacterService::class),
                $app->make(Manager::class),
                $app->make(CharacterSheetBaseInfoTransformer::class)
            );

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
                $app->make(HolyStacks::class),
                $app->make(BuildMonsterCacheService::class)
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

        $this->app->bind(MonsterAttackHandler::class, function($app) {
            return new MonsterAttackHandler(
                $app->make(CharacterInformationBuilder::class),
                $app->make(CharacterAffixInformation::class),
                $app->make(EntrancingChanceHandler::class),
                $app->make(ItemHandler::class),
                $app->make(CanHitHandler::class),
            );
        });

        $this->app->bind(HealingExtraActionHandler::class, function($app) {
            return new HealingExtraActionHandler();
        });

        $this->app->bind(AttackExtraActionHandler::class, function($app) {
            return new AttackExtraActionHandler();
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

        $this->app->bind(CastHandler::class, function($app) {
            return new CastHandler(
                $app->make(CharacterAttackBuilder::class),
                $app->make(EntrancingChanceHandler::class),
                $app->make(AttackExtraActionHandler::class),
                $app->make(HealingExtraActionHandler::class),
                $app->make(ItemHandler::class),
                $app->make(CanHitHandler::class),
            );
        });

        $this->app->bind(CastAndAttackHandler::class, function($app) {
            return new CastAndAttackHandler(
                $app->make(CharacterAttackBuilder::class),
                $app->make(EntrancingChanceHandler::class),
                $app->make(AttackExtraActionHandler::class),
                $app->make(CastHandler::class),
                $app->make(ItemHandler::class),
                $app->make(CanHitHandler::class),
            );
        });

        $this->app->bind(AttackAndCastHandler::class, function($app) {
            return new AttackAndCastHandler(
                $app->make(CharacterAttackBuilder::class),
                $app->make(EntrancingChanceHandler::class),
                $app->make(AttackExtraActionHandler::class),
                $app->make(CastHandler::class),
                $app->make(ItemHandler::class),
                $app->make(CanHitHandler::class),
            );
        });

        $this->app->bind(DefendHandler::class, function($app) {
            return new DefendHandler(
                $app->make(CharacterAttackBuilder::class),
                $app->make(EntrancingChanceHandler::class),
                $app->make(AttackExtraActionHandler::class),
                $app->make(ItemHandler::class),
            );
        });

        $this->app->bind(CharacterAttackHandler::class, function($app) {
            return new CharacterAttackHandler(
                $app->make(AttackHandler::class),
                $app->make(CastHandler::class),
                $app->make(CastAndAttackHandler::class),
                $app->make(AttackAndCastHandler::class),
                $app->make(DefendHandler::class),
            );
        });

        $this->app->bind(FightService::class, function($app) {
            return new FightService(
                $app->make(SetupFightHandler::class),
                $app->make(CharacterInformationBuilder::class),
                $app->make(CharacterAttackHandler::class),
                $app->make(MonsterAttackHandler::class),
            );
        });

        $this->app->bind(BuildMonsterCacheService::class, function($app) {
            return new BuildMonsterCacheService(
                $app->make(Manager::class),
                $app->make(MonsterTransformer::class)
            );
        });

        $this->app->bind(BuildCharacterAttackTypes::class, function($app) {
            return new BuildCharacterAttackTypes(
                $app->make(CharacterAttackBuilder::class)
            );
        });

        $this->app->bind(CanUserEnterSiteService::class, function($app) {
           return new CanUserEnterSiteService();
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
