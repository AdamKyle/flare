<?php

namespace App\Game\Core\Chance\Providers;

use App\Game\Core\Chance\PhpRandomNumberGenerator;
use App\Game\Core\Chance\RandomNumberGenerator;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;

class ServiceProvider extends ApplicationServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RandomNumberGenerator::class, PhpRandomNumberGenerator::class);
    }
}
