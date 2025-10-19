<?php

Route::post('/character-timeout', ['uses' => 'Api\CharacterSheetController@globalTimeout']);

Route::group(['middleware' => ['auth', 'is.character.who.they.say.they.are']], function () {
    Route::get('/character-sheet/{character}', ['uses' => 'Api\CharacterSheetController@sheet']);
    Route::get('/character-sheet/{character}/active-boons', ['uses' => 'Api\CharacterSheetController@activeBoons']);
    Route::get('/character-sheet/{character}/factions', ['uses' => 'Api\CharacterSheetController@factions']);
    Route::get('/character-sheet/{character}/automations', ['uses' => 'Api\CharacterSheetController@automations']);
    Route::get('/character-sheet/{character}/skills', ['uses' => 'Api\CharacterSheetController@skills']);
    Route::get('/character-sheet/{character}/base-inventory-info', ['uses' => 'Api\CharacterSheetController@baseInventoryInfo']);
    Route::get('/character-sheet/{character}/stat-details', ['uses' => 'Api\CharacterSheetController@statDetails']);
    Route::get('/character-sheet/{character}/stat-break-down', ['uses' => 'Api\CharacterSheetController@statBreakDown']);
    Route::get('/character-sheet/{character}/specific-attribute-break-down', ['uses' => 'Api\CharacterSheetController@specificStatBreakDown']);

    Route::get('/character-location-data/{character}', ['uses' => 'Api\CharacterSheetController@basicLocationInformation']);

    Route::post('/character-sheet/{character}/name-change', ['uses' => 'Api\CharacterSheetController@nameChange']);

    Route::middleware(['is.character.exploring'])->group(function () {
        Route::post('/character-sheet/{character}/remove-boon/{boon}', ['uses' => 'Api\CharacterSheetController@cancelBoon']);
    });
});
