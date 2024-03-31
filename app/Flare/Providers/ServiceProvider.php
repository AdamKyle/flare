<?php

namespace App\Flare\Providers;

use App\Flare\Builders\AffixAttributeBuilder;
use App\Flare\Builders\BuildMythicItem;
use App\Flare\Builders\Character\AttackDetails\CharacterAttackBuilder;
use App\Flare\Builders\Character\CharacterCacheData;
use App\Flare\Builders\CharacterBuilder;
use App\Flare\Builders\CharacterInformation\AttributeBuilders\ClassRanksWeaponMasteriesBuilder;
use App\Flare\Builders\CharacterInformation\AttributeBuilders\DamageBuilder;
use App\Flare\Builders\CharacterInformation\AttributeBuilders\DefenceBuilder;
use App\Flare\Builders\CharacterInformation\AttributeBuilders\ElementalAtonement;
use App\Flare\Builders\CharacterInformation\AttributeBuilders\HealingBuilder;
use App\Flare\Builders\CharacterInformation\AttributeBuilders\HolyBuilder;
use App\Flare\Builders\CharacterInformation\AttributeBuilders\ReductionsBuilder;
use App\Flare\Builders\CharacterInformation\CharacterStatBuilder;
use App\Flare\Builders\RandomAffixGenerator;
use App\Flare\Builders\RandomItemDropBuilder;
use App\Flare\Cache\CoordinatesCache;
use App\Flare\Handlers\MessageThrottledHandler;
use App\Flare\Handlers\UpdateCharacterAttackTypes;
use App\Flare\Middleware\IsCharacterDeadMiddleware;
use App\Flare\Middleware\IsCharacterLoggedInMiddleware;
use App\Flare\Middleware\IsCharacterWhoTheySayTheyAreMiddleware;
use App\Flare\Middleware\IsGloballyTimedOut;
use App\Flare\Middleware\IsPlayerBannedMiddleware;
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
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\DoubleAttack;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\DoubleCast;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\DoubleHeal;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\HammerSmash;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\MerchantSupply;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\ThiefBackStab;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\TripleAttack;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\VampireThirst;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\GunslingersAssassination;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\SensualDance;
use App\Flare\ServerFight\Fight\CharacterAttacks\SpecialAttacks\BookBindersFear;
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
use App\Flare\ServerFight\Pvp\PvpAttack;
use App\Flare\ServerFight\Pvp\SetUpFight;
use App\Flare\Services\BuildCharacterAttackTypes;
use App\Flare\Services\BuildMonsterCacheService;
use App\Flare\Services\CanUserEnterSiteService;
use App\Flare\Services\CharacterDeletion;
use App\Flare\Services\CharacterRewardService;
use App\Flare\Services\CharacterXPService;
use App\Flare\Services\DailyGoldDustService;
use App\Flare\Services\EventSchedulerService;
use App\Flare\Transformers\BasicKingdomTransformer;
use App\Flare\Transformers\CharacterAttackDataTransformer;
use App\Flare\Transformers\CharacterAttackTransformer;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Flare\Transformers\InventoryTransformer;
use App\Flare\Transformers\ItemTransformer;
use App\Flare\Transformers\KingdomAttackLogsTransformer;
use App\Flare\Transformers\KingdomBuildingTransformer;
use App\Flare\Transformers\KingdomTransformer;
use App\Flare\Transformers\MarketItemsTransformer;
use App\Flare\Transformers\MonsterTransformer;
use App\Flare\Transformers\OtherKingdomTransformer;
use App\Flare\Transformers\UnitTransformer;
use App\Flare\Transformers\UsableItemTransformer;
use App\Flare\Values\BaseSkillValue;
use App\Flare\Values\BaseStatValue;
use App\Flare\View\Components\ItemDisplayColor;
use App\Game\Core\Services\CharacterService;
use App\Game\Gems\Services\GemComparison;
use App\Game\Kingdoms\Handlers\GiveKingdomsToNpcHandler;
use App\Game\Quests\Services\BuildQuestCacheService;
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
        $this->app->bind(BaseStatValue::class, function () {
            return new BaseStatValue();
        });

        $this->app->bind(CharacterBuilder::class, function ($app) {
            return new CharacterBuilder(
                $app->make(BuildCharacterAttackTypes::class)
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

        $this->app->bind(CharacterAttackBuilder::class, function($app) {
            return new CharacterAttackBuilder(
                $app->make(CharacterStatBuilder::class)
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

        $this->app->bind(OtherKingdomTransformer::class, function($app){
            return new OtherKingdomTransformer();
        });

        $this->app->bind(BasicKingdomTransformer::class, function($app){
            return new BasicKingdomTransformer();
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

        $this->app->bind(UsableItemTransformer::class, function($app) {
            return new UsableItemTransformer();
        });

        $this->app->bind(InventoryTransformer::class, function() {
            return new InventoryTransformer();
        });

        $this->app->bind(KingdomAttackLogsTransformer::class, function() {
            return new KingdomAttackLogsTransformer();
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
                $app->make(SkillService::class),
                $app->make(Manager::class),
                $app->make(CharacterSheetBaseInfoTransformer::class)
            );
        });

        $this->app->bind(MessageThrottledHandler::class, function($app) {
            return new MessageThrottledHandler;
        });

        $this->app->bind(BuildMonsterCacheService::class, function($app) {
            return new BuildMonsterCacheService(
                $app->make(Manager::class),
                $app->make(MonsterTransformer::class),
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

        $this->app->bind(ServerMonster::class, function() {
            return new ServerMonster();
        });

        $this->app->bind(BuildMonster::class, function($app) {
            return new BuildMonster(
                $app->make(ServerMonster::class),
            );
        });

        $this->app->bind(Voidance::class, function() {
            return new Voidance();
        });

        $this->app->bind(CharacterCacheData::class, function($app) {
            return new CharacterCacheData(
                $app->make(Manager::class),
                $app->make(CharacterAttackDataTransformer::class),
                $app->make(CharacterStatBuilder::class)
            );
        });

        $this->app->bind(Ambush::class, function($app) {
            return new Ambush(
                $app->make(CharacterCacheData::class),
            );
        });

        $this->app->bind(Entrance::class, function($app) {
            return new Entrance($app->make(CharacterCacheData::class));
        });

        $this->app->bind(ElementalAttack::class, function($app) {
            return new ElementalAttack($app->make(CharacterCacheData::class));
        });

        $this->app->bind(MonsterSpecialAttack::class, function($app) {
            return new MonsterSpecialAttack($app->make(CharacterCacheData::class));
        });

        $this->app->bind(CanHit::class, function($app) {
            return new CanHit($app->make(CharacterCacheData::class));
        });

        $this->app->bind(Affixes::class, function($app) {
            return new Affixes($app->make(CharacterCacheData::class));
        });

        $this->app->bind(SecondaryAttacks::class, function($app) {
            return new SecondaryAttacks(
                $app->make(CharacterCacheData::class),
                $app->make(Affixes::class)
            );
        });

        $this->app->bind(WeaponType::class, function($app) {
            return new WeaponType(
                $app->make(CharacterCacheData::class),
                $app->make(Entrance::class),
                $app->make(CanHit::class),
                $app->make(SpecialAttacks::class),
            );
        });

        $this->app->bind(CastType::class, function($app) {
            return new CastType(
                $app->make(CharacterCacheData::class),
                $app->make(Entrance::class),
                $app->make(CanHit::class),
                $app->make(SpecialAttacks::class),
            );
        });

        $this->app->bind(AttackAndCast::class, function($app) {
            return new AttackAndCast(
                $app->make(CharacterCacheData::class),
                $app->make(Entrance::class),
                $app->make(CanHit::class),
                $app->make(SecondaryAttacks::class),
                $app->make(WeaponType::class),
                $app->make(CastType::class),
            );
        });

        $this->app->bind(CastAndAttack::class, function($app) {
            return new CastAndAttack(
                $app->make(CharacterCacheData::class),
                $app->make(Entrance::class),
                $app->make(CanHit::class),
                $app->make(SecondaryAttacks::class),
                $app->make(WeaponType::class),
                $app->make(CastType::class),
            );
        });

        $this->app->bind(Defend::class, function($app) {
            return new Defend(
                $app->make(CharacterCacheData::class),
                $app->make(Entrance::class),
                $app->make(CanHit::class),
                $app->make(SecondaryAttacks::class),
                $app->make(SpecialAttacks::class),
            );
        });

        $this->app->bind(CharacterAttack::class, function($app) {
            return new CharacterAttack(
                $app->make(WeaponType::class),
                $app->make(CastType::class),
                $app->make(AttackAndCast::class),
                $app->make(CastAndAttack::class),
                $app->make(Defend::class),
            );
        });

        $this->app->bind(BaseCharacterAttack::class, function($app) {
            return new BaseCharacterAttack($app->make(CharacterAttack::class));
        });

        $this->app->bind(MonsterAttack::class, function($app) {
            return new MonsterAttack(
                $app->make(CharacterCacheData::class),
                $app->make(PlayerHealing::class),
                $app->make(Entrance::class),
                $app->make(CanHit::class)
            );
        });

        $this->app->bind(Attack::class, function($app) {
            return new Attack(
                $app->make(BaseCharacterAttack::class),
                $app->make(MonsterAttack::class)
            );
        });

        $this->app->bind(PlayerHealing::class, function($app) {
           return new PlayerHealing(
               $app->make(CharacterCacheData::class),
               $app->make(Affixes::class)
           );
        });

        $this->app->bind(SpecialAttacks::class, function() {
            return new SpecialAttacks();
        });

        $this->app->bind(HammerSmash::class, function($app) {
            return new HammerSmash($app->make(CharacterCacheData::class));
        });

        $this->app->bind(AlchemistsRavenousDream::class, function($app) {
            return new AlchemistsRavenousDream($app->make(CharacterCacheData::class));
        });

        $this->app->bind(TripleAttack::class, function($app) {
            return new TripleAttack($app->make(CharacterCacheData::class));
        });

        $this->app->bind(DoubleAttack::class, function($app) {
            return new DoubleAttack($app->make(CharacterCacheData::class));
        });

        $this->app->bind(DoubleCast::class, function($app) {
            return new DoubleCast($app->make(CharacterCacheData::class));
        });

        $this->app->bind(DoubleHeal::class, function($app) {
            return new DoubleHeal($app->make(CharacterCacheData::class));
        });

        $this->app->bind(VampireThirst::class, function($app) {
            return new VampireThirst($app->make(CharacterCacheData::class));
        });

        $this->app->bind(ThiefBackStab::class, function($app) {
            return new ThiefBackStab($app->make(CharacterCacheData::class));
        });

        $this->app->bind(BloodyPuke::class, function($app) {
            return new BloodyPuke($app->make(CharacterCacheData::class));
        });

        $this->app->bind(MerchantSupply::class, function($app) {
            return new MerchantSupply($app->make(CharacterCacheData::class));
        });

        $this->app->bind(GunslingersAssassination::class, function($app) {
            return new GunslingersAssassination($app->make(CharacterCacheData::class));
        });

        $this->app->bind(SensualDance::class, function($app) {
            return new SensualDance($app->make(CharacterCacheData::class));
        });

        $this->app->bind(BookBindersFear::class, function($app) {
            return new BookBindersFear($app->make(CharacterCacheData::class));
        });

        $this->app->bind(MonsterPlayerFight::class, function($app) {
            return new MonsterPlayerFight(
                $app->make(BuildMonster::class),
                $app->make(CharacterCacheData::class),
                $app->make(Voidance::class),
                $app->make(Ambush::class),
                $app->make(Attack::class),
            );
        });

        $this->app->bind(CharacterDeletion::class, function($app) {
            return new CharacterDeletion(
                $app->make(GiveKingdomsToNpcHandler::class),
                $app->make(CharacterBuilder::class),
            );
        });

        $this->app->bind(SetUpFight::class, function($app) {
            return new SetUpFight(
                $app->make(CharacterCacheData::class),
                $app->make(Voidance::class),
                $app->make(Ambush::class)
            );
        });

        $this->app->bind(PvpAttack::class, function($app) {
            return new PvpAttack(
                $app->make(CharacterCacheData::class),
                $app->make(SetUpFight::class),
                $app->make(BaseCharacterAttack::class),
            );
        });

        $this->app->bind(BuildMythicItem::class, function($app) {
            return new BuildMythicItem($app->make(RandomAffixGenerator::class));
        });

        $this->app->bind(UpdateCharacterAttackTypes::class, function($app) {
            return new UpdateCharacterAttackTypes($app->make(BuildCharacterAttackTypes::class));
        });

        $this->app->bind(DefenceBuilder::class, function() {
            return new DefenceBuilder();
        });

        $this->app->bind(ClassRanksWeaponMasteriesBuilder::class, function() {
            return new ClassRanksWeaponMasteriesBuilder();
        });

        $this->app->bind(DamageBuilder::class, function($app) {
            return new DamageBuilder($app->make(ClassRanksWeaponMasteriesBuilder::class));
        });

        $this->app->bind(HealingBuilder::class, function($app) {
            return new HealingBuilder($app->make(ClassRanksWeaponMasteriesBuilder::class));
        });

        $this->app->bind(HolyBuilder::class, function() {
            return new HolyBuilder();
        });

        $this->app->bind(ElementalAtonement::class, function($app) {
            return new ElementalAtonement($app->make(GemComparison::class));
        });

        $this->app->bind(ReductionsBuilder::class, function() {
            return new ReductionsBuilder();
        });

        $this->app->bind(CharacterStatBuilder::class, function($app) {
            return new CharacterStatBuilder(
                $app->make(DefenceBuilder::class),
                $app->make(DamageBuilder::class),
                $app->make(HealingBuilder::class),
                $app->make(HolyBuilder::class),
                $app->make(ReductionsBuilder::class),
                $app->make(ElementalAtonement::class)
            );
        });

        $this->app->bind(EventSchedulerService::class, function() {
           return new EventSchedulerService();
        });

        $this->app->bind(BuildQuestCacheService::class, function() {
            return new BuildQuestCacheService();
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
    }
}
