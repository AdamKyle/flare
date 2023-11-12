<?php

namespace App\Console\Commands;

use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Values\MapNameValue;
use Illuminate\Console\Command;

class TestQuery extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-query';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle() {

        $gameMap = GameMap::where('name', MapNameValue::ICE_PLANE)->first();

        $result = Character::join('maps', 'maps.character_id', '=', 'characters.id')
            ->where('maps.game_map_id', $gameMap->id)->get();
    }
}
