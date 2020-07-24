<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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


Route::get('find_hotels','HotelsController@find_hotel');
Route::get('best_hotel','HotelsController@BestHotelAPI')->name('best_hotel');
Route::get('top_hotel','HotelsController@TopHotelsAPI')->name('top_hotels');