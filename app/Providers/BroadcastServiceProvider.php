<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Broadcast::routes();

        require base_path('routes/channels.php');
        require base_path('routes/admin/channels.php');
        require base_path('routes/game/channels.php');
        require base_path('routes/game/automation/channels.php');
        require base_path('routes/game/messages/channels.php');
        require base_path('routes/game/battle/channels.php');
        require base_path('routes/game/kingdoms/channels.php');
        require base_path('routes/game/adventures/channels.php');
        require base_path('routes/game/maps/channels.php');
        require base_path('routes/game/skills/channels.php');
        require base_path('routes/flare/channels.php');
    }
}
