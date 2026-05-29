<?php

Route::middleware([
    'auth',
    'is.player.banned',
    'is.character.who.they.say.they.are',
])->group(function () {

    Route::middleware(['is.character.dead'])->group(function () {
        Route::post('/automation/{character}/start', ['as' => 'automation.start', 'uses' => 'Api\ExplorationController@begin']);
        Route::post('/automation/{character}/stop', ['as' => 'automation.stop', 'uses' => 'Api\ExplorationController@stop']);

        Route::post('/delve/{character}/start', ['as' => 'delve.start', 'uses' => 'Api\DelveExplorationController@begin']);
        Route::post('/delve/{character}/stop', ['as' => 'delve.stop', 'uses' => 'Api\DelveExplorationController@stop']);

        Route::post('/faction-loyalty-automation/{character}/start', ['as' => 'faction-loyalty-automation.start', 'uses' => 'Api\FactionLoyaltyAutomationController@begin']);
        Route::post('/faction-loyalty-automation/{character}/stop', ['as' => 'faction-loyalty-automation.stop', 'uses' => 'Api\FactionLoyaltyAutomationController@stop']);
        Route::post('/faction-loyalty-automation/{character}/warning-notice/read', ['as' => 'faction-loyalty-automation.warning-notice.read', 'uses' => 'Api\FactionLoyaltyAutomationController@markWarningNoticeRead']);
    });
});
