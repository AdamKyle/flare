<?php

Route::middleware([
    'auth',
    'is.player.banned',
    'is.character.who.they.say.they.are',
    'is.character.dead',
    'is.character.adventuring'
])->group(function() {
    Route::get('/attack-automation/{character}', ['as' => 'attack.automation.index', 'uses' => 'Api\AttackAutomationController@index']);
    Route::post('/attack-automation/{character}/start', ['as' => 'attack.automation.start', 'uses' => 'Api\AttackAutomationController@begin']);
    Route::post('/attack-automation/{characterAutomation}/{character}/stop', ['as' => 'attack.automation.stop', 'uses' => 'Api\AttackAutomationController@stop']);
});
