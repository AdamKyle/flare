<?php

Route::middleware([
    'auth',
    'is.player.banned',
    'is.character.who.they.say.they.are',
])->group(function () {

    Route::middleware(['is.character.dead'])->group(function () {
        Route::post('/exploration/{character}/start', ['as' => 'exploration.start', 'uses' => 'Api\ExplorationController@begin']);
        Route::post('/exploration/{character}/stop', ['as' => 'exploration.stop', 'uses' => 'Api\ExplorationController@stop']);
    });
});
