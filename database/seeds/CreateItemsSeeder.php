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
                'name' => 'Rusty Dagger',
            ],
        ]);
    }
}
