<?php

Route::group(['middleware' => [
    'auth',
    'is.character.who.they.say.they.are',
    'is.player.banned',
]], function () {
    Route::get('/character/{character}/visit-shop', ['uses' => 'Api\ShopController@fetchItemsForShop']);
});

Route::group(['middleware' => [
    'auth',
    'is.character.who.they.say.they.are',
    'is.player.banned',
    'is.character.dead',
]], function () {

    Route::get('/shop/view/comparison/{character}', ['uses' => 'Api\ShopController@shopCompare']);

    Route::post('/character/{character}/inventory/sell-item', ['uses' => 'Api\ShopController@sellItem']);
    Route::post('/character/{character}/inventory/sell-all', ['uses' => 'Api\ShopController@sellAll']);

    Route::post('/character/{character}/sell-gem/{gemBagSlot}', ['uses' => 'Api\GemShopController@sellSingleGem']);
    Route::post('/character/{character}/sell-all-gems', ['uses' => 'Api\GemShopController@SellAllGems']);

    Route::post('/shop/buy/item/{character}', ['uses' => 'Api\ShopController@buy']);
    Route::post('/shop/purchase/multiple/{character}', ['uses' => 'Api\ShopController@buyMultiple']);
    Route::post('/shop/buy-and-replace/{character}', ['uses' => 'Api\ShopController@buyAndReplace']);
});
