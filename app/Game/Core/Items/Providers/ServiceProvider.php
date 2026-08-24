<?php

namespace App\Game\Core\Items\Providers;

use App\Flare\Transformers\Serializer\PlainDataSerializer;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Core\Items\Builders\AffixAttributeBuilder;
use App\Game\Core\Items\Builders\BuildCosmicItem;
use App\Game\Core\Items\Builders\BuildMythicItem;
use App\Game\Core\Items\Builders\BuildUniqueItem;
use App\Game\Core\Items\Builders\RandomAffixGenerator;
use App\Game\Core\Items\Builders\RandomItemDropBuilder;
use App\Game\Core\Items\Comparison\Comparator;
use App\Game\Core\Items\Comparison\ItemComparison;
use App\Game\Core\Items\DataBuilders\QuestItem\QuestItemBuilder;
use App\Game\Core\Items\Enricher\EquippableEnricher;
use App\Game\Core\Items\Enricher\ItemEnricherFactory;
use App\Game\Core\Items\Enricher\Manifest\Concerns\ManifestSchema;
use App\Game\Core\Items\Enricher\Manifest\EquippableManifest;
use App\Game\Core\Items\Transformers\Api\UsableItemTransformer as ApiUsableItemTransformer;
use App\Game\Core\Items\Transformers\BaseEquippableItemTransformer;
use App\Game\Core\Items\Transformers\CraftingItemPreviewTransformer;
use App\Game\Core\Items\Transformers\EquippableItemTransformer;
use App\Game\Core\Items\Transformers\ItemTransformer;
use App\Game\Core\Items\Transformers\QuestItemTransformer;
use App\Game\Core\Items\Transformers\UsableItemTransformer;
use App\Game\Core\Items\View\Components\ItemDisplayColor;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use League\Fractal\Manager;

/**
 * @codeCoverageIgnore
 */
class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(EquippableEnricher::class, function () {
            return new EquippableEnricher;
        });

        $this->app->bind(EquippableItemTransformer::class, function () {
            return new EquippableItemTransformer;
        });

        $this->app->bind(BaseEquippableItemTransformer::class, function () {
            return new BaseEquippableItemTransformer;
        });

        $this->app->bind(QuestItemTransformer::class, function () {
            return new QuestItemTransformer;
        });

        $this->app->bind(UsableItemTransformer::class, function () {
            return new UsableItemTransformer;
        });

        $this->app->bind(ManifestSchema::class, EquippableManifest::class);

        $this->app->bind(CraftingItemPreviewTransformer::class, function ($app) {
            return new CraftingItemPreviewTransformer(
                $app->make(ItemEnricherFactory::class),
            );
        });

        $this->app->singleton(ItemEnricherFactory::class, function ($app) {
            return new ItemEnricherFactory(
                $app->make(EquippableEnricher::class),
                $app->make(EquippableItemTransformer::class),
                $app->make(UsableItemTransformer::class),
                $app->make(QuestItemTransformer::class),
                $app->make(PlainDataSerializer::class),
                $app->make(Manager::class),
            );
        });

        $this->app->bind(ItemComparison::class, function ($app) {
            return new ItemComparison(
                $app->make(EquippableEnricher::class),
                $app->make(Comparator::class),
                $app->make(BaseEquippableItemTransformer::class),
                $app->make(PlainDataSerializer::class),
                $app->make(Manager::class),
            );
        });

        $this->app->bind(QuestItemBuilder::class, function ($app) {
            return new QuestItemBuilder(
                $app->make(Manager::class),
                $app->make(QuestItemTransformer::class),
            );
        });

        $this->app->bind(AffixAttributeBuilder::class, function ($app) {
            return new AffixAttributeBuilder(
                $app->make(RandomNumberGenerator::class),
                $app->make(ChanceCalculator::class),
            );
        });

        $this->app->bind(RandomAffixGenerator::class, function ($app) {
            return new RandomAffixGenerator(
                $app->make(AffixAttributeBuilder::class)
            );
        });

        $this->app->bind(RandomItemDropBuilder::class, function ($app) {
            return new RandomItemDropBuilder(
                $app->make(RandomNumberGenerator::class),
                $app->make(ChanceCalculator::class),
            );
        });

        $this->app->bind(ItemTransformer::class, function ($app) {
            return new ItemTransformer($app->make(ItemEnricherFactory::class));
        });

        $this->app->bind(ApiUsableItemTransformer::class, function ($app) {
            return new ApiUsableItemTransformer;
        });

        $this->app->bind(BuildCosmicItem::class, function ($app) {
            return new BuildCosmicItem($app->make(RandomAffixGenerator::class));
        });

        $this->app->bind(BuildUniqueItem::class, function ($app) {
            return new BuildUniqueItem($app->make(RandomAffixGenerator::class));
        });

        $this->app->bind(BuildMythicItem::class, function ($app) {
            return new BuildMythicItem($app->make(RandomAffixGenerator::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::component('item-display-color', ItemDisplayColor::class);
    }
}
