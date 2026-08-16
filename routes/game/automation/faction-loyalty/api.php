<?php

Route::middleware([
    'auth',
    'is.player.banned',
    'is.character.who.they.say.they.are',
])->group(function () {
    Route::middleware(['throttle:150,2'])->group(function () {
        Route::post('/faction-loyalty-automation/{character}/warning/dismiss', ['as' => 'faction-loyalty-automation.warning.dismiss', 'uses' => 'Api\FactionLoyaltyAutomationWarningController@dismiss']);
    });

    Route::middleware(['is.character.dead'])->group(function () {
        Route::post('/faction-loyalty-automation/{character}/start', ['as' => 'faction-loyalty-automation.start', 'uses' => 'Api\FactionLoyaltyAutomationController@begin']);
        Route::post('/faction-loyalty-automation/{character}/stop', ['as' => 'faction-loyalty-automation.stop', 'uses' => 'Api\FactionLoyaltyAutomationController@stop']);
    });
});
