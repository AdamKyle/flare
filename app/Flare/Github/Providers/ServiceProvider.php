<?php

namespace App\Flare\Github\Providers;

use App\Flare\Github\Commands\GetReleaseData;
use App\Flare\Github\Components\ReleaseNote;
use App\Flare\Github\Services\Github;
use App\Flare\Github\Services\Markdown;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->commands([
            GetReleaseData::class,
        ]);

        $this->app->bind(Github::class, function () {
            return new Github;
        });

        $this->app->bind(Markdown::class, function () {
            return new Markdown;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        // Release Notes Blade Component
        Blade::component('release-note', ReleaseNote::class);

        // new @convertMarkdownToHtml directive
        Blade::directive('convertMarkdownToHtml', function (string $expression): string {
            return "<?php echo app('".Markdown::class."')->convertToHtml(app('".Markdown::class."')->cleanMarkdown({$expression})); ?>";
        });
    }
}
