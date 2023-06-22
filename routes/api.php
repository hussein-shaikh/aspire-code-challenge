<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::group(["namespace" => '\App\Http\Controllers\Api'], function () {
    Route::post('/register', 'AuthController@register');
    Route::post('/login', 'AuthController@login');
});


Route::group(["namespace" => '\App\Http\Controllers\Api', "middleware" => "auth:sanctum"], function () {
    Route::post('/logout', 'AuthController@logout');



    Route::group(["prefix" => "loan"], function () {
        Route::post('/create', 'LoanController@createLoan');
        Route::get('/view', 'LoanController@viewLoan');
        Route::post('/repay/{id}', 'LoanController@repayLoan');
    });


    Route::group(["prefix" => "admin", "middleware" => "admin", "namespace" => '\App\Http\Controllers\Api\Admin'], function () {
        Route::group(["prefix" => "loan"], function () {
            Route::get('/{type}', 'LoanController@viewLoan');
            Route::post('/approve/{id}', 'LoanController@approveLoanRequest');
            Route::post('/reject/{id}', 'LoanController@rejectLoanRequest');
        });
    });
});
