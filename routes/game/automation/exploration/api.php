<?php

Route::middleware([
    'auth',
    'is.player.banned',
    'is.character.who.they.say.they.are',
])->group(function () {
    Route::get('/exploration/{character}/output', ['as' => 'exploration.output', 'uses' => 'Api\ExplorationOutputController@output']);

    Route::middleware(['throttle:150,2'])->group(function () {
        Route::post('/exploration/{character}/warning/dismiss', ['as' => 'exploration.warning.dismiss', 'uses' => 'Api\ExplorationWarningController@dismiss']);
        Route::post('/exploration/{character}/dismiss', ['as' => 'exploration.dismiss', 'uses' => 'Api\ExplorationWarningController@dismissEnded']);
    });

    Route::middleware(['is.character.dead'])->group(function () {
        Route::post('/automation/{character}/start', ['as' => 'automation.start', 'uses' => 'Api\ExplorationController@begin']);
        Route::post('/automation/{character}/stop', ['as' => 'automation.stop', 'uses' => 'Api\ExplorationController@stop']);
    });
});
