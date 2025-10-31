<?php

namespace App\Flare\Items\Providers;

use App\Flare\Items\Comparison\Comparator;
use App\Flare\Items\Comparison\ItemComparison;
use App\Flare\Items\DataBuilders\QuestItem\QuestItemBuilder;
use App\Flare\Items\Enricher\EquippableEnricher;
use App\Flare\Items\Enricher\ItemEnricherFactory;
use App\Flare\Items\Enricher\Manifest\Concerns\ManifestSchema;
use App\Flare\Items\Enricher\Manifest\EquippableManifest;
use App\Flare\Items\Transformers\BaseEquippableItemTransformer;
use App\Flare\Items\Transformers\EquippableItemTransformer;
use App\Flare\Items\Transformers\QuestItemTransformer;
use App\Flare\Items\Transformers\UsableItemTransformer;
use App\Flare\Transformers\Serializer\PlainDataSerializer;
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
