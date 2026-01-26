<?php

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

$middleware = [
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class
];

Route::prefix('auth')->middleware($middleware)->group(function() {
    Route::prefix('login')->group(function() {
        Route::get('/', 'LoginController@init')->name('login');
        Route::post('/', 'LoginController@login');
    });

    Route::get('{type}/method/{userAuth}', 'MethodController@init')->name('auth.method');
    Route::post('{type}/method/{userAuth}', 'MethodController@set')->name('auth.method.set');

    Route::get('{type}/verification/{userAuth}', 'VerificationController@init')->name('auth.verification');
    Route::post('{type}/verification/{userAuth}', 'VerificationController@set')->name('auth.verification.set');

    Route::get('logout', 'LogoutController@logout')->middleware('auth')->name('logout');
});
