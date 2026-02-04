<?php

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Upsoftware\Svarium\Http\Middleware\LocaleMiddleware;
use Upsoftware\Svarium\Http\Middleware\HandleInertiaRequests;

$middleware = ['web'];
$middleware[] = LocaleMiddleware::class;
$middleware[] = HandleInertiaRequests::class;

if (config('tenancy.enabled', false)) {
    $middleware[] = InitializeTenancyByDomain::class;
    $middleware[] = PreventAccessFromCentralDomains::class;
}

Route::prefix('auth')->middleware($middleware)->group(function() {
    Route::prefix('login')->group(function() {
        Route::get('/', 'LoginController@init')->name('login');
        Route::post('/', 'LoginController@login');
    });

    Route::prefix('{type}')->group(function() {
        Route::prefix('method')->group(function() {
            Route::prefix('{userAuth}')->group(function() {
                Route::get('/', 'MethodController@init')->name('auth.method');
                Route::post('/', 'MethodController@set')->name('auth.method.set');
            });
        });

        Route::prefix('verification')->group(function() {
            Route::prefix('{userAuth}')->group(function() {
                Route::get('/', 'VerificationController@init')->name('auth.verification');
                Route::post('/', 'VerificationController@set')->name('auth.verification.set');
            });
        });
    });

    Route::prefix('reset')->group(function() {
        Route::get('/', 'ResetController@init')->name('reset');
        Route::post('/', 'ResetController@reset')->name('reset.set');

        Route::prefix('password/{userAuth}')->group(function() {
            Route::get('/', 'ResetPasswordController@init')->name('reset.password');
            Route::post('/', 'ResetPasswordController@reset')->name('reset.password.set');
        });
    });

    Route::get('logout', LogoutController::class)->middleware('auth')->name('logout');
});

Route::get('locale/{locale}', LocaleController::class)->name('locale');
