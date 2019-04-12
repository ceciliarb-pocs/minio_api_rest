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

Route::post('/uploadStorage', 'ObjectController@uploadFileStorage');
Route::get('/getStorage', 'ObjectController@getFileStorage');
Route::get('/getUrlStorage', 'ObjectController@getUrlFileStorage');
Route::get('/downloadStorage', 'ObjectController@downloadFileStorage');
Route::delete('/deleteStorage', 'ObjectController@deleteFileStorage');

Route::get('/arquivos/{id?}/{modo?}', 'ObjectController@getObjAws');
Route::post('/arquivos', 'ObjectController@putObjAws');
Route::delete('/arquivos/{id}', 'ObjectController@deleteObjAws');
