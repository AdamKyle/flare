<?php

namespace App\Flare\Providers;

use App\Admin\Services\SiteStatisticsService;
use App\Flare\Builders\AffixAttributeBuilder;
use App\Flare\Builders\BuildCosmicItem;
use App\Flare\Builders\BuildMythicItem;
use App\Flare\Builders\BuildUniqueItem;
use App\Flare\Builders\RandomAffixGenerator;
use App\Flare\Builders\RandomItemDropBuilder;
use App\Flare\Cache\CoordinatesCache;
use App\Flare\Handlers\MessageThrottledHandler;
use App\Flare\Items\Builders\AffixAttributeBuilder;
use App\Flare\Items\Builders\BuildMythicItem;
use App\Flare\Items\Builders\RandomAffixGenerator;
use App\Flare\Items\Builders\RandomItemDropBuilder;
use App\Flare\Middleware\IsCharacterDeadMiddleware;
use App\Flare\Middleware\IsCharacterLoggedInMiddleware;
use App\Flare\Middleware\IsCharacterWhoTheySayTheyAreMiddleware;
use App\Flare\Middleware\IsGloballyTimedOut;
use App\Flare\Middleware\IsPlayerBannedMiddleware;
use App\Flare\Middleware\TrackSessionLifeMiddleware;
use App\Flare\Middleware\UpdatePlayerSessionActivity;
use App\Flare\ServerFight\Fight\Affixes;
use App\Flare\ServerFight\Fight\Ambush;
use App\Flare\ServerFight\Fight\Attack;
use App\Flare\ServerFight\Fight\CanHit;
use App\Flare\ServerFight\Fight\CharacterAttacks\BaseCharacterAttack;
use App\Flare\ServerFight\Fight\CharacterAttacks\CharacterAttack;
use App\Flare\ServerFight\Fight\CharacterAttacks\PlayerHealing;
use App\Flare\ServerFight\Fight\CharacterAttacks\SecondaryAttacks;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\AlchemistsRavenousDream;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\BloodyPuke;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\BookBindersFear;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\DoubleAttack;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\DoubleCast;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\DoubleHeal;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\GunslingersAssassination;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\HammerSmash;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\MerchantSupply;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\PlagueSurge;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\SensualDance;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\ThiefBackStab;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\TripleAttack;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\VampireThirst;
use App\Flare\ServerFight\Fight\CharacterAttacks\Types\AttackAndCast;
use App\Flare\ServerFight\Fight\CharacterAttacks\Types\CastAndAttack;
use App\Flare\ServerFight\Fight\CharacterAttacks\Types\CastType;
use App\Flare\ServerFight\Fight\CharacterAttacks\Types\Defend;
use App\Flare\ServerFight\Fight\CharacterAttacks\Types\WeaponType;
use App\Flare\ServerFight\Fight\ElementalAttack;
use App\Flare\ServerFight\Fight\Entrance;
use App\Flare\ServerFight\Fight\MonsterAttack;
use App\Flare\ServerFight\Fight\Voidance;
use App\Flare\ServerFight\Monster\BuildMonster;
use App\Flare\ServerFight\Monster\MonsterSpecialAttack;
use App\Flare\ServerFight\Monster\ServerMonster;
use App\Flare\ServerFight\MonsterPlayerFight;
use App\Flare\Services\CanUserEnterSiteService;
use App\Flare\Services\CharacterCurrencyRewardService;
use App\Flare\Services\CharacterDeletion;
use App\Flare\Services\CharacterRewardService;
use App\Flare\Services\CharacterXPService;
use App\Flare\Services\CreateSurveySnapshot;
use App\Flare\Services\DailyGoldDustService;
use App\Flare\Services\DelveMonsterService;
use App\Flare\Services\EventSchedulerService;
use App\Flare\Services\SiteAccessStatisticService;
use App\Flare\Services\SkillBonusContextService;
use App\Flare\Transformers\BasicKingdomTransformer;
use App\Flare\Transformers\CharacterAttackTransformer;
use App\Flare\Transformers\InventoryTransformer;
use App\Flare\Transformers\ItemTransformer;
use App\Flare\Transformers\MarketItemsTransformer;
use App\Flare\Transformers\Serializer\PlainDataSerializer;
use App\Flare\Transformers\UsableItemTransformer;
use App\Flare\Values\BaseSkillValue;
use App\Flare\Values\BaseStatValue;
use App\Flare\View\Components\ItemDisplayColor;
use App\Game\BattleRewardProcessing\Handlers\BattleMessageHandler;
use App\Game\Character\Builders\AttackBuilders\AttackDetails\CharacterAttackBuilder;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\ClassRanksWeaponMasteriesBuilder;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use App\Game\Character\CharacterCreation\Services\CharacterBuilderService;
use App\Game\Character\CharacterSheet\Transformers\CharacterSheetBaseInfoTransformer;
use App\Game\Core\Services\CharacterService;
use App\Game\Kingdoms\Handlers\GiveKingdomsToNpcHandler;
use App\Game\Kingdoms\Transformers\KingdomAttackLogsTransformer;
use App\Game\Kingdoms\Transformers\KingdomBuildingTransformer;
use App\Game\Kingdoms\Transformers\KingdomTransformer;
use App\Game\Kingdoms\Transformers\OtherKingdomTransformer;
use App\Game\Kingdoms\Transformers\UnitTransformer;
use App\Game\Monsters\Services\MonsterListService;
use App\Game\Monsters\Transformers\MonsterTransformer;
use App\Game\Quests\Services\BuildQuestCacheService;
use App\Game\Quests\Transformers\QuestTransformer;
use App\Game\Skills\Services\SkillService;
use Illuminate\Support\Facades\Blade;
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

        $this->app->bind(CreateSurveySnapshot::class, function () {
            return new CreateSurveySnapshot;
        });

        $this->app->bind(BaseStatValue::class, function () {
            return new BaseStatValue;
        });

        $this->app->bind(AffixAttributeBuilder::class, function () {
            return new AffixAttributeBuilder;
        });

        $this->app->bind(DailyGoldDustService::class, function () {
            return new DailyGoldDustService;
        });

        $this->app->bind(RandomAffixGenerator::class, function ($app) {
            return new RandomAffixGenerator(
                $app->make(AffixAttributeBuilder::class)
            );
        });

        $this->app->bind(CharacterAttackBuilder::class, function ($app) {
            return new CharacterAttackBuilder(
                $app->make(CharacterStatBuilder::class)
            );
        });

        $this->app->bind(RandomItemDropBuilder::class, function ($app) {
            return new RandomItemDropBuilder;
        });

        $this->app->bind(CharacterAttackTransformer::class, function ($app) {
            return new CharacterAttackTransformer;
        });

        $this->app->bind(CharacterSheetBaseInfoTransformer::class, function () {
            return new CharacterSheetBaseInfoTransformer;
        });

        $this->app->bind(OtherKingdomTransformer::class, function ($app) {
            return new OtherKingdomTransformer;
        });

        $this->app->bind(BasicKingdomTransformer::class, function ($app) {
            return new BasicKingdomTransformer;
        });

        $this->app->bind(KingdomTransformer::class, function ($app) {
            return new KingdomTransformer;
        });

        $this->app->bind(KingdomBuildingTransformer::class, function ($app) {
            return new KingdomBuildingTransformer;
        });

        $this->app->bind(UnitTransformer::class, function ($app) {
            return new UnitTransformer;
        });

        $this->app->bind(MonsterTransformer::class, function ($app) {
            return new MonsterTransformer;
        });

        $this->app->bind(MarketItemsTransformer::class, function ($app) {
            return new MarketItemsTransformer;
        });

        $this->app->bind(ItemTransformer::class, function ($app) {
            return new ItemTransformer;
        });

        $this->app->bind(UsableItemTransformer::class, function ($app) {
            return new UsableItemTransformer;
        });

        $this->app->bind(InventoryTransformer::class, function () {
            return new InventoryTransformer;
        });

        $this->app->bind(KingdomAttackLogsTransformer::class, function () {
            return new KingdomAttackLogsTransformer;
        });

        $this->app->bind(BaseSkillValue::class, function ($app) {
            return new BaseSkillValue;
        });

        $this->app->bind(CoordinatesCache::class, function ($app) {
            return new CoordinatesCache;
        });

        $this->app->bind(CharacterXPService::class, function ($app) {
            return new CharacterXPService(
                $app->make(CharacterService::class),
                $app->make(SkillService::class),
                $app->make(Manager::class),
                $app->make(CharacterSheetBaseInfoTransformer::class),
                $app->make(BattleMessageHandler::class),
            );
        });

        $this->app->bind(CharacterRewardService::class, function ($app) {
            return new CharacterRewardService(
                $app->make(CharacterXPService::class),
                $app->make(CharacterCurrencyRewardService::class),
                $app->make(SkillService::class),
                $app->make(BuildUniqueItem::class),
                $app->make(BuildMythicItem::class),
                $app->make(BuildCosmicItem::class)
            );
        });

        $this->app->bind(MessageThrottledHandler::class, function ($app) {
            return new MessageThrottledHandler;
        });

        $this->app->bind(CanUserEnterSiteService::class, function ($app) {
            return new CanUserEnterSiteService;
        });

        $this->app->bind(ServerMonster::class, function () {
            return new ServerMonster;
        });

        $this->app->bind(BuildMonster::class, function ($app) {
            return new BuildMonster(
                $app->make(ServerMonster::class),
            );
        });

        $this->app->bind(Voidance::class, function () {
            return new Voidance;
        });

        $this->app->bind(Ambush::class, function ($app) {
            return new Ambush(
                $app->make(CharacterCacheData::class),
            );
        });

        $this->app->bind(Entrance::class, function ($app) {
            return new Entrance($app->make(CharacterCacheData::class));
        });

        $this->app->bind(ElementalAttack::class, function ($app) {
            return new ElementalAttack($app->make(CharacterCacheData::class));
        });

        $this->app->bind(MonsterSpecialAttack::class, function ($app) {
            return new MonsterSpecialAttack($app->make(CharacterCacheData::class));
        });

        $this->app->bind(CanHit::class, function ($app) {
            return new CanHit($app->make(CharacterCacheData::class));
        });

        $this->app->bind(Affixes::class, function ($app) {
            return new Affixes($app->make(CharacterCacheData::class));
        });

        $this->app->bind(SecondaryAttacks::class, function ($app) {
            return new SecondaryAttacks(
                $app->make(CharacterCacheData::class),
                $app->make(Affixes::class)
            );
        });

        $this->app->bind(WeaponType::class, function ($app) {
            return new WeaponType(
                $app->make(CharacterCacheData::class),
                $app->make(Entrance::class),
                $app->make(CanHit::class),
                $app->make(SpecialAttacks::class),
            );
        });

        $this->app->bind(CastType::class, function ($app) {
            return new CastType(
                $app->make(CharacterCacheData::class),
                $app->make(Entrance::class),
                $app->make(CanHit::class),
                $app->make(SpecialAttacks::class),
            );
        });

        $this->app->bind(AttackAndCast::class, function ($app) {
            return new AttackAndCast(
                $app->make(CharacterCacheData::class),
                $app->make(Entrance::class),
                $app->make(WeaponType::class),
                $app->make(CastType::class),
            );
        });

        $this->app->bind(CastAndAttack::class, function ($app) {
            return new CastAndAttack(
                $app->make(CharacterCacheData::class),
                $app->make(Entrance::class),
                $app->make(WeaponType::class),
                $app->make(CastType::class),
            );
        });

        $this->app->bind(Defend::class, function ($app) {
            return new Defend(
                $app->make(CharacterCacheData::class),
                $app->make(Entrance::class),
                $app->make(CanHit::class),
                $app->make(SecondaryAttacks::class),
                $app->make(SpecialAttacks::class),
            );
        });

        $this->app->bind(CharacterAttack::class, function ($app) {
            return new CharacterAttack(
                $app->make(WeaponType::class),
                $app->make(CastType::class),
                $app->make(AttackAndCast::class),
                $app->make(CastAndAttack::class),
                $app->make(Defend::class),
            );
        });

        $this->app->bind(BaseCharacterAttack::class, function ($app) {
            return new BaseCharacterAttack($app->make(CharacterAttack::class));
        });

        $this->app->bind(MonsterAttack::class, function ($app) {
            return new MonsterAttack(
                $app->make(CharacterCacheData::class),
                $app->make(PlayerHealing::class),
                $app->make(Entrance::class),
                $app->make(CanHit::class)
            );
        });

        $this->app->bind(Attack::class, function ($app) {
            return new Attack(
                $app->make(BaseCharacterAttack::class),
                $app->make(MonsterAttack::class)
            );
        });

        $this->app->bind(PlayerHealing::class, function ($app) {
            return new PlayerHealing(
                $app->make(CharacterCacheData::class),
                $app->make(Affixes::class),
                $app->make(CastType::class),
            );
        });

        $this->app->bind(SpecialAttacks::class, function () {
            return new SpecialAttacks;
        });

        $this->app->bind(HammerSmash::class, function ($app) {
            return new HammerSmash($app->make(CharacterCacheData::class));
        });

        $this->app->bind(AlchemistsRavenousDream::class, function ($app) {
            return new AlchemistsRavenousDream($app->make(CharacterCacheData::class));
        });

        $this->app->bind(TripleAttack::class, function ($app) {
            return new TripleAttack($app->make(CharacterCacheData::class));
        });

        $this->app->bind(DoubleAttack::class, function ($app) {
            return new DoubleAttack($app->make(CharacterCacheData::class));
        });

        $this->app->bind(DoubleCast::class, function ($app) {
            return new DoubleCast($app->make(CharacterCacheData::class));
        });

        $this->app->bind(DoubleHeal::class, function ($app) {
            return new DoubleHeal($app->make(CharacterCacheData::class));
        });

        $this->app->bind(VampireThirst::class, function ($app) {
            return new VampireThirst($app->make(CharacterCacheData::class));
        });

        $this->app->bind(ThiefBackStab::class, function ($app) {
            return new ThiefBackStab($app->make(CharacterCacheData::class));
        });

        $this->app->bind(BloodyPuke::class, function ($app) {
            return new BloodyPuke($app->make(CharacterCacheData::class));
        });

        $this->app->bind(MerchantSupply::class, function ($app) {
            return new MerchantSupply($app->make(CharacterCacheData::class));
        });

        $this->app->bind(PlagueSurge::class, function ($app) {
            return new PlagueSurge($app->make(CharacterCacheData::class));
        });

        $this->app->bind(GunslingersAssassination::class, function ($app) {
            return new GunslingersAssassination($app->make(CharacterCacheData::class));
        });

        $this->app->bind(SensualDance::class, function ($app) {
            return new SensualDance($app->make(CharacterCacheData::class));
        });

        $this->app->bind(BookBindersFear::class, function ($app) {
            return new BookBindersFear($app->make(CharacterCacheData::class));
        });

        $this->app->bind(DelveMonsterService::class, function () {
            return new DelveMonsterService();
        });

        $this->app->bind(BuildCosmicItem::class, function ($app) {
            return new BuildCosmicItem($app->make(RandomAffixGenerator::class));
        });

        $this->app->bind(BuildUniqueItem::class, function ($app) {
            return new BuildUniqueItem($app->make(RandomAffixGenerator::class));
        });

        $this->app->bind(MonsterPlayerFight::class, function ($app) {
            return new MonsterPlayerFight(
                $app->make(BuildMonster::class),
                $app->make(MonsterListService::class),
                $app->make(CharacterCacheData::class),
                $app->make(DelveMonsterService::class),
                $app->make(Voidance::class),
                $app->make(Ambush::class),
                $app->make(Attack::class),
            );
        });

        $this->app->bind(CharacterDeletion::class, function ($app) {
            return new CharacterDeletion(
                $app->make(GiveKingdomsToNpcHandler::class),
                $app->make(CharacterBuilderService::class),
            );
        });

        $this->app->bind(BuildMythicItem::class, function ($app) {
            return new BuildMythicItem($app->make(RandomAffixGenerator::class));
        });

        $this->app->bind(ClassRanksWeaponMasteriesBuilder::class, function () {
            return new ClassRanksWeaponMasteriesBuilder;
        });

        $this->app->bind(EventSchedulerService::class, function () {
            return new EventSchedulerService;
        });

        $this->app->bind(BuildQuestCacheService::class, function ($app) {
            return new BuildQuestCacheService(
                $app->make(QuestTransformer::class),
                $app->make(Manager::class),
            );
        });

        $this->app->bind(SiteAccessStatisticService::class, function () {
            return new SiteAccessStatisticService();
        });

        $this->app->bind(PlainDataSerializer::class, function () {
            return new PlainDataSerializer;
        });

        $this->app->singleton(CharacterCurrencyRewardService::class, function ($app) {
            return new CharacterCurrencyRewardService($app->make(BattleMessageHandler::class));
        });

        $this->app->bind(SkillBonusContextService::class, function () {
            return new SkillBonusContextService();
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
        $router->aliasMiddleware('session.time.tracking', TrackSessionLifeMiddleware::class);
        $router->aliasMiddleware('update.player-activity', UpdatePlayerSessionActivity::class);

        // Blade Components - Cross System:
        Blade::component('item-display-color', ItemDisplayColor::class);
    }
}
