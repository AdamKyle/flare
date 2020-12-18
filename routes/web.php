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
Route::get('/information/skill/{skill}', ['as' => 'info.page.skill', 'uses' => 'InfoPageController@viewSkill']);
Route::get('/information/adventure/{adventure}', ['as' => 'info.page.adventure', 'uses' => 'InfoPageController@viewAdventure']);

Route::get('/releases', ['as' => 'releases.list', 'uses' => 'ReleasesController@index']);

Route::get('/security-questions/{user}', ['as' => 'user.security.questions', 'uses' => 'Auth\ForgotPasswordController@answerSecurityQuestions']);
Route::get('/reset-security-questions/{user}', ['as' => 'user.reset.security.questions', 'uses' => 'Auth\ResetPasswordController@resetSecuityQuestions']);
Route::post('/security-questions/{user}/answers', ['as' => 'user.security.questions.answers', 'uses' => 'Auth\ForgotPasswordController@securityQuestionsAnswers']);
Route::post('/reset-security-questions/{user}/answers', ['as' => 'user.reset.security.questions.answers', 'uses' => 'Auth\ResetPasswordController@updateSecurityQuestions']);

Route::get('/un-ban-request', ['as' => 'un.ban.request', 'uses' => 'UnbanRequestController@unbanRequest']);
Route::get('/un-ban/security-check/{user}', ['as' => 'un.ban.security.check', 'uses' => 'UnbanRequestController@securityForm']);
Route::get('/un-ban/request-form/{user}', ['as' => 'un.ban.request.form', 'uses' => 'UnbanRequestController@requestForm']);
Route::post('/request-email', ['as' => 'un.ban.request.email', 'uses' => 'UnbanRequestController@findUser']);
Route::post('/request-security/{user}', ['as' => 'un.ban.request.security', 'uses' => 'UnbanRequestController@securityCheck']);
Route::post('/request-submit/{user}', ['as' => 'un.ban.request.submit', 'uses' => 'UnbanRequestController@submitRequest']);


Auth::routes();
