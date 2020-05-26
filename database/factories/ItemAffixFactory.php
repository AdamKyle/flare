<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */
use Faker\Generator as Faker;
use Illuminate\Support\Str;
use App\Flare\Models\ItemAffix;


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

$factory->define(ItemAffix::class, function (Faker $faker) {
    return [
        'name'                 => null,
        'base_damage_mod'      => null,
        'type'                 => null,
        'description'          => null,
        'base_healing_mod'     => null,
        'str_mod'              => null,
        'dur_mod'              => null,
        'dex_mod'              => null,
        'chr_mod'              => null,
        'int_mod'              => null,
        'ac_mod'               => null,
        'skill_name'           => null,
        'skill_training_bonus' => null,
    ];
});
