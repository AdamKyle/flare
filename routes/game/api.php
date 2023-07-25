<?php

Route::post('/character-timeout', ['uses' => 'Api\CharacterSheetController@globalTimeout']);

Route::group(['middleware' => ['auth']], function() {
    Route::get('/ranked-fight-tops', ['uses' => 'Api\RankTopsController@loadRankTops']);
    Route::get('/rank-fight-tops-list', ['uses' => 'Api\RankTopsController@loadSpecificTop']);

    Route::get('/calendar/events', ['uses' => 'Api\EventCalendarController@loadEvents']);
});

Route::group(['middleware' => ['is.character.who.they.say.they.are']], function() {
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
    Route::post('/character-sheet/{character}/name-change', ['uses' => 'Api\CharacterSheetController@nameChange']);

    Route::middleware(['is.character.exploring'])->group(function() {
        Route::post('/character-sheet/{character}/remove-boon/{boon}', ['uses' => 'Api\CharacterSheetController@cancelBoon']);
    });

    Route::get('/maps/{character}', ['uses' => 'Api\MapsController@index']);

    Route::get('/character/{character}/inventory', ['uses' => 'Api\CharacterInventoryController@inventory']);
    Route::get('/character/{character}/inventory/comparison', ['uses' => 'Api\ItemComparisonController@compareItem']);
    Route::get('/character/{character}/inventory/comparison-from-chat', ['uses' => 'Api\ItemComparisonController@compareItemFromChat']);

    Route::get('/character/{character}/gem-bag', ['uses' => 'Api\CharacterGemBagController@getGemSlots']);
    Route::get('/character/{character}/gem-details/{gemBagSlot}', ['uses' => 'Api\CharacterGemBagController@getGem']);


    Route::group(['middleware' => ['is.character.dead']], function() {
        Route::get('/character/{character}/inventory/item/{item}', ['uses' => 'Api\CharacterInventoryController@itemDetails']);

        Route::middleware(['is.character.exploring'])->group(function() {

            Route::post('/character/{character}/inventory/equip-item', ['uses' => 'Api\CharacterInventoryController@equipItem']);
            Route::post('/character/{character}/inventory/save-equipped-as-set', ['uses' => 'Api\CharacterInventoryController@saveEquippedAsSet']);
            Route::post('/character/{character}/inventory/unequip', ['uses' => 'Api\CharacterInventoryController@unequipItem']);
            Route::post('/character/{character}/inventory/unequip-all', ['uses' => 'Api\CharacterInventoryController@unequipAll']);
            Route::post('/character/{character}/inventory-set/equip/{inventorySet}', ['uses' => 'Api\CharacterInventoryController@equipItemSet']);
            Route::post('/character/{character}/inventory/use-many-items', ['uses' => 'Api\CharacterInventoryController@useManyItems']);
            Route::post('/character/{character}/inventory/use-item/{item}', ['uses' => 'Api\CharacterInventoryController@useItem']);
        });

        Route::post('/character/{character}/inventory/destroy-all-alchemy-items', ['uses' => 'Api\CharacterInventoryController@destroyAllAlchemyItems']);
        Route::post('/character/{character}/inventory/destroy-alchemy-item', ['uses' => 'Api\CharacterInventoryController@destroyAlchemyItem']);


        Route::post('/character/{character}/inventory/destroy', ['uses' => 'Api\CharacterInventoryController@destroy']);
        Route::post('/character/{character}/inventory-set/remove', ['uses' => 'Api\CharacterInventoryController@removeFromSet']);
        Route::post('/character/{character}/inventory-set/{inventorySet}/remove-all', ['uses' => 'Api\CharacterInventoryController@emptySet']);

        Route::post('/character/{character}/inventory/destroy-all', ['uses' => 'Api\CharacterInventoryController@destroyAll']);
        Route::post('/character/{character}/inventory/disenchant-all', ['uses' => 'Api\CharacterInventoryController@disenchantAll']);
        Route::post('/character/{character}/inventory/move-to-set', ['uses' => 'Api\CharacterInventoryController@moveToSet']);
        Route::post('/character/{character}/inventory-set/rename-set', ['uses' => 'Api\CharacterInventoryController@renameSet']);
    });
});
