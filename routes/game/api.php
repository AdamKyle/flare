<?php

Route::get('/character-sheet/{character}', ['uses' => 'Api\CharacterSheetController@sheet']);
