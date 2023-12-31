<?php

Route::group(['middleware' => ['is.character.who.they.say.they.are']], function() {
    Route::get('/faction-loyalty/{character}', ['uses' => 'Api\FactionLoyaltyController@fetchLoyaltyInfo']);
    Route::post('/faction-loyalty/remove-pledge/{character}/{faction}', ['uses' => 'Api\FactionLoyaltyController@removePledge']);
    Route::post('/faction-loyalty/pledge/{character}/{faction}', ['uses' => 'Api\FactionLoyaltyController@pledgeLoyalty']);

});
