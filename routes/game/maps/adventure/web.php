<?php

Route::get('/adeventures/{adventure}', ['as' => 'game.adventures.adventure', 'uses' => 'AdventuresController@show']);