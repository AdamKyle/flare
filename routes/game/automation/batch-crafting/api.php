<?php

Route::middleware([
    'auth',
    'is.player.banned',
    'is.character.who.they.say.they.are',
])->group(function () {
    Route::get('/batch-crafting/{character}/status', ['as' => 'batch-crafting.status', 'uses' => 'Api\BatchCraftingController@status']);

    Route::middleware(['throttle:150,2'])->group(function () {
        Route::post('/batch-crafting/{character}/dismiss', ['as' => 'batch-crafting.dismiss', 'uses' => 'Api\BatchCraftingController@dismiss']);
        Route::post('/batch-crafting/{character}/info/acknowledge', ['as' => 'batch-crafting.info.acknowledge', 'uses' => 'Api\BatchCraftingController@acknowledgeInfo']);
        Route::post('/batch-crafting/{character}/preview', ['as' => 'batch-crafting.preview', 'uses' => 'Api\BatchCraftingController@preview']);
    });

    Route::middleware(['is.character.dead'])->group(function () {
        Route::post('/batch-crafting/{character}/start', ['as' => 'batch-crafting.start', 'uses' => 'Api\BatchCraftingController@start']);
        Route::post('/batch-crafting/{character}/cancel', ['as' => 'batch-crafting.cancel', 'uses' => 'Api\BatchCraftingController@cancel']);
    });
});
