<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */
use Faker\Generator as Faker;
use Illuminate\Support\Str;
use App\Flare\Models\AdventureLog;

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

$factory->define(AdventureLog::class, function (Faker $faker) {
    return [
        'character_id'         => null,
        'adventure_id'         => null,
        'complete'             => null,
        'in_progress'          => null,
        'last_completed_level' => null,
        'logs'                 => null,
    ];
});
