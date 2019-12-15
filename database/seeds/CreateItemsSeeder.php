<?php

use Illuminate\Database\Seeder;
use App\Flare\Models\Item;

class CreateItemsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Item::insert([
            [
                'name'        => 'Rusty bloody broken dagger',
                'type'        => 'weapon',
                'base_damage' => 3,
            ],
            [
                'name'        => 'Chapped, scared and ripped leather breast plate',
                'type'        => 'body',
                'base_damage' => null,
            ],
            [
                'name'        => 'Steel rimmed wooden shield',
                'type'        => 'shield',
                'base_damage' => null,
            ],
            [
                'name'        => 'Worn out musty old shoes',
                'type'        => 'feet',
                'base_damage' => null,
            ],
            [
                'name'        => 'Torn, ripped and bloody leggings',
                'type'        => 'leggings',
                'base_damage' => null,
            ],
            [
                'name'        => 'Old cotton sleeves',
                'type'        => 'sleeves',
                'base_damage' => null,
            ],
            [
                'name'        => 'Ruined and burnt wooden mask',
                'type'        => 'helmet',
                'base_damage' => null,
            ],
            [
                'name'        => 'Fingerless ripped gloves',
                'type'        => 'gloves',
                'base_damage' => null,
            ],
            [
                'name'        => 'Scroll of Dexterity',
                'type'        => 'artifact',
                'base_damage' => null,
            ],
            [
                'name'        => 'Quick cast rapid damage spell',
                'type'        => 'spell',
                'base_damage' => 5,
            ],
            [
                'name'        => 'Quick cast rapid healing spell',
                'type'        => 'spell',
                'base_damage' => null,
            ],
            [
                'name'        => 'Basic ring of hatred and despair',
                'type'        => 'ring',
                'base_damage' => 3,
            ],
            [
                'name'        => 'The Legendary and Lost Flask of Fresh Air',
                'type'        => 'quest',
                'base_damage' => null,
            ],
        ]);

        foreach(Item::all() as $item) {
            if ($item->name === 'Scroll of Dexterity') {
                $item->artifactProperty()->create(config('game.artifact_properties')[1]);
            }
        }
    }
}
