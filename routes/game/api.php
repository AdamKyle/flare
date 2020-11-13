<?php

Route::get('/character-sheet/{character}', ['uses' => 'Api\CharacterSheetController@sheet']);
Route::post('/character-sheet/{character}/name-change', ['uses' => 'Api\CharacterSheetController@nameChange']);

Route::get('/crafting/{character}', ['uses' => 'Api\CharacterSkillController@fetchItemsToCraft']);
Route::post('/craft/{character}', ['uses' => 'Api\CharacterSkillController@trainCrafting']);

Route::get('/notifications', ['uses' => 'Api\NotificationsController@index']);
Route::post('/notifications/clear', ['uses' => 'Api\NotificationsController@clear']);
Route::post('/notifications/{notification}/clear', ['uses' => 'Api\NotificationsController@clearNotification']);
