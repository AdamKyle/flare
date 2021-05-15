<?php

Route::middleware(['auth:api', 'is.player.banned', 'is.character.dead', 'is.character.adventuring', 'is.character.who.they.say.they.are'])->group(function() {
    // Fetch Items
    Route::get('/crafting/{character}', ['uses' => 'Api\CraftingController@fetchItemsToCraft']);

    // Fetch Item Affixes
    Route::get('/enchanting/{character}', ['uses' => 'Api\EnchantingController@fetchAffixes']);

    Route::group(['middleware' => 'throttle:crafting'], function() {

        // Craft Item
        Route::post('/craft/{character}', ['uses' => 'Api\CraftingController@craft']);
    });

    Route::group(['middleware' => 'throttle:enchanting'], function() {
        // Enchant Item
        Route::post('/enchant/{character}', ['uses' => 'Api\EnchantingController@enchant']);
    });
});
