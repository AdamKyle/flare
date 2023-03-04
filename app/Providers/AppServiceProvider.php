<?php

namespace App\Providers;

use App\Console\Commands\IncreaseRankFight;
use App\Console\DevelopmentCommands\AssignTopEndGearToPlayer;
use App\Console\DevelopmentCommands\IncreaseRankFightToMax;
use App\Console\DevelopmentCommands\ReincarnateCharacter;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use App\Console\DevelopmentCommands\CreateTestCharacters;
use App\Console\DevelopmentCommands\LevelCharacter;
use App\Console\DevelopmentCommands\MaxOutCharacter;
use App\Console\DevelopmentCommands\UpdateUsersForDevelopment;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void {

        // Register development based commands.
        $this->commands([
            CreateTestCharacters::class,
            MaxOutCharacter::class,
            UpdateUsersForDevelopment::class,
            LevelCharacter::class,
            AssignTopEndGearToPlayer::class,
            ReincarnateCharacter::class,
            IncreaseRankFightToMax::class,
        ]);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void {

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
