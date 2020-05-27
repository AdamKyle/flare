<?php

// Core routes for the game related stuff:
Route::get('/game', ['as' => 'game', 'uses' => 'GameController@game']);

Route::get('/shop/buy', ['as' => 'game.shop.buy', 'uses' => 'ShopController@shopBuy']);
Route::get('/shop/sell', ['as' => 'game.shop.sell', 'uses' => 'ShopController@shopSell']);
Route::post('/shop/buy/item', ['as' => 'game.shop.buy.item', 'uses' => 'ShopController@buy']);
Route::post('/shop/sell/item', ['as' => 'game.shop.sell.item', 'uses' => 'ShopController@sell']);

Route::get('/game/character/sheet', ['as' => 'game.character.sheet', 'uses' => 'CharacterSheetController@index']);

Route::get('/game/character/inventory', ['as' => 'game.character.inventory', 'uses' => 'CharacterInventoryController@index']);
Route::get('/game/character/inventory/compare', ['as' => 'game.inventory.compare', 'uses' => 'CharacterInventoryController@compare']);
Route::post('/game/equip/item', ['as' => 'game.equip.item', 'uses' => 'CharacterInventoryController@equipItem']);
Route::post('/game/unequip/item', ['as' => 'game.inventory.unequip', 'uses' => 'CharacterInventoryController@unequipItem']);
Route::post('/game/destroy/item', ['as' => 'game.destroy.item', 'uses' => 'CharacterInventoryController@destroy']);

Route::get('/items/{item}', ['as' => 'items.item', 'uses' => 'ItemController@show']);
