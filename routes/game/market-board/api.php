<?php

Route::group(['middleware' => [
    'auth',
    'throttle:100,1',
    'is.character.who.they.say.they.are',
    'is.character.dead',
    'is.character.adventuring',
    'is.player.banned'
]], function() {

    Route::middleware(['is.character.exploring'])->group(function() {
        Route::middleware(['can.access.market'])->group(function() {
            Route::get('/market-board/character-items/{character}', ['uses' => 'Api\MarketController@fetchCharacterItems']);
            Route::get('/market-board/item', ['uses' => 'Api\MarketController@fetchItemData']);
            Route::get('/market-board/{item}/listing-details', ['uses' => 'Api\MarketController@listingDetails']);
            Route::post('/market-board/purchase/{character}', ['uses' => 'Api\MarketController@purchase']);
        });

        Route::get('/market-board/items', ['uses' => 'Api\MarketController@marketItems']);
        Route::post('/market-board/sell-item/{character}', ['uses' => 'Api\MarketController@sellItem']);
    });

    Route::middleware(['can.access.market'])->group(function() {
        Route::get('/market-board/history', ['as' => 'market.history', 'uses' => 'Api\MarketController@history']);
    });
});
