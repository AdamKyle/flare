<?php

Route::post('/character-timeout', ['uses' => 'Api\CharacterSheetController@globalTimeout']);

Route::group(['middleware' => ['auth']], function() {
    Route::get('/ranked-fight-tops', ['uses' => 'Api\RankTopsController@loadRankTops']);
    Route::get('/rank-fight-tops-list', ['uses' => 'Api\RankTopsController@loadSpecificTop']);
});

Route::group(['middleware' => ['is.character.who.they.say.they.are']], function() {
    Route::get('/character-sheet/{character}', ['uses' => 'Api\CharacterSheetController@sheet']);
    Route::get('/character-sheet/{character}/active-boons', ['uses' => 'Api\CharacterSheetController@activeBoons']);
    Route::get('/character-sheet/{character}/factions', ['uses' => 'Api\CharacterSheetController@factions']);
    Route::get('/character-sheet/{character}/automations', ['uses' => 'Api\CharacterSheetController@automations']);
    Route::get('/character-sheet/{character}/skills', ['uses' => 'Api\CharacterSheetController@skills']);
    Route::get('/character-sheet/{character}/base-inventory-info', ['uses' => 'Api\CharacterSheetController@baseInventoryInfo']);
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

            Route::get('/character/{character}/inventory/uniques', ['uses' => 'Api\RandomEnchantController@uniquesOnly']);
            Route::post('/character/{character}/random-enchant/purchase', ['uses' => 'Api\RandomEnchantController@purchase']);
            Route::post('/character/{character}/random-enchant/reroll', ['uses' => 'Api\RandomEnchantController@reRoll']);
            Route::post('/character/{character}/random-enchant/move', ['uses' => 'Api\RandomEnchantController@moveAffixes']);

            Route::get('/character/{character}/inventory/smiths-workbench', ['uses' => 'Api\HolyItemsController@index']);
            Route::post('/character/{character}/smithy-workbench/apply', ['uses' => 'Api\HolyItemsController@apply']);

            Route::get('/visit-seer-camp/{character}', ['uses' => 'Api\SeerCampController@visitCamp']);
            Route::get('/seer-camp/gems-to-remove/{character}', ['uses' => 'Api\SeerCampController@fetchItemsWithGems']);
            Route::post('/seer-camp/add-sockets/{character}', ['uses' => 'Api\SeerCampController@rollSockets']);
            Route::post('/seer-camp/add-gem/{character}', ['uses' => 'Api\SeerCampController@attachGemToItem']);
            Route::post('/seer-camp/replace-gem/{character}', ['uses' => 'Api\SeerCampController@replaceGemOnItem']);
            Route::post('/seer-camp/remove-gem/{character}', ['uses' => 'Api\SeerCampController@removeGemFromItem']);
            Route::post('/seer-camp/remove-all-gems/{character}/{inventorySlot}', ['uses' => 'Api\SeerCampController@removeAllGemsFromItem']);
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
