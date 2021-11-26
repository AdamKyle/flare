<?php

Route::middleware([
    'auth',
    'is.player.banned',
    'is.character.who.they.say.they.are',
    'is.character.adventuring',
])->group(function() {
    Route::post('/view/passive/{characterPassiveSkill}/{character}', ['as' => 'view.passive.skill', 'uses' => 'CharacterPassiveSkillController@viewSkill']);
});