<?php

namespace App\Providers;

use Mail;
use Illuminate\Support\ServiceProvider;
use App\Charts\CreateHistoryForItem;
use App\Charts\MarketBoardHistory;
use ConsoleTVs\Charts\Registrar as Charts;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(Charts $charts)
    {

        $charts->register([
            MarketBoardHistory::class,
            CreateHistoryForItem::class,
        ]);

        if ($this->app->environment('local')) {
            Mail::alwaysTo(env('DEFAULT_LOCAL_EMAIL'));
        }

        \Response::macro('attachment', function ($content, $fileName) {

            $headers = [
                'Content-type' => 'text/json',
                'Content-Disposition' => "attachment; filename=".$fileName.".json",
            ];

            return \Response::make($content, 200, $headers);
        });
    }
}
