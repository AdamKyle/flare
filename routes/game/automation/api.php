<?php

Route::middleware([
    'auth',
    'is.player.banned',
    'is.character.who.they.say.they.are',
])->group(function () {
    Route::get('/exploration/{character}/output', ['as' => 'exploration.output', 'uses' => 'Api\ExplorationOutputController@output']);

    Route::middleware(['throttle:150,2'])->group(function () {
        Route::post('/faction-loyalty-automation/{character}/warning/dismiss', ['as' => 'faction-loyalty-automation.warning.dismiss', 'uses' => 'Api\FactionLoyaltyAutomationWarningController@dismiss']);
        Route::post('/exploration/{character}/warning/dismiss', ['as' => 'exploration.warning.dismiss', 'uses' => 'Api\ExplorationWarningController@dismiss']);
    });

    Route::middleware(['is.character.dead'])->group(function () {
        Route::post('/automation/{character}/start', ['as' => 'automation.start', 'uses' => 'Api\ExplorationController@begin']);
        Route::post('/automation/{character}/stop', ['as' => 'automation.stop', 'uses' => 'Api\ExplorationController@stop']);

        Route::post('/delve/{character}/start', ['as' => 'delve.start', 'uses' => 'Api\DelveExplorationController@begin']);
        Route::post('/delve/{character}/stop', ['as' => 'delve.stop', 'uses' => 'Api\DelveExplorationController@stop']);

        Route::post('/faction-loyalty-automation/{character}/start', ['as' => 'faction-loyalty-automation.start', 'uses' => 'Api\FactionLoyaltyAutomationController@begin']);
        Route::post('/faction-loyalty-automation/{character}/stop', ['as' => 'faction-loyalty-automation.stop', 'uses' => 'Api\FactionLoyaltyAutomationController@stop']);
    });
});
