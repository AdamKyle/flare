<?php

Route::get('/items/{item}', ['as' => 'game.items.item', 'uses' => 'ItemsController@show']);
Route::get('/monsters/{monster}', ['as' => 'game.monsters.monster', 'uses' => 'MonstersController@show']);
Route::get('/locations/{location}', ['as' => 'game.locations.location', 'uses' => 'LocationsController@show']);

Route::middleware(['is.player.banned', 'is.character.who.they.say.they.are', 'is.globally.timed.out'])->group(function() {

    // Core routes for the game related stuff:
    Route::get('/game', ['as' => 'game', 'uses' => 'GameController@game']);

    Route::get('/shop/buy/{character}', ['as' => 'game.shop.buy', 'uses' => 'ShopController@shopBuy']);
    Route::get('/shop/sell/{character}', ['as' => 'game.shop.sell', 'uses' => 'ShopController@shopSell']);
    Route::post('/shop/sell-all/{character}', ['as' => 'game.shop.sell.all', 'uses' => 'ShopController@shopSellAll']);
    Route::post('/shop/buy-bulk/{character}', ['as' => 'game.shop.buy.bulk', 'uses' => 'ShopController@shopBuyBulk']);
    Route::post('/shop/buy/item/{character}', ['as' => 'game.shop.buy.item', 'uses' => 'ShopController@buy']);
    Route::post('/shop/sell-bulk/{character}', ['as' => 'game.shop.sell.bulk', 'uses' => 'ShopController@shopSellBulk']);
    Route::post('/shop/sell/item/{character}', ['as' => 'game.shop.sell.item', 'uses' => 'ShopController@sell']);

    Route::get('/game/character/sheet', ['as' => 'game.character.sheet', 'uses' => 'CharacterSheetController@index']);

    Route::get('/game/character/inventory/compare/{character}', ['as' => 'game.inventory.compare', 'uses' => 'CharacterInventoryController@compare']);
    Route::get('/game/character/inventory/compare-items/{user}', ['as' => 'game.inventory.compare-items', 'uses' => 'CharacterInventoryController@compareItem']);
    Route::post('/game/equip/item/{character}', ['as' => 'game.equip.item', 'uses' => 'CharacterInventoryController@equipItem']);

    Route::get('/skill/{skill}', ['as' => 'skill.character.info', 'uses' => 'CharacterSkillController@show']);

    Route::get('/current-adventure/', ['as' => 'game.current.adventure', 'uses' => 'CharacterAdventureController@currentAdventure']);
    Route::get('/current-adventures/', ['as' => 'game.completed.adventures', 'uses' => 'CharacterAdventureController@completedAdventures']);
    Route::get('/completed-adventure/{adventureLog}', ['as' => 'game.completed.adventure', 'uses' => 'CharacterAdventureController@completedAdventure']);
    Route::get('/completed-adventure/{adventureLog}/logs/{name}', ['as' => 'game.completed.adventure.logs', 'uses' => 'CharacterAdventureController@completedAdventureLogs']);
    Route::post('/current-adventures/batch-delete', ['as' => 'game.adventures.batch-delete', 'uses' => 'CharacterAdventureController@batchDelete']);
    Route::post('/current-adventures/delete/{adventureLog}', ['as' => 'game.adventures.delete', 'uses' => 'CharacterAdventureController@delete']);
    Route::post('/current-adventure/{adventureLog}/distribute-rewards', ['as' => 'game.current.adventure.reward', 'uses' => 'CharacterAdventureController@collectReward']);

    Route::get('/settings/{user}', ['as' => 'user.settings', 'uses' => 'SettingsController@index']);
    Route::post('/settings/{user}/chat-settings', ['as' => 'user.settings.chat', 'uses' => 'SettingsController@chatSettings']);
    Route::post('/settings/{user}/email-settings', ['as' => 'user.settings.email', 'uses' => 'SettingsController@emailSettings']);
    Route::post('/settings/{user}/character-name', ['as' => 'user.settings.character', 'uses' => 'SettingsController@characterSettings']);
});
