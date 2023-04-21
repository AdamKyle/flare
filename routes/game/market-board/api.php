<?php

Route::group(['middleware' => [
    'auth',
    'throttle:100,1',
    'is.character.who.they.say.they.are',
    'is.character.dead',
    'is.player.banned'
]], function() {
    Route::middleware(['is.character.exploring'])->group(function() {
        Route::get('/market-board/items', ['uses' => 'Api\MarketController@marketItems']);
        Route::post('/market-board/sell-item/{character}', ['uses' => 'Api\MarketController@sellItem']);
    });
});
