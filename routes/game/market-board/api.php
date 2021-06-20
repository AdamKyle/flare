<?php

Route::group(['middleware' => [
    'auth',
    'throttle:100,1',
    'is.character.who.they.say.they.are',
    'is.character.dead',
    'is.character.adventuring',
    'is.character.at.location',
]], function() {
    Route::get('/market-board/character-items/{character}', ['uses' => 'Api\MarketController@fetchCharacterItems']);
    Route::get('/market-board/item', ['uses' => 'Api\MarketController@fetchItemData']);
});
