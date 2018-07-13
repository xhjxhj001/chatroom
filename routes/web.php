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
    return view('welcome');
});
Route::any('/wechat', 'Wechat\WeChatController@serve');
Route::get('/test', 'IndexController@index');
Route::get('/order', 'OrderController@getorder');
Route::post('/orderbysql', 'OrderController@makeOrderByMysql');
Route::post('/initorder', 'OrderController@initorder');
Route::post('/makeorder', 'OrderController@makeorder');

Route::post('/login', 'IndexController@Login');
Route::get('/user', 'IndexController@getUser');
Route::post('/signup', 'IndexController@signUp');
Route::get('/signlist', 'IndexController@getSignList');

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
