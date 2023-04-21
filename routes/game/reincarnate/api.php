<?php

Route::group(['middleware' => [
    'auth',
    'throttle:100,1',
    'is.character.who.they.say.they.are',
    'is.player.banned',
    'is.character.exploring',
    'is.character.dead',
]], function() {
    Route::post('/character/reincarnate/{character}', ['uses' => 'Api\ReincarnateController@reincarnate']);
});
