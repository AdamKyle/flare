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
                'name'        => 'Rusty Bloody Broken Dagger',
                'type'        => 'weapon',
                'base_damage' => 3,
            ],
            [
                'name'        => 'The Legendary and Lost Flask of Fresh Air',
                'type'        => 'quest',
                'base_damage' => null,
            ],
        ]);
    }
}
