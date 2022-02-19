<?php

Route::middleware([
    'auth',
    'is.player.banned',
    'is.character.who.they.say.they.are',
])->group(function() {
    Route::get('/exploration-automations/{character}', ['as' => 'exploration.automation.index', 'uses' => 'Api\ExplorationController@index']);

    Route::middleware(['is.character.dead', 'is.character.adventuring'])->group(function() {
        Route::post('/exploration/{character}/start', ['as' => 'exploration.start', 'uses' => 'Api\ExplorationController@begin']);
        Route::post('/exploration/{characterAutomation}/{character}/stop', ['as' => 'exploration.stop', 'uses' => 'Api\ExplorationController@stop']);
    });
});
