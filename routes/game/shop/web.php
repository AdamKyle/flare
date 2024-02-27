<?php

Route::middleware([
    'is.player.banned',
    'is.character.who.they.say.they.are',
    'is.globally.timed.out',
])->group(function() {

    Route::middleware(['is.character.exploring'])->group(function() {
        Route::get('/goblin-shop/{character}', ['as' => 'game.goblin-shop.shop', 'uses' => 'GoblinShopController@listItems']);
        Route::post('/goblin-shop/buy/{character}/{item}', ['as' => 'game.goblin-shop.buy', 'uses' => 'GoblinShopController@buyItem']);

        Route::get('/shop/buy/{character}', ['as' => 'game.shop.buy', 'uses' => 'ShopController@shopBuy']);
        Route::get('/shop/sell/{character}', ['as' => 'game.shop.sell', 'uses' => 'ShopController@shopSell']);

        Route::post('/shop/sell-all/{character}', ['as' => 'game.shop.sell.all', 'uses' => 'ShopController@shopSellAll']);
        Route::post('/shop/sell/item/{character}', ['as' => 'game.shop.sell.item', 'uses' => 'ShopController@sell']);
    });
});
