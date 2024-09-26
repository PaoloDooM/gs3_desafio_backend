<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\CheckProfile;

Route::post('/user/login', 'App\Http\Controllers\UsersController@userLogin');

Route::middleware(['auth:sanctum'])->group(function () {
    //User
    Route::get('/user', 'App\Http\Controllers\UsersController@loggedUser');
    //Admin
    Route::post('/user/create', 'App\Http\Controllers\UsersController@createUser')->middleware(CheckProfile::class.':admin');
    Route::get('/user/list', 'App\Http\Controllers\UsersController@listUsers')->middleware(CheckProfile::class.':admin');
});
