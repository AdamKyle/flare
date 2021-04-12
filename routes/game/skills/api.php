<?php

Route::middleware(['auth:api', 'is.player.banned', 'is.character.dead', 'is.character.adventuring'])->group(function() {
    Route::group(['middleware' => 'throttle:6,1'], function() {
        // Fetch Items
        Route::get('/crafting/{character}', ['uses' => 'Api\CraftingController@fetchItemsToCraft']);

        // Craft Item
        Route::post('/craft/{character}', ['uses' => 'Api\CraftingController@craft']);

        // Fetch Item Affixes
        Route::get('/enchanting/{character}', ['uses' => 'Api\EnchantingController@fetchAffixes']);

        // Enchant Item
        Route::post('/enchant/{character}', ['uses' => 'Api\EnchantingController@enchant']);
    });
});
