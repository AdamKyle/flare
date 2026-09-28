<?php

Route::middleware(['auth', 'is.admin'])->group(function () {
    Route::get('/admin/gem-abilities', ['uses' => 'Api\GemAbilitiesController@index']);
    Route::get('/admin/gem-abilities/options', ['uses' => 'Api\GemAbilitiesController@options']);
    Route::post('/admin/gem-abilities', ['uses' => 'Api\GemAbilitiesController@store']);
    Route::get('/admin/gem-abilities/{gameGemAbility}/edit', ['uses' => 'Api\GemAbilitiesController@edit']);
    Route::put('/admin/gem-abilities/{gameGemAbility}', ['uses' => 'Api\GemAbilitiesController@update']);
    Route::get('/admin/gem-abilities/{gameGemAbility}', ['uses' => 'Api\GemAbilitiesController@show']);
});
