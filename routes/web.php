<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GoogleSocialiteController;



Route::get('/oauth/google/redirect', [GoogleSocialiteController::class, 'redirect']);
Route::get('/oauth/google/callback', [GoogleSocialiteController::class, 'callback']);

Route::get('/', function () {
    return view('welcome');
});
