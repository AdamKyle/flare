<?php

Route::group(['middleware' => ['is.character.who.they.say.they.are']], function() {
    Route::get('/faction-loyalty/{character}', ['uses' => 'Api\FactionLoyaltyController@fetchLoyaltyInfo']);
    Route::post('/faction-loyalty/remove-pledge/{character}/{faction}', ['uses' => 'Api\FactionLoyaltyController@removePledge']);
    Route::post('/faction-loyalty/pledge/{character}/{faction}', ['uses' => 'Api\FactionLoyaltyController@pledgeLoyalty']);
    Route::post('/faction-loyalty/assist/{character}/{factionLoyaltyNpc}', ['uses' => 'Api\FactionLoyaltyController@assistNpc']);
    Route::post('/faction-loyalty/stop-assisting/{character}/{factionLoyaltyNpc}', ['uses' => 'Api\FactionLoyaltyController@stopAssistingNpc']);
});
