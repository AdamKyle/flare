<?php

namespace App\Game\Core\Items\Providers;

use App\Flare\Transformers\Serializer\PlainDataSerializer;
use App\Game\Core\Items\Comparison\Comparator;
use App\Game\Core\Items\Comparison\ItemComparison;
use App\Game\Core\Items\DataBuilders\QuestItem\QuestItemBuilder;
use App\Game\Core\Items\Enricher\EquippableEnricher;
use App\Game\Core\Items\Enricher\ItemEnricherFactory;
use App\Game\Core\Items\Enricher\Manifest\Concerns\ManifestSchema;
use App\Game\Core\Items\Enricher\Manifest\EquippableManifest;
use App\Game\Core\Items\Transformers\BaseEquippableItemTransformer;
use App\Game\Core\Items\Transformers\CraftingItemPreviewTransformer;
use App\Game\Core\Items\Transformers\EquippableItemTransformer;
use App\Game\Core\Items\Transformers\QuestItemTransformer;
use App\Game\Core\Items\Transformers\UsableItemTransformer;
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

    }
}
