<?php

Route::group(['middleware' => [
    'auth',
    'throttle:100,1',
    'is.character.who.they.say.they.are',
    'is.player.banned',
]], function() {
    Route::get('/mercenaries/list/{character}', ['uses' => 'Api\MercenaryController@list']);

    Route::middleware(['is.character.exploring', 'is.character.dead'])->group(function() {
        Route::post('/mercenaries/buy/{character}', ['uses' => 'Api\MercenaryController@buy']);
        Route::post('/mercenaries/re-incarnate/{character}/{characterMercenary}', ['uses' => 'Api\MercenaryController@reincarnate']);
        Route::post('/mercenaries/purcahse-buff/{character}/{characterMercenary}', ['uses' => 'Api\MercenaryController@purchaseBuff']);
    });
});
