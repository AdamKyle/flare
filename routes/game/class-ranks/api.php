<?php

Route::middleware([
    'auth',
    'is.player.banned',
    'is.character.who.they.say.they.are',
])->group(function() {

    Route::get('/class-ranks/{character}', ['uses' => 'Api\ClassRanksController@getCharacterClassRanks']);
    Route::get('/class-ranks/{character}/specials/{characterClassRank}', ['uses' => 'Api\ClassRanksController@getCharacterClassSpecialties']);


    Route::middleware(['is.character.dead', 'is.character.exploring'])->group(function() {
        Route::post('/switch-classes/{character}/{gameClass}', ['uses' => 'Api\ManageClassController@switchClass']);

        Route::post('/equip-specialty/{character}/{gameClassSpecial}', ['uses' => 'Api\ClassRanksController@equipSpecial']);
        Route::post('/unequip-specialty/{character}/{classSpecialEquipped}', ['uses' => 'Api\ClassRanksController@unequipSpecial']);
    });
});
