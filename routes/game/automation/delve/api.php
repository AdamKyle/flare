<?php

Route::middleware([
    'auth',
    'is.player.banned',
    'is.character.who.they.say.they.are',
])->group(function () {
    Route::get('/delve/{character}/status', ['as' => 'delve.status', 'uses' => 'Api\DelveExplorationController@status']);
    Route::get('/delve/{character}/quest-item/{item}', ['as' => 'delve.quest-item', 'uses' => 'Api\DelveExplorationController@questItemDetail']);

    Route::middleware(['throttle:150,2'])->group(function () {
        Route::post('/delve/{character}/dismiss', ['as' => 'delve.dismiss', 'uses' => 'Api\DelveExplorationController@dismiss']);
    });

    Route::middleware(['is.character.dead'])->group(function () {
        Route::post('/delve/{character}/start', ['as' => 'delve.start', 'uses' => 'Api\DelveExplorationController@begin']);
        Route::post('/delve/{character}/stop', ['as' => 'delve.stop', 'uses' => 'Api\DelveExplorationController@stop']);
    });
});
