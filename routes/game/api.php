<?php

Route::post('/character-timeout', ['uses' => 'Api\CharacterSheetController@globalTimeout']);

Route::group(['middleware' => ['auth']], function () {
    Route::get('/ranked-fight-tops', ['uses' => 'Api\RankTopsController@loadRankTops']);
    Route::get('/rank-fight-tops-list', ['uses' => 'Api\RankTopsController@loadSpecificTop']);

    Route::get('/calendar/events', ['uses' => 'Api\EventCalendarController@loadEvents']);
});

Route::group(['middleware' => ['is.character.who.they.say.they.are']], function () {
    Route::get('/character-sheet/{character}', ['uses' => 'Api\CharacterSheetController@sheet']);
    Route::get('/character-sheet/{character}/active-boons', ['uses' => 'Api\CharacterSheetController@activeBoons']);
    Route::get('/character-sheet/{character}/factions', ['uses' => 'Api\CharacterSheetController@factions']);
    Route::get('/character-sheet/{character}/automations', ['uses' => 'Api\CharacterSheetController@automations']);
    Route::get('/character-sheet/{character}/skills', ['uses' => 'Api\CharacterSheetController@skills']);
    Route::get('/character-sheet/{character}/base-inventory-info', ['uses' => 'Api\CharacterSheetController@baseInventoryInfo']);
    Route::get('/character-sheet/{character}/stat-details', ['uses' => 'Api\CharacterSheetController@statDetails']);
    Route::get('/character-sheet/{character}/resistance-info', ['uses' => 'Api\CharacterSheetController@resistanceInfo']);
    Route::get('/character-sheet/{character}/reincarnation-info', ['uses' => 'Api\CharacterSheetController@reincarnationInfo']);
    Route::get('/character-sheet/{character}/elemental-atonement-info', ['uses' => 'Api\CharacterSheetController@elementalAtonementInfo']);
    Route::get('/character-location-data/{character}', ['uses' => 'Api\CharacterSheetController@basicLocationInformation']);
    Route::get('/character-base-data/{character}', ['uses' => 'Api\CharacterSheetController@baseCharacterInformation']);
    Route::get('/update-character-timers/{character}', ['uses' => 'Api\TimersController@updateTimersForCharacter']);

    Route::post('/character-sheet/{character}/name-change', ['uses' => 'Api\CharacterSheetController@nameChange']);

    Route::middleware(['is.character.exploring'])->group(function () {
        Route::post('/character-sheet/{character}/remove-boon/{boon}', ['uses' => 'Api\CharacterSheetController@cancelBoon']);
    });

    Route::get('/maps/{character}', ['uses' => 'Api\MapsController@index']);
});
