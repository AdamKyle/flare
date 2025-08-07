<?php

namespace App\Flare\Items\Providers;

use App\Flare\Github\Commands\GetReleaseData;
use App\Flare\Github\Components\ReleaseNote;
use App\Flare\Github\Services\Github;
use App\Flare\Github\Services\Markdown;
use App\Flare\Items\Enricher\EquippableEnricher;
use App\Flare\Items\Enricher\ItemEnricherFactory;
use App\Flare\Items\Transformers\EquippableItemTransformer;
use App\Flare\Items\Transformers\QuestItemTransformer;
use App\Flare\Items\Transformers\UsableItemTransformer;
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

        $this->app->bind(QuestItemTransformer::class, function () {
            return new QuestItemTransformer;
        });

        $this->app->bind(UsableItemTransformer::class, function () {
            return new UsableItemTransformer;
        });

        $this->app->singleton(ItemEnricherFactory::class, function ($app) {
            return new ItemEnricherFactory(
                $app->make(EquippableEnricher::class),
                $app->make(EquippableItemTransformer::class),
                $app->make(UsableItemTransformer::class),
                $app->make(QuestItemTransformer::class),
                $app->make(Manager::class),
            );
        });
    }
}
