<?php

use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function() {
    Route::prefix('login')->group(function() {
        Route::get('/', 'LoginController@init');
    });
});
