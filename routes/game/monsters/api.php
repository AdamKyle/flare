<?php

Route::middleware(['auth', 'is.character.who.they.say.they.are'])->group(function () {
    Route::get('/monster-list/{character}', ['uses' => 'Api\MonstersController@listMonsters']);
    Route::get('/monster-stat/{monster}/{character}', ['uses' => 'Api\MonstersController@getMonsterStats']);
});
