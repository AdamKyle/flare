<?php

Route::middleware(['auth:api', 'is.player.banned', 'is.character.dead', 'is.character.adventuring'])->group(function() {
    Route::get('/crafting/{character}', ['uses' => 'Api\CraftingController@fetchItemsToCraft']);
    Route::post('/craft/{character}', ['uses' => 'Api\CraftingController@craft']);

    Route::get('/enchanting/{character}', ['uses' => 'Api\EnchantingController@fetchAffixes']);
    Route::post('/enchant/{character}', ['uses' => 'Api\EnchantingController@enchant']);
});