<?php

Route::middleware('is.player.banned')->group(function() {
    
    // Core routes for the game related stuff:
    Route::get('/game', ['as' => 'game', 'uses' => 'GameController@game']);

    Route::get('/shop/buy', ['as' => 'game.shop.buy', 'uses' => 'ShopController@shopBuy']);
    Route::get('/shop/sell', ['as' => 'game.shop.sell', 'uses' => 'ShopController@shopSell']);
    Route::post('/shop/sell-all', ['as' => 'game.shop.sell.all', 'uses' => 'ShopController@shopSellAll']);
    Route::post('/shop/buy-bulk', ['as' => 'game.shop.buy.bulk', 'uses' => 'ShopController@shopBuyBulk']);
    Route::post('/shop/buy/item', ['as' => 'game.shop.buy.item', 'uses' => 'ShopController@buy']);
    Route::post('/shop/sell-bulk', ['as' => 'game.shop.sell.bulk', 'uses' => 'ShopController@shopSellBulk']);
    Route::post('/shop/sell/item', ['as' => 'game.shop.sell.item', 'uses' => 'ShopController@sell']);
    
    Route::get('/game/character/sheet', ['as' => 'game.character.sheet', 'uses' => 'CharacterSheetController@index']);
    
    Route::get('/game/character/inventory/compare', ['as' => 'game.inventory.compare', 'uses' => 'CharacterInventoryController@compare']);
    Route::get('/game/character/inventory/compare-items', ['as' => 'game.inventory.compare-items', 'uses' => 'CharacterInventoryController@compareItem']);
    Route::post('/game/equip/item', ['as' => 'game.equip.item', 'uses' => 'CharacterInventoryController@equipItem']);
    Route::post('/game/unequip/all', ['as' => 'game.unequip.all', 'uses' => 'CharacterInventoryController@unequipAll']);
    Route::post('/game/unequip/item', ['as' => 'game.inventory.unequip', 'uses' => 'CharacterInventoryController@unequipItem']);
    Route::post('/game/destroy/item', ['as' => 'game.destroy.item', 'uses' => 'CharacterInventoryController@destroy']);
    
    Route::get('/items/{item}', ['as' => 'items.item', 'uses' => 'ItemsController@show']);
    Route::get('/monsters/{monster}', ['as' => 'game.monsters.monster', 'uses' => 'MonstersController@show']);
    Route::get('/locations/{location}', ['as' => 'game.locations.location', 'uses' => 'LocationsController@show']);
    
    Route::get('/skill/{skill}', ['as' => 'skill.character.info', 'uses' => 'CharacterSkillController@show']);
    Route::post('/skill/train/{character}', ['as' => 'train.skill', 'uses' => 'CharacterSkillController@train']);
    Route::post('/skill/cancel-train/{skill}', ['as' => 'cancel.train.skill', 'uses' => 'CharacterSkillController@cancelTrain']);
    
    Route::get('/current-adventure/', ['as' => 'game.current.adventure', 'uses' => 'CharacterAdventureController@currentAdventure']);
    Route::get('/current-adventures/', ['as' => 'game.completed.adventures', 'uses' => 'CharacterAdventureController@completedAdventures']);
    Route::get('/completed-adventure/{adventureLog}', ['as' => 'game.completed.adventure', 'uses' => 'CharacterAdventureController@completedAdventure']);
    Route::get('/completed-adventure/{adventureLog}/logs/{name}', ['as' => 'game.completed.adventure.logs', 'uses' => 'CharacterAdventureController@completedAdventureLogs']);
    Route::post('/current-adventure/{adventureLog}/distribute-rewards', ['as' => 'game.current.adventure.reward', 'uses' => 'CharacterAdventureController@collectReward']);
});
