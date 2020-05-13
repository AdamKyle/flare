<?php

// Core routes for the game related stuff:
Route::get('/game', ['as' => 'game', 'uses' => 'GameController@game']);

Route::get('/shop/buy', ['as' => 'game.shop.buy', 'uses' => 'ShopController@shopBuy']);
Route::get('/shop/sell', ['as' => 'game.shop.sell', 'uses' => 'ShopController@shopSell']);
