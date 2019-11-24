<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */
use Faker\Generator as Faker;
use Illuminate\Support\Str;
use App\Flare\Models\Skill;


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

$factory->define(Skill::class, function (Faker $faker) {
    return [
        'character_id' => null,
        'monster_id' => null,
        'name' => 'something',
        'currently_training' => false,
        'level' => 1,
        'xp' => 0,
        'xp_max' => 100,
        'skill_bonus' => 0,
        'skill_bonus_per_level' => 1,
    ];
});
