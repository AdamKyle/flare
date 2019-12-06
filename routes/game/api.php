<?php

Route::get('/character-sheet/{character}', ['uses' => 'Api\CharacterSheetController@sheet']);
Route::get('/character-inventory/{character}', ['uses' => 'Api\CharacterInventoryController@inventory']);
Route::post('/equip-item/{character}', ['uses' => 'Api\CharacterInventoryController@equipItem']);
Route::delete('/unequip-item/{character}', ['uses' => 'Api\CharacterInventoryController@unequipItem']);
