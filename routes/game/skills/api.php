<?php

Route::middleware(['auth', 'is.player.banned', 'is.character.dead', 'is.character.adventuring', 'is.character.who.they.say.they.are'])->group(function() {
    // Fetch Items
    Route::get('/crafting/{character}', ['uses' => 'Api\CraftingController@fetchItemsToCraft']);

    // Fetch Item Affixes
    Route::get('/enchanting/{character}', ['uses' => 'Api\EnchantingController@fetchAffixes']);

    // Fetch Alchemy Items
    Route::get('/alchemy/{character}', ['uses' => 'Api\AlchemyController@alchemyItems']);

    Route::group(['middleware' => 'throttle:crafting'], function() {
        // Craft Item
        Route::post('/craft/{character}', ['uses' => 'Api\CraftingController@craft']);
    });

    Route::group(['middleware' => 'throttle:enchanting'], function() {
        // Enchant Item
        Route::post('/enchant/{character}', ['uses' => 'Api\EnchantingController@enchant']);
    });

    Route::group(['middleware' => 'throttle:25,1'], function() {
        // Enchant Item
        Route::post('/disenchant/{item}', ['uses' => 'Api\DisenchantingController@disenchant']);
        Route::post('/destroy/{item}', ['uses' => 'Api\DisenchantingController@destroy']);
    });

    Route::group(['middleware' => 'throttle:25,1'], function() {
        // Alchemy
        Route::post('/transmute/{character}', ['uses' => 'Api\AlchemyController@transmute']);
    });
});
