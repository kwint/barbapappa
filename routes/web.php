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

Route::get('/', 'PagesController@index')->name('index');
Route::get('/about', 'PagesController@about')->name('about');

// Posts
Route::resource('posts', 'PostsController');

//Auth::routes();

// Authentication
Route::get('/login', 'LoginController@login')->name('login');
Route::post('/login', 'LoginController@doLogin');
Route::get('/register', 'RegisterController@register')->name('register');
Route::post('/register', 'RegisterController@doRegister');
Route::get('/password/request', 'PasswordForgetController@request')->name('password.request');
Route::post('/password/request', 'PasswordForgetController@doRequest');
Route::get('/password/reset/{token?}', 'PasswordResetController@reset')->name('password.reset');
Route::post('/password/reset', 'PasswordResetController@doReset');

// TODO: Create a proper controller for this mail verification route
Route::get('/email/verify/{token?}', 'PasswordResetController@reset')->name('email.verify');

// Dashboard
Route::get('/dashboard', 'DashboardController@index')->name('dashboard');