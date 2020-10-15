<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    if (!Auth::check()) {
        return view('welcome');
    }
    
    if (auth()->user()->hasRole('Admin')) {
        return redirect()->route('home');
    }

    return redirect()->route('game');
});

Route::get('/information/{pageName}', ['as' => 'info.page', 'uses' => 'InfoPageController@viewPage']);
Route::get('/information/race/{race}', ['as' => 'info.page.race', 'uses' => 'InfoPageController@viewRace']);
Route::get('/information/class/{class}', ['as' => 'info.page.class', 'uses' => 'InfoPageController@viewClass']);

Auth::routes();
