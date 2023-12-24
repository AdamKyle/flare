<?php

Route::group(['middleware' => ['is.character.who.they.say.they.are']], function() {
    Route::get('/faction-loyalty/{character}', ['uses' => 'Api\FactionLoyaltyController@fetchLoyaltyInfo']);
});
