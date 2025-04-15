<?php

Route::get('/character/{character}/inventory', ['uses' => 'Api\CharacterInventoryController@inventory']);
Route::get('/character/{character}/quest-items', ['uses' => 'Api\CharacterInventoryController@questItems']);
Route::get('/character/{character}/usable-items', ['uses' => 'Api\CharacterInventoryController@usableItems']);
Route::get('/character/{character}/equipped_items',  ['uses' => 'Api\CharacterInventoryController@equippedItems']);
Route::get('/character/{character}/inventory/comparison', ['uses' => 'Api\ItemComparisonController@compareItem']);
Route::get('/character/{character}/inventory/comparison-from-chat', ['uses' => 'Api\ItemComparisonController@compareItemFromChat']);
Route::get('/character/{character}/inventory/sets', ['uses' => 'Api\CharacterInventoryController@currentSets']);
Route::get('/character/{character}/inventory/set-items', ['uses' => 'Api\CharacterInventoryController@getSetItems']);

Route::get('/character/{character}/gem-bag', ['uses' => 'Api\CharacterGemBagController@getGemSlots']);
Route::get('/character/{character}/gem-details/{gemBagSlot}', ['uses' => 'Api\CharacterGemBagController@getGem']);

Route::group(['middleware' => ['is.character.dead']], function () {
    Route::get('/character/{character}/inventory/item/{item}', ['uses' => 'Api\CharacterInventoryController@itemDetails']);

    Route::middleware(['is.character.exploring'])->group(function () {

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

    Route::post('/character/{character}/inventory/equip-selected', ['uses' => 'Api\CharacterInventoryMultiController@equipSelected']);
    Route::post('/character/{character}/inventory/destroy-selected', ['uses' => 'Api\CharacterInventoryMultiController@destroySelected']);
    Route::post('/character/{character}/inventory/disenchant-selected', ['uses' => 'Api\CharacterInventoryMultiController@disenchantSelected']);
    Route::post('/character/{character}/inventory/move-selected', ['uses' => 'Api\CharacterInventoryMultiController@moveSelected']);
    Route::post('/character/{character}/inventory/sell-selected', ['uses' => 'Api\CharacterInventoryMultiController@sellSelected']);
});
