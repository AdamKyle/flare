<?php

Route::get('/adeventures/{adventure}', ['as' => 'map.adventures.adventure', 'uses' => 'AdventuresController@show']);