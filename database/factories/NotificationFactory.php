<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */
use Faker\Generator as Faker;
use App\Flare\Models\Notification;


$factory->define(Notification::class, function (Faker $faker) {
    return [
        'character_id' => null,
        'title'        => null,
        'message'      => null,
        'status'       => null,
        'type'         => null,
        'read'         => null,
        'url'          => null,
    ];
});
