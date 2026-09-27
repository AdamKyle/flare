<?php

Route::middleware(['auth', 'is.admin'])->group(function () {
    Route::get('/admin/passive-skills', ['uses' => 'Api\PassiveSkillsController@index']);
    Route::get('/admin/passive-skills/options', ['uses' => 'Api\PassiveSkillsController@options']);
    Route::get('/admin/passive-skills/tree', ['uses' => 'Api\PassiveSkillsController@tree']);
    Route::post('/admin/passive-skills', ['uses' => 'Api\PassiveSkillsController@store']);
    Route::post('/admin/passive-skills/import', ['uses' => 'Api\PassiveSkillImportController']);
    Route::get('/admin/passive-skills/{passiveSkill}/edit', ['uses' => 'Api\PassiveSkillsController@edit']);
    Route::put('/admin/passive-skills/{passiveSkill}', ['uses' => 'Api\PassiveSkillsController@update']);
    Route::get('/admin/passive-skills/{passiveSkill}', ['uses' => 'Api\PassiveSkillsController@show']);
});
