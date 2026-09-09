<?php

Route::middleware(['auth', 'is.admin'])->group(function () {
    Route::get('/admin/location-gems', ['uses' => 'Api\LocationGemsController@index']);
    Route::get('/admin/location-gems/options', ['uses' => 'Api\LocationGemsController@options']);
    Route::post('/admin/location-gems', ['uses' => 'Api\LocationGemsController@store']);
    Route::post('/admin/location-gems/import', ['uses' => 'Api\LocationGemImportController']);
    Route::post('/admin/location-gems/roll-all', ['uses' => 'Api\LocationGemsController@rollAll']);
    Route::get('/admin/location-gems/{gameLocationGemParamter}/edit', ['uses' => 'Api\LocationGemsController@edit']);
    Route::put('/admin/location-gems/{gameLocationGemParamter}', ['uses' => 'Api\LocationGemsController@update']);
    Route::post('/admin/location-gems/{gameLocationGemParamter}/roll', ['uses' => 'Api\LocationGemsController@roll']);
    Route::put('/admin/location-gems/{gameLocationGemParamter}/rolls/{gem}/activate', ['uses' => 'Api\LocationGemsController@activateRoll']);
    Route::get('/admin/location-gems/{gameLocationGemParamter}/rolls', ['uses' => 'Api\LocationGemsController@rolls']);
    Route::get('/admin/location-gems/{gameLocationGemParamter}', ['uses' => 'Api\LocationGemsController@show']);
});
