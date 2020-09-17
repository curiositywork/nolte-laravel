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

Route::group(['prefix' => 'v1/company'], function () {
  Route::get('feedback', 'CompanyController@feedback');
  Route::get('report', 'CompanyController@report');
  Route::get('feedback/{id}', 'FeedbackController@show');
  Route::post('insights', 'InsightsController@insights');
  Route::post('store', 'CompanyController@store');
  Route::post('components', 'CompanyController@components');
  Route::patch('feedback/archive/{id}', 'FeedbackController@archive');
});
