<?php

Route::middleware([
    'auth',
    'is.player.banned',
    'is.character.who.they.say.they.are',
])->group(function () {

    Route::middleware(['is.character.dead'])->group(function () {
        Route::post('/exploration/{character}/start', ['as' => 'exploration.start', 'uses' => 'Api\ExplorationController@begin']);
        Route::post('/exploration/{character}/stop', ['as' => 'exploration.stop', 'uses' => 'Api\ExplorationController@stop']);

        Route::post('/dwelve/{character}/start', ['as' => 'dwelve.start', 'uses' => 'Api\DelveExplorationController@begin']);
        Route::post('/dwelve/{character}/stop', ['as' => 'dwelve.stop', 'uses' => 'Api\DelveExplorationController@stop']);
    });
});
