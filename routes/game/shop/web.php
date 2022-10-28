<?php

Route::middleware([
    'is.player.banned',
    'is.character.who.they.say.they.are',
    'is.globally.timed.out',
])->group(function() {

    Route::middleware(['is.character.exploring'])->group(function() {
        Route::get('/shop/buy/{character}', ['as' => 'game.shop.buy', 'uses' => 'ShopController@shopBuy']);
        Route::get('/shop/sell/{character}', ['as' => 'game.shop.sell', 'uses' => 'ShopController@shopSell']);
        Route::get('/shop/view/comparison/{character}', ['as' => 'game.shop.view.comparison', 'uses' => 'ShopController@viewShopCompare']);
        Route::get('/shop/buy/multiple/{character}', ['as' => 'game.shop.buy.multiple', 'uses' => 'ShopController@puracheMultiple']);
        Route::post('/shop/purchase/multiple/{character}', ['as' => 'game.shop.purchase.multiple', 'uses' => 'ShopController@buyMultiple']);
        Route::post('/shop/compare/item/{character}', ['as' => 'game.shop.compare.item', 'uses' => 'ShopController@shopCompare']);
        Route::post('/shop/sell-all/{character}', ['as' => 'game.shop.sell.all', 'uses' => 'ShopController@shopSellAll']);
        Route::post('/shop/buy/item/{character}', ['as' => 'game.shop.buy.item', 'uses' => 'ShopController@buy']);
        Route::post('/shop/sell/item/{character}', ['as' => 'game.shop.sell.item', 'uses' => 'ShopController@sell']);
        Route::post('/shop/buy-and-replace/{character}', ['as' => 'game.shop.buy-and-replace', 'uses' => 'ShopController@buyAndReplace']);
    });
});
