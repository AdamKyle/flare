<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */
use App\User;
use Faker\Generator as Faker;
use Illuminate\Support\Str;
use App\Flare\Models\Map;


$factory->define(Map::class, function (Faker $faker) {
    return [
        'character_id' => 1,
        'position_x' => 0,
        'position_y' => 0,
        'character_position_x' => 32,
        'character_position_y' => 32,
    ];
});
