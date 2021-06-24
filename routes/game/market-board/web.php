<?php

Route::group(['middleware' => [
    'auth',
    'is.character.who.they.say.they.are',
    'is.character.dead',
    'is.character.adventuring',
    'can.access.market',
]], function() {
    Route::get('/market/', ['as' => 'game.market', 'uses' => 'MarketController@index']);
    Route::get('/market/sell', ['as' => 'game.market.sell', 'uses' => 'MarketController@sell']);
    Route::get('/market/current-listings/{character}', ['as' => 'game.current-listings', 'uses' => 'MarketController@currentListings']);

    Route::get('/market/current-listings/edit/{marketBoard}', ['as' => 'game.edit.current-listings', 'uses' => 'MarketController@editCurrentListings']);
    Route::post('/market/current-listing/{marketBoard}/update', ['as' => 'game.update.current-listing', 'uses' => 'MarketController@updateCurrentListing']);
    Route::post('/market/current-listing/{marketBoard}/delist', ['as' => 'game.delist.current-listing', 'uses' => 'MarketController@delist']);
});
