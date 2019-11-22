<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */
use App\User;
use Faker\Generator as Faker;
use Illuminate\Support\Str;
use App\Flare\Models\GameClass;


$factory->define(GameClass::class, function (Faker $faker) {
    return [
        'name' => 'Sample Class',
    ];
});
