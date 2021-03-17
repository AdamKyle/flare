<?php

Route::get('/kingdoms/{kingdom}', ['as' => 'kingdoms.location', 'uses' => 'Api\KingdomsController@getLocationData']);

Route::post('/kingdoms/{character}/settle', ['as' => 'kingdoms.settle', 'uses' => 'Api\KingdomsController@settle']);

Route::post('/kingdoms/{character}/upgrade-building/{building}', ['as' => 'kingdoms.building.upgrade', 'uses' => 'Api\KingdomsController@upgradeKingdomBuilding']);
Route::post('/kingdoms/building-upgrade/cancel', ['as' => 'kingdoms.building.queue.delete', 'uses' => 'Api\KingdomsController@removeKingdomBuildingFromQueue']);

Route::post('/kingdoms/{character}/rebuild-building/{building}', ['as' => 'kingdoms.building.rebuild', 'uses' => 'Api\KingdomsController@rebuildKingdomBuilding']);

Route::post('/kingdoms/{kingdom}/recruit-units/{gameUnit}', ['as' => 'kingdoms.recruit.units', 'uses' => 'Api\KingdomsController@recruitUnits']);
Route::post('/kingdoms/recruit-units/cancel', ['as' => 'kingdoms.recruit.units.cancel', 'uses' => 'Api\KingdomsController@cancelRecruit']);

Route::post('/kingdoms/embezel/{kingdom}', ['as' => 'kingdom.embezzel', 'uses' => 'Api\KingdomsController@embezzel']);

Route::post('/kingdoms/{character}/attack/selection', ['as' => 'kingdom.attack.selection', 'uses' => 'Api\KingdomAttackController@selectKingdoms']);
Route::post('/kingdoms/{character}/attack', ['as' => 'kingdom.atack', 'uses' => 'Api\KingdomAttackController@attack']);