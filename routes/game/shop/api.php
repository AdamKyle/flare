<?php

Route::group(['middleware' => [
    'auth',
    'is.character.who.they.say.they.are',
    'is.player.banned',
    'is.character.dead'
]], function() {
    Route::post('/character/{character}/inventory/sell-item', ['uses' => 'Api\ShopController@sellItem']);
    Route::post('/character/{character}/inventory/sell-all', ['uses' => 'Api\ShopController@sellAll']);

    Route::post('/character/{character}/sell-gem/{gemBagSlot}', ['uses' => 'Api\GemShopController@sellSingleGem']);
    Route::post('/character/{character}/sell-all-gems', ['uses' => 'Api\GemShopController@SellAllGems']);
});
