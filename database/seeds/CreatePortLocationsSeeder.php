<?php

namespace Database\Seeders;

use App\Flare\Models\GameMap;
use Illuminate\Database\Seeder;
use App\Flare\Models\Location;
use Illuminate\Support\Facades\Storage;

class CreatePortLocationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $path = Storage::disk('maps')->putFile('Surface', resource_path('maps/surface.jpg'));

        $gameMap = GameMap::create([
            'name'          => 'Surface',
            'path'          => $path,
            'default'       => true,
            'kingdom_color' => '#879bc2',
        ]);

        // Ports:
        $ports = [
            [
                'name'        => 'Smugglers Port',
                'description' => 'A place where the stolen goods are smuggled in.',
                'is_port'     => true,
                'game_map_id' => $gameMap->id,
                'x'           => 160,
                'y'           => 80,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'name'        => 'Port of Kalith',
                'description' => 'Welcome to Kalith port! Adventure awaits!',
                'is_port'     => true,
                'game_map_id' => $gameMap->id,
                'x'           => 304,
                'y'           => 496,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'name'        => 'Dalix',
                'description' => 'Dalix offers all your needs! Come to Dalix!',
                'is_port'     => true,
                'game_map_id' => $gameMap->id,
                'x'           => 320,
                'y'           => 288,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'name'        => 'Port of Salix',
                'description' => 'The cousin of Dalix. A place where rules do not exist.',
                'is_port'     => true,
                'game_map_id' => $gameMap->id,
                'x'           => 432,
                'y'           => 208,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'name'        => 'Karth',
                'description' => 'The port of Karth is a majour trading port.',
                'is_port'     => true,
                'game_map_id' => $gameMap->id,
                'x'           => 32,
                'y'           => 256,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ];

        Location::insert($ports);
    }
}
