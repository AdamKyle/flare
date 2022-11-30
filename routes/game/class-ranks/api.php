<?php

Route::middleware([
    'auth',
    'is.player.banned',
    'is.character.who.they.say.they.are',
])->group(function() {

    Route::get('/class-ranks/{character}', ['uses' => 'Api\ClassRanksController@getCharacterClassRanks']);


    Route::middleware(['is.character.dead', 'is.character.exploring'])->group(function() {
        Route::post('/switch-classes/{character}', ['uses' => 'Api\ManageClassController@switchClass']);
    });
});
