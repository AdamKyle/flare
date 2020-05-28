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
        'character_id'          => null,
        'monster_id'            => null,
        'description'           => null,
        'name'                  => null,
        'currently_training'    => false,
        'level'                 => 1,
        'max_level'             => 100,
        'xp'                    => 0,
        'xp_max'                => rand(100, 1000),
        'base_damage_mod'       => 0.1,
        'base_healing_mod'      => 0.1,
        'base_ac_mod'           => 0.1,
        'fight_time_out_mod'    => 0.1,
        'move_time_out_mod'     => 0.1,
        'skill_bonus'           => 0.1,
        'skill_bonus_per_level' => 0.1,
        'xp_towards'            => 0.0,
    ];
});
