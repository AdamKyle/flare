<?php

namespace App\Game\Core\Items\Providers;

use App\Game\Core\Items\Enricher\ItemEnricherFactory;
use App\Game\Core\Items\Enricher\Manifest\Concerns\ManifestSchema;
use App\Game\Core\Items\Enricher\Manifest\EquippableManifest;
use App\Game\Core\Items\View\Components\ItemDisplayColor;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Bind the Core Items interface implementation and share one Item enricher factory.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(ManifestSchema::class, EquippableManifest::class);

        $this->app->singleton(ItemEnricherFactory::class);
    }

    /**
     * Register the Core Items Blade components.
     *
     * @return void
     */
    public function boot(): void
    {
        Blade::component('item-display-color', ItemDisplayColor::class);
    }
}
