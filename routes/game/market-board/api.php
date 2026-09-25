<?php

Route::group(['middleware' => [
    'auth',
    'throttle:100,1',
    'is.character.who.they.say.they.are',
    'is.player.banned',
]], function () {
    Route::get('/market-board/access/{character}', ['uses' => 'Api\MarketController@access']);
});

Route::group(['middleware' => [
    'auth',
    'throttle:100,1',
    'is.character.who.they.say.they.are',
    'is.character.dead',
    'is.player.banned',
    'can.access.market',
]], function () {
    Route::middleware(['is.character.exploring'])->group(function () {
        Route::get('/market-board/items', ['uses' => 'Api\MarketController@marketItems']);
        Route::get('/market-board/items/{marketBoard}', ['uses' => 'Api\MarketController@listing']);
        Route::post('/market-board/items/{marketBoard}/compare/{character}', ['uses' => 'Api\MarketController@compare']);
        Route::post('/market-board/items/{marketBoard}/buy/{character}', ['uses' => 'Api\MarketController@buy']);
        Route::post('/market-board/items/{marketBoard}/buy-and-replace/{character}', ['uses' => 'Api\MarketController@buyAndReplace']);

        Route::get('/market-board/current-listings/{character}', ['uses' => 'Api\MarketController@currentListings']);
        Route::post('/market-board/current-listings/{marketBoard}/edit/{character}', ['uses' => 'Api\MarketController@beginEdit']);
        Route::patch('/market-board/current-listings/{marketBoard}/{character}', ['uses' => 'Api\MarketController@updateListing']);
        Route::post('/market-board/current-listings/{marketBoard}/cancel-edit/{character}', ['uses' => 'Api\MarketController@cancelEdit']);
        Route::delete('/market-board/current-listings/{marketBoard}/{character}', ['uses' => 'Api\MarketController@delist']);

        Route::get('/market-history/fetch-history-for-type', ['uses' => 'Api\MarketController@fetchMarketHistoryForItem']);

        Route::post('/market-board/sell-item/{character}', ['uses' => 'Api\MarketController@sellItem']);
    });
});
