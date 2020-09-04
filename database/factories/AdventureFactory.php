<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */
use Faker\Generator as Faker;
use Illuminate\Support\Str;
use App\Flare\Models\Adventure;


/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(Adventure::class, function (Faker $faker) {
    return [
        'name'             => null,
        'description'      => null,
        'reward_item_id'   => null,
        'levels'           => null,
        'time_per_level'   => null,
        'gold_rush_chance' => null,
        'item_find_chance' => null,
        'skill_exp_bonus'  => null,
    ];
});
