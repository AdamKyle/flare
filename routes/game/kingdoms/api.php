<?php

Route::middleware(['auth', 'is.character.who.they.say.they.are', 'character.owns.kingdom', 'throttle:500,1'])->group(function() {
    Route::get('/player-kingdoms/{character}', ['as' => 'character.kingdoms', 'uses' => 'Api\KingdomInformationController@getKingdomsList']);
    Route::get('/kingdom/{kingdom}/{character}', ['as' => 'kingdom.character.info', 'uses' => 'Api\KingdomInformationController@getCharacterInfoForKingdom']);
    Route::get('/kingdoms/{character}/{kingdom}', ['as' => 'kingdoms.location', 'uses' => 'Api\KingdomInformationController@getLocationData']);
});

Route::middleware(['auth', 'is.character.dead', 'is.character.exploring', 'is.character.who.they.say.they.are'])->group(function() {
    Route::post('/kingdoms/{character}/settle', ['as' => 'kingdoms.settle', 'uses' => 'Api\KingdomSettleController@settle']);
});

Route::middleware(['auth', 'is.character.dead', 'is.character.exploring', 'is.character.who.they.say.they.are'])->group(function() {
    Route::post('/kingdoms/{character}/upgrade-building/{building}', ['as' => 'kingdoms.building.upgrade', 'uses' => 'Api\KingdomBuildingsController@upgradeKingdomBuilding']);
    Route::post('/kingdoms/building-upgrade/cancel', ['as' => 'kingdoms.building.queue.delete', 'uses' => 'Api\KingdomBuildingsController@removeKingdomBuildingFromQueue']);

    Route::post('/kingdoms/{character}/rebuild-building/{building}', ['as' => 'kingdoms.building.rebuild', 'uses' => 'Api\KingdomBuildingsController@rebuildKingdomBuilding']);
});

Route::middleware(['auth', 'is.character.dead', 'is.character.exploring', 'is.character.who.they.say.they.are', 'character.owns.kingdom'])->group(function() {
    Route::post('/kingdoms/{kingdom}/recruit-units/{gameUnit}', ['as' => 'kingdoms.recruit.units', 'uses' => 'Api\KingdomUnitsController@recruitUnits']);
    Route::post('/kingdoms/recruit-units/cancel', ['as' => 'kingdoms.recruit.units.cancel', 'uses' => 'Api\KingdomUnitsController@cancelRecruit']);
});

Route::middleware(['auth', 'is.character.dead', 'is.character.exploring', 'is.character.who.they.say.they.are', 'character.owns.kingdom'])->group(function() {
    Route::post('/kingdoms/embezzle/{kingdom}', ['as' => 'kingdom.embezzle', 'uses' => 'Api\KingdomTreasuryController@embezzle']);
    Route::post('/kingdoms/mass-embezzle/{character}', ['as' => 'kingdom.mass.embezzle', 'uses' => 'Api\KingdomTreasuryController@massEmbezzle']);
    Route::post('/kingdoms/deposit/{kingdom}', ['as' => 'kingdom.deposit', 'uses' => 'Api\KingdomTreasuryController@deposit']);
});

Route::middleware(['auth', 'is.character.dead', 'is.character.exploring', 'is.character.who.they.say.they.are', 'character.owns.kingdom'])->group(function() {
    Route::post('/kingdoms/purchase-gold-bars/{kingdom}', ['as' => 'kingdom.purchase.bars', 'uses' => 'Api\KingdomGoldBarsController@purchaseGoldBars']);
    Route::post('/kingdoms/withdraw-bars-as-gold/{kingdom}', ['as' => 'kingdom.withdraw.bars', 'uses' => 'Api\KingdomGoldBarsController@withdrawGoldBars']);
});

Route::middleware(['auth'])->group(function() {

    Route::get('/kingdoms/other/{kingdom}', ['as' => 'kingdom.other', 'uses' => 'Api\KingdomInformationController@getOtherKingdomInfo']);

    Route::middleware(['is.character.who.they.say.they.are', 'character.owns.kingdom', 'throttle:500,1'])->group(function() {


        Route::get('/kingdoms/{character}/kingdoms-with-units', ['as' => 'kingdoms.with.units', 'uses' => 'Api\KingdomAttackController@fetchKingdomsWithUnits']);
        Route::get('/kingdom-unit-movement/{character}', ['as' => 'kingdom.unit.movement', 'uses' => 'Api\KingdomUnitMovementController@fetchUnitMovement']);


        Route::post('/kingdom/{kingdom}/rename', ['as' => 'kingdom.rename', 'uses' => 'Api\KingdomsController@rename']);

        Route::post('/kingdoms/abandon/{kingdom}', ['as' => 'abandon.kingdom', 'uses' => 'Api\KingdomsController@abandon']);

        Route::post('/kingdoms/purchase-people/{kingdom}', ['as' => 'kingdom.deposit', 'uses' => 'Api\KingdomsController@purchasePeople']);

        Route::post('/kingdoms/{character}/attack/selection', ['as' => 'kingdom.attack.selection', 'uses' => 'Api\KingdomAttackController@selectKingdoms']);
        Route::post('/kingdoms/{character}/attack', ['as' => 'kingdom.attack', 'uses' => 'Api\KingdomAttackController@attack']);
        Route::post('/use-items-on-kingdom/{character}', ['as' => 'kingdom.attack-with-items', 'uses' => 'Api\KingdomAttackController@useItems']);
        Route::post('/recall-units/{unitMovementQueue}/{character}', ['as' => 'recall.units', 'uses' => 'Api\KingdomUnitMovementController@recallUnits']);
    });
});
