<?php

Route::group(['middleware' => [
    'auth',
    'is.character.who.they.say.they.are',
    'is.character.dead',
    'can.access.market',
    'is.character.exploring'
]], function() {
    Route::get('/market/', ['as' => 'game.market', 'uses' => 'MarketController@index']);
    Route::get('/market/sell', ['as' => 'game.market.sell', 'uses' => 'MarketController@sell']);
    Route::get('/market/current-listings/{character}', ['as' => 'game.current-listings', 'uses' => 'MarketController@currentListings']);

    Route::get('/market/current-listings/edit/{marketBoard}', ['as' => 'game.edit.current-listings', 'uses' => 'MarketController@editCurrentListings']);
    Route::get('/market/view/comparison/{character}/{marketBoard}', ['as' => 'game.market.view.comparison', 'uses' => 'MarketController@viewItemComparison']);
    Route::post('/market/current-listing/{marketBoard}/update', ['as' => 'game.update.current-listing', 'uses' => 'MarketController@updateCurrentListing']);
    Route::post('/market/current-listing/{marketBoard}/delist', ['as' => 'game.delist.current-listing', 'uses' => 'MarketController@delist']);
    Route::post('/market/compare/item/{character}/{marketBoard}', ['as' => 'game.market.compare.item', 'uses' => 'MarketController@marketCompare']);
    Route::post('/market/buy-and-replace/{character}', ['as' => 'game.market.buy-and-replace', 'uses' => 'MarketController@buyAndReplace']);
    Route::post('/market/buy/{character}', ['as' => 'game.market.buy', 'uses' => 'MarketController@buy']);
});
