<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/lottery/upload', 'LotteryController@upload');
Route::post('/lottery', 'LotteryController@store');
Route::post('/lottery/user_address', 'LotteryController@createUserAddress');
Route::put('/lottery/user_address', 'LotteryController@updateLotteryAddress');
Route::delete('/lottery/user_address', 'LotteryController@deleteUserAddress');

// 下面是回流鱼小程序

