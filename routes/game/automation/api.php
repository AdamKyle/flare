<?php

Route::middleware([
    'auth',
    'is.player.banned',
    'is.character.who.they.say.they.are',
    'is.character.dead',
    'is.character.adventuring'
])->group(function() {
    Route::post('/attack-automation/{character}/start', ['uses' => 'Api\AttackAutomationController@begin']);
    Route::post('/attack-automation/{character}/stop', ['uses' => 'Api\AttackAutomationController@stop']);
});
