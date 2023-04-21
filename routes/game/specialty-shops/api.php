<?php

Route::group(['middleware' => ['is.character.who.they.say.they.are', 'is.character.dead']], function() {

    Route::get('/specialty-shop/{character}', ['uses' => 'Api\SpecialtyShopController@fetchItems']);
    Route::post('/specialty-shop/purchase/{character}', ['uses' => 'Api\SpecialtyShopController@purchaseItem']);
});
