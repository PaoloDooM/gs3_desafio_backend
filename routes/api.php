<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\CheckProfile;
use App\Models\Profile;

Route::post('/user/login', 'App\Http\Controllers\UsersController@userLogin');

Route::middleware(['auth:sanctum'])->group(function () {
    //User
    Route::get('/user', 'App\Http\Controllers\UsersController@loggedUser');
    Route::put('/user', 'App\Http\Controllers\UsersController@updateCurrentUser');
    Route::post('/user/address', 'App\Http\Controllers\UsersController@addAddress');
    Route::delete('/user/address/{id}', 'App\Http\Controllers\UsersController@deleteAddress');
    Route::put('/user/address', 'App\Http\Controllers\UsersController@updateAddress');
    Route::post('/user/phonenumber', 'App\Http\Controllers\UsersController@addPhoneNumber');
    Route::delete('/user/phonenumber/{id}', 'App\Http\Controllers\UsersController@deletePhoneNumber');
    Route::put('/user/phonenumber', 'App\Http\Controllers\UsersController@updatePhoneNumber');
    //Admin
    Route::middleware(CheckProfile::class . ':'.Profile::PROFILES['admin'])->group(function(){
        Route::get('/profile/list', 'App\Http\Controllers\UsersController@getProfiles');
        Route::post('/user', 'App\Http\Controllers\UsersController@createUser');
        Route::get('/user/list', 'App\Http\Controllers\UsersController@listUsers');
        Route::delete('/user/{id}', 'App\Http\Controllers\UsersController@deleteUser');
        Route::get('/user/{id}', 'App\Http\Controllers\UsersController@getUserById');
        Route::put('/user/{id}', 'App\Http\Controllers\UsersController@updateUserById');
        Route::post('/user/{id}/address', 'App\Http\Controllers\UsersController@addAddressByUserId');
        Route::delete('/user/{user_id}/address/{address_id}', 'App\Http\Controllers\UsersController@deleteAddressFromUserId');
        Route::put('/user/{id}/address', 'App\Http\Controllers\UsersController@updateAddressByUserId');
        Route::post('/user/{id}/phonenumber', 'App\Http\Controllers\UsersController@addPhoneNumberByUserId');
        Route::delete('/user/{user_id}/phonenumber/{phone_id}', 'App\Http\Controllers\UsersController@deletePhoneNumberFromUserId');
        Route::put('/user/{id}/phonenumber', 'App\Http\Controllers\UsersController@updatePhoneNumberByUserId');
    });
});
