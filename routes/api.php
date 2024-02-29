<?php

Route::middleware('throttle:20,1')->group(function() {
    Route::get('/items-list', ['uses' => 'Api\ItemsController@fetchCraftableItems']);
    Route::get('/items-list-for-type', ['uses' => 'Api\ItemsController@fetchSpecificSet']);
});
