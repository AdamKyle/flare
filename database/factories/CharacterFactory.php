<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */
use App\User;
use Faker\Generator as Faker;
use Illuminate\Support\Str;
use App\Flare\Models\Character;


$factory->define(Character::class, function (Faker $faker) {
    return [
        'user_id' => 1,
        'name' => 'fake',
        'damage_stat' => 'dex',
        'game_race_id' => 1,
        'game_class_id' => 1,
        'xp' => 1,
        'xp_next' => 100,
        'str' => 1,
        'dur' => 1,
        'dex' => 1,
        'chr' => 1,
        'int' => 1,
        'ac' => 1,
    ];
});
