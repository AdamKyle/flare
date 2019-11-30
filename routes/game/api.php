<?php

Route::get('/character-sheet/{character}', ['uses' => 'Api\CharacterSheetController@sheet']);
Route::get('/character-inventory/{character}', ['uses' => 'Api\CharacterInventoryController@inventory']);
