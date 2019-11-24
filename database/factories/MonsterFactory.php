<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */
use App\User;
use Faker\Generator as Faker;
use Illuminate\Support\Str;
use App\Flare\Models\Monster;


$factory->define(Monster::class, function (Faker $faker) {
    return [
        'name'         => 'Goblin',
        'damage_stat'  => 'str',
        'xp'           => 10,
        'str'          => 1,
        'dur'          => 6,
        'dex'          => 7,
        'chr'          => 8,
        'int'          => 8,
        'ac'           => 6,
        'health_range' => '1-8',
        'attack_range' => '1-6',
    ];
});
