<?php

Route::group(['middleware' => [
    'auth',
    'is.character.who.they.say.they.are',
    'is.player.banned',
    'is.character.exploring',
    'is.character.adventuring',
    'is.character.dead'
]], function() {
    Route::post('/character/{character}/inventory/sell-item', ['uses' => 'Api\Shopcontroller@sellItem']);
});
