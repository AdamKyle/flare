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
        require base_path('routes/flare/channels.php');
        require base_path('routes/admin/channels.php');
        require base_path('routes/game/channels.php');
        require base_path('routes/game/character/character-inventory/channels.php');
        require base_path('routes/game/character/character-attack/channels.php');
        require base_path('routes/game/exploration/channels.php');
        require base_path('routes/game/messages/channels.php');
        require base_path('routes/game/battle/channels.php');
        require base_path('routes/game/kingdoms/channels.php');
        require base_path('routes/game/maps/channels.php');
        require base_path('routes/game/skills/channels.php');
        require base_path('routes/game/passive-skills/channels.php');
        require base_path('routes/game/quests/channels.php');
        require base_path('routes/game/guide-quests/channels.php');
        require base_path('routes/game/gambler/channels.php');
        require base_path('routes/game/npc-actions/labyrinth-oracle/channels.php');
        require base_path('routes/game/events/channels.php');
        require base_path('routes/game/shop/channels.php');
        require base_path('routes/game/factions/faction-loyalty/channels.php');
        require base_path('routes/game/survey/channels.php');
    }
}
