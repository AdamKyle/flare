<?php

Route::get('/character-sheet/{character}', ['uses' => 'Api\CharacterSheetController@sheet']);

Route::get('/crafting/{character}', ['uses' => 'Api\CharacterSkillController@fetchItemsToCraft']);
Route::post('/craft/{character}', ['uses' => 'Api\CharacterSkillController@trainCrafting']);

Route::post('/character/{character}/adventure/{adventure}', ['uses' => 'Api\AdventureController@adventure']);
Route::post('/character/{character}/adventure/{adventure}/cancel', ['uses' => 'Api\AdventureController@cancelAdventure']);
